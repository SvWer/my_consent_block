<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for editing HTML block instances.
 *
 * @package block_my_consent_block
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/blocks/my_consent_block/classes/form/mail_form.php');

class block_my_consent_block extends block_base
{

    function init()
    {
        $this->title = get_string('pluginname', 'block_my_consent_block');
    }

    function has_config()
    {
        return true;
    }

    function get_content()
    {
        global $DB, $USER, $PAGE, $OUTPUT;
        
      //Put standard email in DB, if not already in there
         $check_mail = $DB->get_record('disea_mail', array('courseid' => $PAGE->course->id));
         
         if(!$check_mail) {
             $dataobject = new stdClass();
             $dataobject->courseid = $PAGE->course->id;
             $dataobject->email = 'sven.milde@th-luebeck.de';
             $DB->insert_record('disea_mail', $dataobject);
         }

        // url to redirect to consent
        $url = new moodle_url('/blocks/my_consent_block/consent.php', array(
            'id' => $PAGE->course->id
        ));

        // Check database, if user already signed the consent
        $user = $DB->get_record('disea_consent', array(
            'userid' => $USER->id,
            'courseid' => $PAGE->course->id
        ));

        if (! $user) {
            // If user is not in database for this course, he has to read and sign the consent
            redirect($url);
        } else {
            // If user is already in database, he stays at course, but now in the block there need
            // to be button, so that user can change his mind
            if ($user->choice === "1") {
                $choice_text = get_string('choice_yes', 'block_my_consent_block');
            } else {
                $choice_text = get_string('choice_no', 'block_my_consent_block');
            }
            $templatecontext = (object) [
                'editurl' => $url,
                'text' => get_string('edit', 'block_my_consent_block'),
                'choice_text' => $choice_text
            ];
            $content = $OUTPUT->render_from_template('block_my_consent_block/block_content', $templatecontext);
            
             //Check if User is teacher+ or not to show aditional settings for teachers+
            if(has_capability('block/block_my_consent_block:addinstance', context_course::instance($PAGE->course->id))) {
                //Get email for this course:
                $usermail = $DB->get_record('disea_mail', array('courseid' => $PAGE->course->id));
                //Set form for email input
                $urltest = new moodle_url('/course/view.php', array(
                    'id' => $PAGE->course->id
                ));
                $mform = new mail_form($urltest);
                $mform->set_data((object)array('emailtext'=> $usermail->email));
                
                //If new email is submitted, put email in database
                if ($fromform = $mform->get_data()){
                    $usermail = $DB->get_record('disea_mail', array('courseid' => $PAGE->course->id));
                    $usermail->email = $fromform->emailtext;
                    $DB->update_record('disea_mail', $usermail);
                }
                //Show form in block
                $content .= $mform->render();
            } 
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = $content;
        return $this->content;
    }
}
