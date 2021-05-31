<?php

/**
 * Version details.
 * 
 * {log_task} class definition
 *
 * @package    my_consent_block
 * @author	   Sven
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_consent_block\task;
//defined('MOODLE_INTERNAL') || die();

class log_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('log_task_name', 'block_my_consent_block');
    }

    public function execute() {
        global $CFG, $DB;
        $counter = 1;
        //Delete old file for Logdata, because otherwise it will create errors
        $DB->delete_records('files', array('filename'=> 'Logdata.csv'));
        //get all users, that accepted the consent
        $consent_user = $DB->get_records('disea_consent', array('choice' => '1'));
        $consent_users = array_values($consent_user);
        //create where clause from users
        $where = 'WHERE ';
        $count = 0;
        foreach ($consent_users as $users) {
            if($count == 0) {
                $where .= 'lsl.userid = '.$users->userid;
                $count++;
            } else {
                $where .= ' OR lsl.userid = '.$users->userid;
            }
        }
        //Create full query to get all the logdata
        $query = 'SELECT lsl.id, lsl.eventname, lsl.component, lsl.action, lsl.target, lsl.objecttable, '.
                'lsl.objectid, lsl.crud, lsl.edulevel, lsl.contextid, lsl.contextlevel, lsl.contextinstanceid, '.
                'lsl.userid, lsl.courseid, lsl.relateduserid, lsl.anonymous, lsl. other, lsl.timecreated, '.
                'lsl.origin, lsl.ip, lsl.realuserid FROM mdl_logstore_standard_log lsl '. $where;
        //get Logdata from database
        $log_data = $DB->get_records_sql($query);
        //save this Logdata
        $data = array_values($log_data);
        //Create CSV-File from logdata
        $fh = fopen('php://temp', 'rw');
        fputcsv($fh, array('id','eventname','component','action','target',
            'obejttable','obejtid','crud','edulevel','contextid',
            'contextlevel','contextinstanceid','userid','courseid',
            'relateduserid','anonymous','other','timecreated',
            'origin','ip','realuserid','id','userid','courseid',
            'choice','timecreated','timemodified'));
        if (count($data) > 0) {
            foreach ($data as $row) {
                fputcsv($fh, json_decode(json_encode($row), true));
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        
        // get Users who participate in sending the message 
        // this has to be changed in the future, so that you are able to define the recipient in the settings of the plugin
        $user = $DB->get_record('user', array('id' => 1));
        
        $tomail = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'consent'));
        $userto = $DB->get_record('user', array('email' => $tomail->value));
        
        //create message
        $message = new \core\message\message();
        $message->component = 'block_my_consent_block';
        $message->name = 'logdata_disea';
        $message->userfrom =\core_user::get_noreply_user();
        $message->userto = $userto;
        $message->subject = 'DiSEA Logdaten'.time();
        $message->fullmessage = 'Anbei erhalten Sie die Logdaten der Nutzer, die zugestimmt haben beim DiSEA-Projekt mitzuwirken.';
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = '<p>message body</p>';
        $message->smallmessage = 'small message';
        $message->notification = 1;
        $message->contexturl = (new \moodle_url('/course/'))->out(false);
        $message->contexturlname = 'Log Daten';
        $message->replyto = 'noreply@dev-moodle.de';
        $content = array('*' => array('header' => ' Sehr geehrte/r Mensch ', 'footer' => ' Auf bald, ihr Administrator ')); // Extra content for specific processor
        $message->set_additional_content('email', $content);
        
        //Create a file instance(attachment)
        $usercontext = \context_user::instance($user->id);
        $file = new \stdClass();
        $file->contextid = $usercontext->id;
        $file->component = 'user';
        $file->filearea  = 'private';
        $file->itemid    = $counter++;
        $file->filepath  = '/';
        $file->filename  = 'logdata'.time().'.csv';
        $file->source    = 'test';
        
        //attach file to message
        $fs = get_file_storage();
        $file = $fs->create_file_from_string($file, $csv);
        $message->attachment = $file;
        $message->attachname = 'logdata.csv';
        //send message
        $messageid = message_send($message);
    }
    
}