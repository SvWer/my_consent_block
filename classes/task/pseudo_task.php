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

class pseudo_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('pseudo_task_name', 'block_my_consent_block');
    }
    
    public function execute() {
        global $CFG, $DB;
        $counter = 1;
        //Delete old file for Logdata, because otherwise it will create errors
        $DB->delete_records('files', array('filename'=> 'peudodata.csv'));

        //get Logdata from database
        $pseudo_data = $DB->get_records('disea_pseudo');
        //save this Logdata
        $data = array_values($pseudo_data);
        //Create CSV-File from logdata
        $fh = fopen('php://temp2', 'rw');
        fputcsv($fh, array('id','userid','firstname','middlename','lastname','email','hash'));
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
        
        //Get the mail addresses for each course
        $email = $DB->get_records('disea_mail');
        $emails = array_values($email);
        
        //$tomail = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'consent'));
        $userto = $DB->get_record('user', array('email' => $emails[3]->email));
        
        //create message
        $message = new \core\message\message();
        $message->component = 'block_my_consent_block';
        $message->name = 'pseudodata_disea';
        $message->userfrom =\core_user::get_noreply_user();
        $message->userto = $userto;
        $message->subject = 'DiSEA Pseudodaten'.time();
        $message->fullmessage = 'Anbei erhalten Sie die Pseudodaten der Nutzer, die zugestimmt haben beim DiSEA-Projekt mitzuwirken.';
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
        $file->filename  = 'pseudodata'.time().'.csv';
        $file->source    = 'test';
        
        //attach file to message
        $fs = get_file_storage();
        $file = $fs->create_file_from_string($file, $csv);
        $message->attachment = $file;
        $message->attachname = 'pseudodata.csv';
        //send message
        $messageid = message_send($message);
        
    }
    
}