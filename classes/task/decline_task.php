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
        //get Time of last run of Log Task
        $sql_t = 'SELECT MAX(timestart) as timestart FROM mdl_task_log WHERE component = "block_my_consent_block"';
        $t = $DB->get_records_sql($sql_t);
        $t = array_values($t);
        
        
        //get all users, that accepted the consent
        $sql_c = 'Select * FROM mdl_disea_consent WHERE choice = 0 AND timemodified >'.intval($t[0]->timestart);
        $consent_user = $DB->get_records_sql($sql_c);
        $consent_users = array_values($consent_user);
        
        if($consent_user) {
        
            //Create CSV-String from logdata
            
            mtrace("Anzahl von Reihen aus Datenbank: ".count($consent_users). '\n\n');
            
            $fh = fopen('php://temp', 'rw');
            fputcsv($fh, array('id','userid','courseid','choice','timecreated','timemodified'));
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
            
            mtrace('First hexvalues of encrypted message: '.substr($message, 0, 501).'...\n\n');
            
            //get course
            $text = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'courseid'));
            $course = $DB->get_record('course', array('id'=> $text->value));
            $modulename = 'resource';
            
            require_once($CFG->dirroot . '/course/modlib.php');
            
            $component = 'mod_resource';
            $filearea = 'content';
            $itemid = 0;
            $filename = date("Y-m-d");
            
            $data3 = new \stdClass();
            $data3->course = $course->id;
            $data3->name = 'Decline-'.$filename.'.txt';
            $data3->intro = '';
            $data3->introformat = FORMAT_HTML;
            $data3->section = 1;
            $data3->module =18;
            $data3->modulename =$modulename;
            $data3->add ='resource';
            $data3->return = 0;
            $data3->sr = 0;
            $data3->files = $itemid;
            $data3->visible=1;
            $data3->display = 4;
            
            $mform = null;
            include_modulelib('resource');
            $data3 = set_moduleinfo_defaults($data3);
            if (!empty($course->groupmodeforce) or !isset($data3->groupmode)) {
                $data3->groupmode = 0; // Do not set groupmode.
            }
            
            // First add course_module record because we need the context.
            $newcm = new \stdClass();
            $newcm->course           = $course->id;
            $newcm->module           = $data3->module;
            $newcm->instance         = 0; // Not known yet, will be updated later (this is similar to restore code).
            $newcm->visible          = $data3->visible;
            $newcm->visibleold       = $data3->visible;
            $newcm->visibleoncoursepage = $data3->visibleoncoursepage;
            if (isset($data3->cmidnumber)) {
                $newcm->idnumber         = $data3->cmidnumber;
            }
            $newcm->groupmode        = $data3->groupmode;
            $newcm->groupingid       = $data3->groupingid;
            $completion = new \completion_info($course);
            if ($completion->is_enabled()) {
                $newcm->completion                = $data3->completion;
                if ($data3->completiongradeitemnumber === '') {
                    $newcm->completiongradeitemnumber = null;
                } else {
                    $newcm->completiongradeitemnumber = $data3->completiongradeitemnumber;
                }
                $newcm->completionview            = $data3->completionview;
                $newcm->completionexpected        = $data3->completionexpected;
            }
            if(!empty($CFG->enableavailability)) {
                // This code is used both when submitting the form, which uses a long
                // name to avoid clashes, and by unit test code which uses the real
                // name in the table.
                $newcm->availability = null;
                if (property_exists($data3, 'availabilityconditionsjson')) {
                    if ($data3->availabilityconditionsjson !== '') {
                        $newcm->availability = $data3->availabilityconditionsjson;
                    }
                } else if (property_exists($data3, 'availability')) {
                    $newcm->availability = $data3->availability;
                }
                // If there is any availability data, verify it.
                if ($newcm->availability) {
                    $tree = new \core_availability\tree(json_decode($newcm->availability));
                    // Save time and database space by setting null if the only data
                    // is an empty tree.
                    if ($tree->is_empty()) {
                        $newcm->availability = null;
                    }
                }
            }
            if (isset($data3->showdescription)) {
                $newcm->showdescription = $data3->showdescription;
            } else {
                $newcm->showdescription = 0;
            }
            
            // From this point we make database changes, so start transaction.
            $transaction = $DB->start_delegated_transaction();
            
            if (!$data3->coursemodule = add_course_module($newcm)) {
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
                
                $filerecord = array(
                    'contextid' => $modcontext->id,
                    'component' => $component,
                    'filearea' => $filearea,
                    'itemid' => $itemid,
                    'filepath' => '/',
                    'filename' => 'Delcine-'.$filename.'.txt',
                    'source' => 'disea_consent'
                );
                
                $f = $fs->create_file_from_string($filerecord, $message);
                //file_save_draft_area_files($draftitemid, $context->id, $component, $filearea, $itemid);
                mtrace("\n\n\$f after create file from string: ");
                var_dump($f);
                
                $file = $fs->get_area_files($modcontext->id, $component, $filearea, 0, $itemid, false);
                $file = reset($file);
                
                mtrace("\n\nFile: ");
                var_dump($file);
                
                if (!empty($introeditor)) {
                    // This will respect a module that has set a value for intro in it's modname_add_instance() function.
                    $introeditor['text'] = $data3->intro;
                    
                    $data3->intro = file_save_draft_area_files($introeditor['itemid'], $modcontext->id,
                        'mod_'.$data3->modulename, 'intro', 0,
                        array('subdirs'=>true), $introeditor['text']);
                    $DB->set_field($data3->modulename, 'intro', $data3->intro, array('id'=>$data3->instance));
                }
                
                // Add module tags.
                if (\core_tag_tag::is_enabled('core', 'course_modules') && isset($data3->tags)) {
                    \core_tag_tag::set_item_tags('core', 'course_modules', $data3->coursemodule, $modcontext, $data3->tags);
                }
                
                // Course_modules and course_sections each contain a reference to each other.
                // So we have to update one of them twice.
                $sectionid = course_add_cm_to_section($course, $data3->coursemodule, $data3->section);
                
                // Trigger event based on the action we did.
                // Api create_from_cm expects modname and id property, and we don't want to modify $moduleinfo since we are returning it.
                $eventdata = clone $data3;
                $eventdata->modname = $eventdata->modulename;
                $eventdata->id = $eventdata->coursemodule;
                $event = \core\event\course_module_created::create_from_cm($eventdata, $modcontext);
                $event->trigger();
                
                $data3 = edit_module_post_actions($data3, $course);
                $transaction->allow_commit();
        }
