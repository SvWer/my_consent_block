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
 * Strings for component 'block_my_consent_block'
 *
 * @package   block_my_consent_block
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/my_consent_block/classes/form/consent_form.php');

global $DB, $USER, $COURSE;

$PAGE->set_url(new moodle_url('/blocks/my_consent_block/consent.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('pluginname', 'block_my_consent_block'));

//Get Course ID from url to be able to redirect
$courseid = optional_param('id',NULL, PARAM_INT);
//Check id user is already in Database
$user = $DB->get_record('disea_consent', array('userid' => $USER->id, 'courseid'=>$courseid));

//create redirecting url
$url = $CFG->wwwroot.'/blocks/my_consent_block/consent.php?id='.$courseid;
$courseurl = $CFG->wwwroot.'/course/view.php?id='.$courseid;

//Make the form
$mform = new consent_form($url);
if($user) {
    $mform->set_data((object)array('agreedis'=> $user->choice));
}

//Check response from consent_form
if($mform->is_cancelled()) {
    if(!$user) {
        //If user wasn't in database and wants to cancell, stay on this page
        redirect($url);
    } else {
        //If user is already in database and cancels, return to course
        redirect($courseurl);
    }
} else if ($fromform = $mform->get_data()){
    //If submitted
    $id = $_POST ['agreedis'];
    
    $choice = 0;
    if($id === '1') {
        $choice = 1;
    }
    
    if(!$user) {
        //if user is not in the database
        $recordtoinsert = new stdClass();
        $recordtoinsert->userid = $USER->id;
        $recordtoinsert->courseid = $courseid;
        $recordtoinsert->choice = $choice;
        $recordtoinsert->timecreated = time();
        $recordtoinsert->timemodified = time();
        $DB->insert_record('disea_consent', $recordtoinsert);
        
        /**/
        //If we need a pseudonymisation list: create it here
        if($choice == 1) {
            $userobj = new stdClass();
            //create object for db entry and string for Hash
            $user_str = '';
            $m_user = $DB ->get_record('user', array('id' => $USER->id));
            $userobj->userid = $USER->id;
            $user_str .= $USER->id . ';';
            $userobj->firstname = $m_user->firstname;
            $user_str .= $m_user->firstname . ';';
            $userobj->middlename = $m_user->middlename;
            $user_str .= $m_user->middlename . ';';
            $userobj->lastname = $m_user->lastname;
            $user_str .= $m_user->lastname . ';';
            $userobj->email = $m_user->email;
            $user_str .= $m_user->email . ';';
            //create hash
            $hash = hash('sha256', $user_str, false);
            $userobj->hash = $hash;
            $DB->insert_record('disea_pseudo', $userobj);
            
        }
        /**/
        
        
        redirect($courseurl, get_string('database_insert', 'block_my_consent_block'));
    } else {
        //if user is in database, it needs to be updated
        $user->choice = $choice;
        $user->timemodified = time();
        $DB->update_record('disea_consent', $user);
        redirect($courseurl, get_string('database_update', 'block_my_consent_block'));
        
        /**/
        //If we need pseudonymisation list and user changes to 'no', delete entry
        if($choice == 0) {
            $DB->delete_records('disea_pseudo', array('userid' => $USER->id));
        }
        /**/
    }
}


echo $OUTPUT->header();

$templatecontext = (object)[
    'consent_title' => get_string('consent_title', 'block_my_consent_block'),
    'consent_greeting' => get_string('consent_greeting', 'block_my_consent_block'),
    'consent_intro' => get_string('consent_intro', 'block_my_consent_block'),
    'advantage_title' => get_string('advantage_title', 'block_my_consent_block'),
    'advantage_one' => get_string('advantage_one', 'block_my_consent_block'),
    'advantage_two' => get_string('advantage_two', 'block_my_consent_block'),
    'data_collection_title' => get_string('data_collection_title', 'block_my_consent_block'),
    'data_collection_one' => get_string('data_collection_one', 'block_my_consent_block'),
    'data_collection_two' => get_string('data_collection_two', 'block_my_consent_block'),
    'sozio_title' => get_string('sozio_title', 'block_my_consent_block'),
    'sozio_one' => get_string('sozio_one', 'block_my_consent_block'),
    'sozio_list_one' => get_string('sozio_list_one', 'block_my_consent_block'),
    'sozio_list_two' => get_string('sozio_list_two', 'block_my_consent_block'),
    'sozio_list_three' => get_string('sozio_list_three', 'block_my_consent_block'),
    'anonymisation_title' => get_string('anonymisation_title', 'block_my_consent_block'),
    'anonymisation_one' => get_string('anonymisation_one', 'block_my_consent_block'),
    'anonymisation_two' => get_string('anonymisation_two', 'block_my_consent_block'),
    'anonymisation_three' => get_string('anonymisation_three', 'block_my_consent_block'),
    'anonymisation_four' => get_string('anonymisation_four', 'block_my_consent_block'),
    
];

echo $OUTPUT->render_from_template('block_my_consent_block/consent', $templatecontext);
$mform->display();
echo $OUTPUT->footer();