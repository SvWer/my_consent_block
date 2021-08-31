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
        global $CFG, $DB;
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
        $data = array_values($log_data);
        //Create CSV-String from logdata
        $filename = date("Y-m-d--H.i.s"); 
        
        $fh = fopen('php://temp', 'rw');
        fputcsv($fh, array('id','eventname','component','action','target',
            'obejttable','obejtid','crud','edulevel','contextid',
            'contextlevel','contextinstanceid','userid','courseid',
            'relateduserid','anonymous','other','timecreated',
            'origin','ip','realuserid'));
        if (count($data) > 0) {
            foreach ($data as $row) {
                fputcsv($fh, json_decode(json_encode($row), true));
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        
        
        //$path = $CFG->dataroot.'\\'.$filename.'.csv';
        //$fh = fopen($path, 'x+');
        //fwrite($fh, $csv);
        //fclose($fh);
       
        //Der Key muss noch ausgetauscht werden 
        $public_key = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA/foMA2jwiqBXGs/vxZiz
4kukg6snWkpVjVz5WqhrMf6ZxY7RRJv9DhISBnaXDiOcgBGozyCXPzJDA0qKTIgG
u67j7b1skUfKKL0p0S7rGem8uFGzBKx8/J4ny84OI8/fDHon+IuVRpy7tPqZVD+b
5Z/qBdSj9qJvaypENwVFGz211Sw8bJp+r0XKXvBpnDTFbarRExXGPzIsm4niBhoH
MoYgeKf+Ufjot3qHFZ3lKbQ2ydio50IDuzX1Adv6ex/8pq0vEulL+h3tWIWKsg87
G2OmDRrE7m2CRLTpDs/ioTtzAlP+SOlflusE40rBdn/H7cgNLSeCMXLT7OsnA24I
+jh5pZPEzbFP6Yj3UAXYIhDX6AwhuJoRjqYBKwVgO3RqcsNxGkSyIKOIWG4aoYDq
TfZY3MZBQLKZAIpALQDW3rr8qnM5b1s5p8I40HpsBLJT1phqIfUOwhuWGSNG6iND
E/q36dWwPkN024l3Cj/dG3YEdYHqiwR5aB7aUY/tX06Sb8SvzZE3TEhldHnaa1zB
u2ocy4i3ft3NHlbgyw78/mIZ4STNzNcVyWm0EkBi1ELEaSvY7igmT8i2nH7KNglh
QATAPY8Aw9iXk98ZsP3PG5Dc2Jycusg8tYNZmbiDr5PrCCA0UtBylHFNEEgZoPX0
0Kui7xFtYT1cHY0nTjNls4UCAwEAAQ==
-----END PUBLIC KEY-----
';
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
        
        //get course
        $course = $DB->get_record('course', array('id'=> 9));
        $modulename = 'resource';
        
        //Create course module
        require_once($CFG->dirroot . '/course/modlib.php');
        list($module, $context, $cw, $cm, $data2) = prepare_new_moduleinfo_data($course, $modulename, 1);
        $context = \context_module::instance(add_course_module($data2));
        $component = 'mod_resource';
        $filearea = 'content';
        $itemid = 0;
        
        $draftitemid = file_get_submitted_draft_itemid($filearea);
        file_prepare_draft_area($draftitemid, $context->id, $component, $filearea, $itemid);
        $fs = get_file_storage();
        
        $filerecord = array(
            'contextid' => $context->id,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'Log-'.$filename.'.txt',
            'source' => 'disea_consent'
        );
        
        $f = $fs->create_file_from_string($filerecord, $message);
        //$f = $fs->create_file_from_pathname($filerecord, $path);
        var_dump('f: ');
        var_dump($f);
        //file_save_draft_area_files($draftitemid, $context->id, $component, $filearea, $itemid);
        
        $file = $fs->get_area_files($context->id, $component, $filearea, 0, $itemid, false);
        $file = reset($file);
        
        var_dump('######################################################');
        var_dump('contextid: '.$context->id);
        var_dump('component: '.$component);
        var_dump('filearea: '.$filearea);
        var_dump('itemid: '.$itemid);
        var_dump('######################################################');
        var_dump('file');
        var_dump($file);
        var_dump('######################################################');
        var_dump($CFG->dataroot);
        var_dump('######################################################');
        
        
        $uploadinfo = new \stdClass();
        $uploadinfo->type = 'Files';
        $uploadinfo->course = $course;
        $uploadinfo->section = 1;
        $uploadinfo->module = $module->id;
        $uploadinfo->modulename= $module->name;
        $uploadinfo->files=$draftitemid;
        $uploadinfo->displayname = 'Log-'.$filename.'.txt';
        
        $data3 = new \stdClass();
        $data3->course = $uploadinfo->course->id;
        $data3->name = $uploadinfo->displayname;
        $data3->intro = '';
        $data3->introformat = FORMAT_HTML;
        $data3->section = $uploadinfo->section;
        $data3->module =$uploadinfo->module;
        $data3->modulename =$uploadinfo->modulename;
        $data3->add ='resource';
        $data3->return = 0;
        $data3->sr = 0;
        $data3->files = $uploadinfo->files;
        $data3->visible=1;
        
        //test
        $data3->display = 5;
        
        // Set the display options to the site defaults.
        
        add_moduleinfo($data3, $uploadinfo->course);
        
        
        //$context = \context_module::instance(1);
        /*$filerecord = array('component'=>'mod_resource', 'filearea'=>'content', 'contextid'=>$context->id,
         'itemid'=> $instanceid, 'filepath'=>'/', 'filename'=>$filename.'.csv', 'source'=>'disea_consent'
         );*/
        
    }
    
}