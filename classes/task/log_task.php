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
        //get Time of last run of Log Task
        $sql_t = 'SELECT MAX(timestart) as timestart FROM mdl_task_log WHERE classname = "block_my_consent_block\\\\task\\\\log_task"';
        $t = $DB->get_records_sql($sql_t);
        $t = array_values($t);
        
        mtrace("Last time log task: ".intval($t[0]->timestart));
        
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
        
        mtrace('Rows collected from db ' . count($data) .'\n\n');
        mtrace('First Row: ');
        if(count($data)>0) {
            var_dump($data[0]);
        }
        //Create CSV-String from logdata
        $filename = date("Y-m-d--H.i.s"); 
        
        $fh = fopen('php://temp', 'rw');
        fputcsv($fh, array('id','eventname','component','action','target',
            'obejttable','obejtid','contextid',
            'contextlevel','contextinstanceid','userid','firstname','lastname','courseid','coursename_short',
            'relateduserid','other','timecreated'));
        if (count($data) > 0) {
            foreach ($data as $row) {
                fputcsv($fh, json_decode(json_encode($row), true));
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);


        $public_key = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'pub_key'));
        $public_key = $public_key->value;
        
        mtrace('public key from config: ' . $public_key. '\n\n');
        mtrace('First values before encrypted message: '.substr($csv, 0, 501).'...\n\n');
        
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
        
        mtrace('First hexvalues of encrypted message: '.substr($message, 0, 501).'...\n\n');
        
        //get course
        $text = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'courseid'));
        $course = $DB->get_record('course', array('id'=> $text->value));
        $modulename = 'resource';
        
        mtrace('Courseid from config: '.$course->id);
        
        require_once($CFG->dirroot . '/course/modlib.php');
        
        $component = 'mod_resource';
        $filearea = 'content';
        $itemid = 0;
        
        $data3 = new \stdClass();
        $data3->course = $course->id;
        $data3->name = 'Log-'.$filename.'.txt';
        $data3->intro = '';
        $data3->introformat = FORMAT_HTML;
        $data3->section = 1;
        $data3->module = 18;
        $data3->modulename =$modulename;
        $data3->add ='resource';
        $data3->return = 0;
        $data3->sr = 0;
        $data3->files = $itemid;
        $data3->visible= 1;
        $data3->display = 4;
        //Values from set_moduleinfo_defaults
        $data3->instance = '';
        $data3->coursemodule = '';
        $data3->groupingid = 0;
        $data3->completion = COMPLETION_DISABLED;
        $data3->completionview = COMPLETION_VIEW_NOT_REQUIRED;
        $data3->completionexpected = 0;
        $data3->completiongradeitemnumber = null;
        $data3->conditiongradegroup = array();
        $data3->conditionfieldgroup = array();
        $data3->visibleoncoursepage = 1;
        //Other values set here before
        $data3->groupmode = 0;
        $data3->visibleold = 1;
        $data3->showdescription = 0;
        
        $mform = null;
        include_modulelib('resource');
        
        // From this point we make database changes, so start transaction.
        $transaction = $DB->start_delegated_transaction();
        
        mtrace("\n\n data3 add_course_module: ");
        var_dump($data3);
        
        if (!$data3->coursemodule = add_course_module($data3)) {
            print_error('cannotaddcoursemodule');
        }
        
        if (plugin_supports('mod', $data3->modulename, FEATURE_MOD_INTRO, true) &&
            isset($data3->introeditor)) {
                $introeditor = $data3->introeditor;
                unset($data3->introeditor);
                $data3->intro       = $introeditor['text'];
                $data3->introformat = $introeditor['format'];
            }
            
            $addinstancefunction    = $data3->modulename."_add_instance";
            try {
                $returnfromfunc = $addinstancefunction($data3, $mform);
            } catch (\moodle_exception $e) {
                $returnfromfunc = $e;
            }
            if (!$returnfromfunc or !is_number($returnfromfunc)) {
                // Undo everything we can. This is not necessary for databases which
                // support transactions, but improves consistency for other databases.
                \context_helper::delete_instance(CONTEXT_MODULE, $data3->coursemodule);
                $DB->delete_records('course_modules', array('id'=>$data3->coursemodule));
                
                if ($returnfromfunc instanceof \moodle_exception) {
                    throw $returnfromfunc;
                } else if (!is_number($returnfromfunc)) {
                    print_error('invalidfunction', '', course_get_url($course, $data3->section));
                } else {
                    print_error('cannotaddnewmodule', '', course_get_url($course, $data3->section), $data3->modulename);
                }
            }
            
            $data3->instance = $returnfromfunc;
            
            $DB->set_field('course_modules', 'instance', $returnfromfunc, array('id'=>$data3->coursemodule));
            
            // Update embedded links and save files.
            $modcontext = \context_module::instance($data3->coursemodule);
            
            $draftitemid = file_get_submitted_draft_itemid($filearea);
            file_prepare_draft_area($draftitemid, $modcontext->id, $component, $filearea, $itemid);
            $fs = get_file_storage();
            
            mtrace("Draftitemid: ".$draftitemid.'\n\n');
            mtrace("fileStorage: ");
            var_dump($fs);
            
            $filerecord = array(
                'contextid' => $modcontext->id,
                'component' => $component,
                'filearea' => $filearea,
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => 'Log-'.$filename.'.txt',
                'source' => 'disea_consent'
            );
            
            $fs->create_file_from_string($filerecord, $message);
            //file_save_draft_area_files($draftitemid, $context->id, $component, $filearea, $itemid);
            
            $file = $fs->get_area_files($modcontext->id, $component, $filearea, 0, $itemid, false);
            $file = reset($file);
            
            var_dump($CFG->dataroot);
            var_dump(substr(sprintf('%o', fileperms($CFG->dataroot)), -4));
            if (is_writable($CFG->dataroot)) {
                var_dump( 'Die Datei kann geschrieben werden');
            } else {
                var_dump( 'Die Datei kann nicht geschrieben werden');
            }
            var_dump($CFG->dataroot.'/filedir');
            var_dump(substr(sprintf('%o', fileperms($CFG->dataroot.'/filedir')), -4));
            if (is_writable($CFG->dataroot.'/filedir')) {
                var_dump('Die Datei kann geschrieben werden');
            } else {
                var_dump( 'Die Datei kann nicht geschrieben werden');
            }
            var_dump($CFG->dataroot.'/trashdir');
            var_dump(substr(sprintf('%o', fileperms($CFG->dataroot.'/trashdir')), -4));
            if (is_writable($CFG->dataroot.'/trashdir')) {
                var_dump('Die Datei kann geschrieben werden');
            } else {
                var_dump( 'Die Datei kann nicht geschrieben werden');
            }
            var_dump($CFG->dataroot.'/temp/filestorage');
            var_dump(substr(sprintf('%o', fileperms($CFG->dataroot.'/temp/filestorage')), -4));
            if (is_writable($CFG->dataroot.'/temp/filestorage')) {
                var_dump('Die Datei kann geschrieben werden');
            } else {
                var_dump( 'Die Datei kann nicht geschrieben werden');
            }
            
            $sectionid = course_add_cm_to_section($course, $data3->coursemodule, $data3->section);
            
            mtrace("Sectionid: ".$sectionid);
            
            // Trigger event based on the action we did.
            // Api create_from_cm expects modname and id property, and we don't want to modify $moduleinfo since we are returning it.
            $eventdata = clone $data3;
            $eventdata->modname = $eventdata->modulename;
            $eventdata->id = $eventdata->coursemodule;
            $event = \core\event\course_module_created::create_from_cm($eventdata, $modcontext);
            $event->trigger();
            
            $data3 = edit_module_post_actions($data3, $course);
            $transaction->allow_commit();
            
            $sql_c = 'Select d.courseid, COUNT(case when d.choice = 1 then 1 else null end) as yes, '.
                'COUNT(case when d.choice = 0 then 1 else null end) as no '.
                'from mdl_disea_consent d '.
                'Group by d.courseid';
            $consent_user = $DB->get_records_sql($sql_c);
            $consent_users = array_values($consent_user);
            mtrace("Print some statistics");
            mtrace("Anzahl Kurse mit aktiviertem Consent: ".count($consent_users));
            var_dump($consent_users);
    }
    
}