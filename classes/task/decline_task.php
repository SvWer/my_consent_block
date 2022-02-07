<?php

/**
 * Version details.
 *
 * {decline_task} class definition
 *
 * @package    my_consent_block
 * @author	   Sven
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_consent_block\task;

class decline_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('decline_task_name', 'block_my_consent_block');
    }
    
    public function execute() {
        global $CFG, $DB;
        $filename = date("Y-m-d--H.i.s");
        //get Time of last run of Log Task
        $sql_t = 'SELECT MAX(timestart) as timestart FROM mdl_task_log WHERE classname = "block_my_consent_block\\\\task\\\\decline_task"';
        $t = $DB->get_records_sql($sql_t);
        $t = array_values($t);
        
        mtrace("Last time log task: ".intval($t[0]->timestart));
        
        //get all users, that accepted the consent
        $sql_c = 'Select * FROM mdl_disea_consent_all WHERE choice = 0 AND timemodified >'.intval($t[0]->timestart);
        $consent_user = $DB->get_records_sql($sql_c);
        $consent_users = array_values($consent_user);
        
        if($consent_user) {
        
            //Create CSV-String from logdata
            $fh = fopen('php://temp', 'rw');
            fputcsv($fh, array('id','userid','choice','timecreated','timemodified'));
            if (count($consent_users) > 0) {
                foreach ($consent_users as $row) {
                    fputcsv($fh, json_decode(json_encode($row), true));
                }
            }
            rewind($fh);
            $csv = stream_get_contents($fh);
            fclose($fh);
            
            $public_key = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'pub_key'));
            $public_key = $public_key->value;
            
            //Encryption of the csv String, so only the user with the private key can read it
            $publicKey = openssl_get_publickey($public_key);
            $encryptedMessage = "";
            $max_length = 501;
            $output = '';
            while($csv) {
                $input = substr($csv, 0, $max_length);
                $csv = substr($csv, $max_length);
                openssl_public_encrypt($input,$encryptedMessage,$publicKey);
                $output.=$encryptedMessage;
            }
            $message = bin2hex($output);
            mtrace("Decline File encrypted");
            $context = \context_system::instance();
            
            //creation of file
            $file = new \stdClass;
            $file->contextid = $context->id;
            $file->component = 'my_consent_block';
            $file->filearea  = 'disea';
            $file->itemid    = 3;
            $file->filepath  = '/';
            $file->filename  = 'Decline-'.$filename.'.txt';
            $file->source    = 'Decline-'.$filename.'.txt';
            $fs = get_file_storage();
            $file = $fs->create_file_from_string($file, $message);
        }
//#####################################################################################################################################
        //Export User Data with Consent Data
        //get all lines from disea_consent with name
        $sql_c = 'Select d.id, d.userid, d.choice, u.firstname, u.lastname from mdl_disea_consent_all d '.
                  'JOIN mdl_user u ON d.userid = u.id '.
                  'WHERE d.choice = 1';
        $consent_user = $DB->get_records_sql($sql_c);
        $consent_users = array_values($consent_user);

            //Create CSV-String from logdata
            $fh = fopen('php://temp', 'rw');
            fputcsv($fh, array('id','userid','choice','firstname','lastname'));
            if (count($consent_users) > 0) {
                foreach ($consent_users as $row) {
                    fputcsv($fh, json_decode(json_encode($row), true));
                }
            }
            rewind($fh);
            $csv = stream_get_contents($fh);
            fclose($fh);
            
            //Get public key from config
            $public_key = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'pub_key'));
            $public_key = $public_key->value;
            
            //Encryption of the csv String, so only the user with the private key can read it
            $publicKey = openssl_get_publickey($public_key);
            $encryptedMessage = "";
            $max_length = 501;
            $output = '';
            while($csv) {
                $input = substr($csv, 0, $max_length);
                $csv = substr($csv, $max_length);
                openssl_public_encrypt($input,$encryptedMessage,$publicKey);
                $output.=$encryptedMessage;
            }
            $message = bin2hex($output);
            mtrace("User data encrypted");
            $context = \context_system::instance();
            
            //creation of file
            $file = new \stdClass;
            $file->contextid = $context->id;
            $file->component = 'my_consent_block';
            $file->filearea  = 'disea';
            $file->itemid    = 1;
            $file->filepath  = '/';
            $file->filename  = 'UserData-'.$filename.'.txt';
            $file->source    = 'UserData-'.$filename.'.txt';
            $fs = get_file_storage();
            $file = $fs->create_file_from_string($file, $message);
        
        //###########################################################################################################################
        //create statistik 
        $sql_c = 'Select d.courseid, COUNT(case when d.choice = 1 then 1 else null end) as yes, '.
            'COUNT(case when d.choice = 0 then 1 else null end) as no '.
            'from mdl_disea_consent d '.
            'Group by d.courseid';
        $consent_user = $DB->get_records_sql($sql_c);
        $consent_users = array_values($consent_user);
        
        //Create CSV-String from logdata
        $fh = fopen('php://temp', 'rw');
        fputcsv($fh, array('Kurs','Ja','Nein'));
        if (count($consent_users) > 0) {
            foreach ($consent_users as $row) {
                fputcsv($fh, json_decode(json_encode($row), true));
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        
        //Get public key from config
        $public_key = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'pub_key'));
        $public_key = $public_key->value;
        
        //Encryption of the csv String, so only the user with the private key can read it
        $publicKey = openssl_get_publickey($public_key);
        $encryptedMessage = "";
        $max_length = 501;
        $output = '';
        while($csv) {
            $input = substr($csv, 0, $max_length);
            $csv = substr($csv, $max_length);
            openssl_public_encrypt($input,$encryptedMessage,$publicKey);
            $output.=$encryptedMessage;
        }
        $message = bin2hex($output);
        mtrace("Statistics encrypted");
        
        $context = \context_system::instance();
        
        //creation of file
        $file = new \stdClass;
        $file->contextid = $context->id;
        $file->component = 'my_consent_block';
        $file->filearea  = 'disea';
        $file->itemid    = 2;
        $file->filepath  = '/';
        $file->filename  = 'Statistics-'.$filename.'.txt';
        $file->source    = 'Statistics-'.$filename.'.txt';
        $fs = get_file_storage();
        $file = $fs->create_file_from_string($file, $message);
    }
    
}