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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for editing HTML block instances.
 *
 * @package   block_my_consent_block
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/my_consent_block/classes/form/download_button_form.php');

class block_my_consent_block extends block_base {
    
    function init() {
        $this->title = get_string('pluginname', 'block_my_consent_block');
    }

    function has_config() {
        return true;
    }
    
    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => true,
        );
    }

    function get_content() {
        global $DB, $USER, $PAGE, $OUTPUT, $CFG;
        
        //url to redirect to consent
        $url = new moodle_url('/blocks/my_consent_block/consent.php', array('id'=>$PAGE->course->id));
        $show = new moodle_url('/blocks/my_consent_block/list_files.php', array('id'=>$PAGE->course->id));
        
        //Check database, if user already signed the consent
        $user = $DB->get_record('disea_consent', array('userid' => $USER->id, 'courseid' => $PAGE->course->id));
        
       
       
        if(!$user) {
            //If user is not in database for this course, he has to read and sign the consent
            redirect($url);
        } else {
            //If user is already in database, he stays at course, but now in the block there need
            // to be button, so that user can change his mind
            if($user->choice === "1") {
                $choice_text = get_string('choice_yes', 'block_my_consent_block');
            } else {
                $choice_text = get_string('choice_no', 'block_my_consent_block');
            }
            $templatecontext = (object)[
                'editurl' => $url,
                'text'    => get_string('edit', 'block_my_consent_block'),
                'choice_text' => $choice_text,
            ];
            
            $content = $OUTPUT->render_from_template('block_my_consent_block/block_content', $templatecontext);
            $context = context_system::instance();
            
            var_dump("Capability TEst: ");
            var_dump(has_capability('block/block_my_consent_block:download', $context));
            var_dump("Capability TEst: ");
            var_dump(has_capability('block/block_my_consent_block:download', $context));
            var_dump("Capability TEst: ");
            var_dump(has_capability('block/block_my_consent_block:download', $context));
            var_dump("Capability TEst: ");
            var_dump(has_capability('block/block_my_consent_block:download', $context));
            
            if (has_capability('block/block_my_consent_block:download', $context)) {
                //Create downloadbutton
                $mform = new download_button_form($show);
                
                $content = $content . $mform->render();
            }
            
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = $content;
        return $this->content;
    }

  
}
