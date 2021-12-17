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

class log_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('log_task_name', 'block_my_consent_block');
    }
    
    public function execute() {
        global $DB;
        //get Time of last run of Log Task
        $sql_t = 'SELECT MAX(timestart) as timestart FROM mdl_task_log WHERE classname = "block_my_consent_block\\\\task\\\\log_task"';
        $t = $DB->get_records_sql($sql_t);
        $t = array_values($t);
        
        //SQL Query to get logdata in interval of one week
        $query1 = 'SELECT l.id, l.eventname, l.component, l.action, l.target, l.objecttable, '.
            'l.objectid, l.contextid, l.contextlevel, l.contextinstanceid, '.
            'l.userid, c.id as courseid, c.shortname, l.relateduserid, l. other, l.timecreated '.
            'FROM mdl_logstore_standard_log l '.
            'LEFT JOIN mdl_course c '.
            'ON l.courseid = c.id '.
            'LEFT JOIN (SELECT * FROM mdl_disea_consent d WHERE d.timemodified < '. intval($t[0]->timestart) .' ) disea '.
            'ON l.courseid = disea.courseid '.
            'WHERE l.userid = disea.userid '.
            'AND (l.relateduserid IN (SELECT userid FROM mdl_disea_consent disea2 '.
            'WHERE l.courseid = disea2.courseid AND disea2.choice = 1) '.
            'OR l.relateduserid IS NULL) '.
            'AND disea.choice = 1 AND l.timecreated >'.intval($t[0]->timestart) .
            ' UNION SELECT l.id, l.eventname, l.component, l.action, l.target, l.objecttable, '.
            'l.objectid, l.contextid, l.contextlevel, l.contextinstanceid, '.
            'l.userid, c.id as courseid, c.shortname, l.relateduserid, l. other, l.timecreated '.
            'FROM mdl_logstore_standard_log l '.
            'LEFT JOIN mdl_course c '.
            'ON l.courseid = c.id '.
            'LEFT JOIN (SELECT * FROM mdl_disea_consent d WHERE d.timemodified > '. intval($t[0]->timestart) .' ) disea '.
            'ON l.courseid = disea.courseid '.
            'WHERE l.userid = disea.userid '.
            'AND (l.relateduserid IN (SELECT userid FROM mdl_disea_consent disea2 '.
            'WHERE l.courseid = disea2.courseid AND disea2.choice = 1) '.
            'OR l.relateduserid IS NULL) '.
            'AND disea.choice = 1 ';
        
        //get Logdata from database
        $log_data1 = $DB->get_records_sql($query1);
        $data = array_values($log_data1);
        mtrace("Daten aus Datenbank geholt");
        mtrace("Number Rows: " . count($data));
        
        $blocks = count($data) / 50000;
        //For every block
        for ($i = 0; $i < $blocks; $i++)
        {
            $cnt = 0;
            foreach ($data as $row) {
                $cnt++;
                if($cnt < $i*50000) {
                    continue;
                }
                elseif($cnt > $i*50000 + 50000)
                {
                    break;
                }
                else{
                    $filename = date("Y-m-d--H.i.s").'__'.$i;
                    $fh = fopen('php://temp', 'rw');
                    fputcsv($fh, array('id','eventname','component','action','target',
                        'obejttable','obejtid','contextid',
                        'contextlevel','contextinstanceid','userid','firstname','lastname','courseid','coursename_short',
                        'relateduserid','other','timecreated'));
                    fputcsv($fh, json_decode(json_encode($row), true));
                }
            }
            rewind($fh);
            $csv = stream_get_contents($fh);
            fclose($fh);
            
            mtrace("CSV String erstellt");
            
            //Get public key from config
            $public_key = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'pub_key'));
            $public_key = $public_key->value;
            
            mtrace("Public key geholt");
            
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
            
            mtrace("Nachricht verschluesselt");
            
            $context = \context_system::instance();
            
            //creation of file
            $file = new \stdClass;
            $file->contextid = $context->id;
            $file->component = 'my_consent_block';
            $file->filearea  = 'disea';
            $file->itemid    = 4;
            $file->filepath  = '/';
            $file->filename  = 'Log-'.$filename.'.txt';
            $file->source    = 'Log-'.$filename.'.txt';
            $fs = get_file_storage();
            $file = $fs->create_file_from_string($file, $message);
        }
    }
}