//#####################################################################################################################################
        //Export User Data with Consent Data
        //get all lines from disea_consent with name
        $sql_c = 'Select d.id, d.userid, d.choice, d.courseid, u.firstname, u.lastname from mdl_disea_consent d '.
                  'JOIN mdl_user u ON d.userid = u.id '.
                  'WHERE d.choice = 1';
        $consent_user = $DB->get_records_sql($sql_c);
        $consent_users = array_values($consent_user);

            mtrace("\n\nConsent Ja mit Namen\n");
            mtrace("Consent Data eintraege: ".count($consent_users).'\n\n');
            //Create CSV-String from logdata
            $fh = fopen('php://temp', 'rw');
            fputcsv($fh, array('id','userid','choice','courseid','firstname','lastname'));
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
            
            mtrace('First hexvalues of encrypted message: '.substr($message, 0, 501).'...\n\n');
            
            //get course
            $text = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'courseid'));
            $course = $DB->get_record('course', array('id'=> $text->value));
            $modulename = 'resource';
            
            require_once($CFG->dirroot . '/course/modlib.php');
            
            $component = 'mod_resource';
            $filearea = 'content';
            $itemid = 0;
            $filename = date("Y-m-d");
            
            $data3 = new \stdClass();
            $data3->course = $course->id;
            $data3->name = 'ConsentList-'.$filename.'.txt';
            $data3->intro = '';
            $data3->introformat = FORMAT_HTML;
            $data3->section = 1;
            $data3->module =18;
            $data3->modulename =$modulename;
            $data3->add ='resource';
            $data3->return = 0;
            $data3->sr = 0;
            $data3->files = $itemid;
            $data3->visible=1;
            $data3->display = 4;
            
            $mform = null;
            include_modulelib('resource');
            $data3 = set_moduleinfo_defaults($data3);
            if (!empty($course->groupmodeforce) or !isset($data3->groupmode)) {
                $data3->groupmode = 0; // Do not set groupmode.
            }
            mtrace("data3 set module info: ");
            var_dump($data3);
            
            // First add course_module record because we need the context.
            $newcm = new \stdClass();
            $newcm->course           = $course->id;
            $newcm->module           = $data3->module;
            $newcm->instance         = 0; // Not known yet, will be updated later (this is similar to restore code).
            $newcm->visible          = $data3->visible;
            $newcm->visibleold       = $data3->visible;
            $newcm->visibleoncoursepage = $data3->visibleoncoursepage;
            if (isset($data3->cmidnumber)) {
                $newcm->idnumber         = $data3->cmidnumber;
            }
            $newcm->groupmode        = $data3->groupmode;
            $newcm->groupingid       = $data3->groupingid;
            $completion = new \completion_info($course);
            if ($completion->is_enabled()) {
                $newcm->completion                = $data3->completion;
                if ($data3->completiongradeitemnumber === '') {
                    $newcm->completiongradeitemnumber = null;
                } else {
                    $newcm->completiongradeitemnumber = $data3->completiongradeitemnumber;
                }
                $newcm->completionview            = $data3->completionview;
                $newcm->completionexpected        = $data3->completionexpected;
            }
            if(!empty($CFG->enableavailability)) {
                // This code is used both when submitting the form, which uses a long
                // name to avoid clashes, and by unit test code which uses the real
                // name in the table.
                $newcm->availability = null;
                if (property_exists($data3, 'availabilityconditionsjson')) {
                    if ($data3->availabilityconditionsjson !== '') {
                        $newcm->availability = $data3->availabilityconditionsjson;
                    }
                } else if (property_exists($data3, 'availability')) {
                    $newcm->availability = $data3->availability;
                }
                // If there is any availability data, verify it.
                if ($newcm->availability) {
                    $tree = new \core_availability\tree(json_decode($newcm->availability));
                    // Save time and database space by setting null if the only data
                    // is an empty tree.
                    if ($tree->is_empty()) {
                        $newcm->availability = null;
                    }
                }
            }
            if (isset($data3->showdescription)) {
                $newcm->showdescription = $data3->showdescription;
            } else {
                $newcm->showdescription = 0;
            }
            
            // From this point we make database changes, so start transaction.
            $transaction = $DB->start_delegated_transaction();
            
            if (!$data3->coursemodule = add_course_module($newcm)) {
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
                
                $filerecord = array(
                    'contextid' => $modcontext->id,
                    'component' => $component,
                    'filearea' => $filearea,
                    'itemid' => $itemid,
                    'filepath' => '/',
                    'filename' => 'ConsentList-'.$filename.'.txt',
                    'source' => 'disea_consent'
                );
                
                $f = $fs->create_file_from_string($filerecord, $message);
                //file_save_draft_area_files($draftitemid, $context->id, $component, $filearea, $itemid);
                mtrace("\n\n\$f after create file from string: ");
                var_dump($f);
                
                $file = $fs->get_area_files($modcontext->id, $component, $filearea, 0, $itemid, false);
                $file = reset($file);
                
                mtrace("\n\nFile: ");
                var_dump($file);
                
                if (!empty($introeditor)) {
                    // This will respect a module that has set a value for intro in it's modname_add_instance() function.
                    $introeditor['text'] = $data3->intro;
                    
                    $data3->intro = file_save_draft_area_files($introeditor['itemid'], $modcontext->id,
                        'mod_'.$data3->modulename, 'intro', 0,
                        array('subdirs'=>true), $introeditor['text']);
                    $DB->set_field($data3->modulename, 'intro', $data3->intro, array('id'=>$data3->instance));
                }
                
                // Add module tags.
                if (\core_tag_tag::is_enabled('core', 'course_modules') && isset($data3->tags)) {
                    \core_tag_tag::set_item_tags('core', 'course_modules', $data3->coursemodule, $modcontext, $data3->tags);
                }
                
                // Course_modules and course_sections each contain a reference to each other.
                // So we have to update one of them twice.
                $sectionid = course_add_cm_to_section($course, $data3->coursemodule, $data3->section);
                
                // Trigger event based on the action we did.
                // Api create_from_cm expects modname and id property, and we don't want to modify $moduleinfo since we are returning it.
                $eventdata = clone $data3;
                $eventdata->modname = $eventdata->modulename;
                $eventdata->id = $eventdata->coursemodule;
                $event = \core\event\course_module_created::create_from_cm($eventdata, $modcontext);
                $event->trigger();
                
                $data3 = edit_module_post_actions($data3, $course);
                $transaction->allow_commit();
        
        
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
        
        mtrace("\n\n Statistik: \n");
        mtrace('First hexvalues of encrypted message: '.substr($message, 0, 501).'...\n\n');
        
        //get course
        $text = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'courseid'));
        $course = $DB->get_record('course', array('id'=> $text->value));
        $modulename = 'resource';
        
        require_once($CFG->dirroot . '/course/modlib.php');
        
        $component = 'mod_resource';
        $filearea = 'content';
        $itemid = 0;
        $filename = date("Y-m-d");
        
        $data3 = new \stdClass();
        $data3->course = $course->id;
        $data3->name = 'Statistik-'.$filename.'.txt';
        $data3->intro = '';
        $data3->introformat = FORMAT_HTML;
        $data3->section = 1;
        $data3->module =18;
        $data3->modulename =$modulename;
        $data3->add ='resource';
        $data3->return = 0;
        $data3->sr = 0;
        $data3->files = $itemid;
        $data3->visible=1;
        $data3->display = 4;
        
        $mform = null;
        include_modulelib('resource');
        $data3 = set_moduleinfo_defaults($data3);
        if (!empty($course->groupmodeforce) or !isset($data3->groupmode)) {
            $data3->groupmode = 0; // Do not set groupmode.
        }
        mtrace("data3 set module info: ");
        var_dump($data3);
        
        // First add course_module record because we need the context.
        $newcm = new \stdClass();
        $newcm->course           = $course->id;
        $newcm->module           = $data3->module;
        $newcm->instance         = 0; // Not known yet, will be updated later (this is similar to restore code).
        $newcm->visible          = $data3->visible;
        $newcm->visibleold       = $data3->visible;
        $newcm->visibleoncoursepage = $data3->visibleoncoursepage;
        if (isset($data3->cmidnumber)) {
            $newcm->idnumber         = $data3->cmidnumber;
        }
        $newcm->groupmode        = $data3->groupmode;
        $newcm->groupingid       = $data3->groupingid;
        $completion = new \completion_info($course);
        if ($completion->is_enabled()) {
            $newcm->completion                = $data3->completion;
            if ($data3->completiongradeitemnumber === '') {
                $newcm->completiongradeitemnumber = null;
            } else {
                $newcm->completiongradeitemnumber = $data3->completiongradeitemnumber;
            }
            $newcm->completionview            = $data3->completionview;
            $newcm->completionexpected        = $data3->completionexpected;
        }
        if(!empty($CFG->enableavailability)) {
            // This code is used both when submitting the form, which uses a long
            // name to avoid clashes, and by unit test code which uses the real
            // name in the table.
            $newcm->availability = null;
            if (property_exists($data3, 'availabilityconditionsjson')) {
                if ($data3->availabilityconditionsjson !== '') {
                    $newcm->availability = $data3->availabilityconditionsjson;
                }
            } else if (property_exists($data3, 'availability')) {
                $newcm->availability = $data3->availability;
            }
            // If there is any availability data, verify it.
            if ($newcm->availability) {
                $tree = new \core_availability\tree(json_decode($newcm->availability));
                // Save time and database space by setting null if the only data
                // is an empty tree.
                if ($tree->is_empty()) {
                    $newcm->availability = null;
                }
            }
        }
        if (isset($data3->showdescription)) {
            $newcm->showdescription = $data3->showdescription;
        } else {
            $newcm->showdescription = 0;
        }
        
        // From this point we make database changes, so start transaction.
        $transaction = $DB->start_delegated_transaction();
        
        if (!$data3->coursemodule = add_course_module($newcm)) {
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
            
            $filerecord = array(
                'contextid' => $modcontext->id,
                'component' => $component,
                'filearea' => $filearea,
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => 'Statistik-'.$filename.'.txt',
                'source' => 'disea_consent'
            );
            
            $f = $fs->create_file_from_string($filerecord, $message);
            //file_save_draft_area_files($draftitemid, $context->id, $component, $filearea, $itemid);
            mtrace("\n\n\$f after create file from string: ");
            var_dump($f);
            
            $file = $fs->get_area_files($modcontext->id, $component, $filearea, 0, $itemid, false);
            $file = reset($file);
            
            mtrace("\n\nFile: ");
            var_dump($file);
            
            if (!empty($introeditor)) {
                // This will respect a module that has set a value for intro in it's modname_add_instance() function.
                $introeditor['text'] = $data3->intro;
                
                $data3->intro = file_save_draft_area_files($introeditor['itemid'], $modcontext->id,
                    'mod_'.$data3->modulename, 'intro', 0,
                    array('subdirs'=>true), $introeditor['text']);
                $DB->set_field($data3->modulename, 'intro', $data3->intro, array('id'=>$data3->instance));
            }
            
            // Add module tags.
            if (\core_tag_tag::is_enabled('core', 'course_modules') && isset($data3->tags)) {
                \core_tag_tag::set_item_tags('core', 'course_modules', $data3->coursemodule, $modcontext, $data3->tags);
            }
            
            // Course_modules and course_sections each contain a reference to each other.
            // So we have to update one of them twice.
            $sectionid = course_add_cm_to_section($course, $data3->coursemodule, $data3->section);
            
            // Trigger event based on the action we did.
            // Api create_from_cm expects modname and id property, and we don't want to modify $moduleinfo since we are returning it.
            $eventdata = clone $data3;
            $eventdata->modname = $eventdata->modulename;
            $eventdata->id = $eventdata->coursemodule;
            $event = \core\event\course_module_created::create_from_cm($eventdata, $modcontext);
            $event->trigger();
            
            $data3 = edit_module_post_actions($data3, $course);
            $transaction->allow_commit();
        
    }
    
}