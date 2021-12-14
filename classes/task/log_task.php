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
        global $CFG, $DB, $OUTPUT;
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
        $text = $DB->get_record('config_plugins', array('plugin' => 'block_my_consent_block', 'name' => 'courseid'));
        $course = $DB->get_record('course', array('id'=> $text->value));
        
        $forum_ids = $DB->get_records('forum', array('course'=>$course->id));
        
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/forum/externallib.php');
        
        mtrace("Forum gefunden?");
        var_dump(count($forum_ids));
        
        //If there is no Forum, create one
        if (count($forum_ids) < 1)
        {
            mtrace("No Forum, so create one");
            $forum = new \stdClass();
            $forum->course = $course->id;
            $forum->type = "general";
            $forum->intro = "Test Forum";
            $forum->name = "Meine Logdaten";
            $forum->timemodified = time();
            $forum->id = $DB->insert_record("forum", $forum);
            
            if (! $module = $DB->get_record("modules", array("name" => "forum"))) {
                echo $OUTPUT->notification("Could not find forum module!!");
                return false;
            }
            
            $mod = new \stdClass();
            $mod->course = $course->id;
            $mod->module = $module->id;
            $mod->instance = $forum->id;
            $mod->section = 2;
            if (! $mod->coursemodule = add_course_module($mod) ) {   // assumes course/lib.php is loaded
                echo $OUTPUT->notification("Could not add a new course module to the course '" . $course->id . "'");
                return false;
            }
            
            if (!$sectionid = course_add_cm_to_section ($course->id, $mod->coursemodule, 2, null) ) {   // assumes course/lib.php is loaded
                echo $OUTPUT->notification("Could not add the new course module to that section");
                return false;
            }
            
            $DB->set_field("course_modules", "section", $sectionid, array("id" => $mod->coursemodule));
            
            include_once("$CFG->dirroot/course/lib.php");
            rebuild_course_cache($course->id);
        }
        //Check if there is an discussion
        $forums = array_values($forum_ids);
        $discussion_ids = $DB->get_records('forum_discussions', array('forum'=>$forums[0]->id));
        
        mtrace("Check Forum for Discussions: ");
        var_dump(count($discussion_ids));
        
        //Creating a discussion
        list($course2, $cm) = get_course_and_cm_from_instance($forums[0], 'forum');
        $context = \context_module::instance($cm->id);
        $discussion = new \stdClass();
        $discussion->course = $course2->id;
        $discussion->forum = $forums[0]->id;
        $discussion->message = "Hallo, anbei befinden sich die Logdaten";
        $discussion->messageformat = FORMAT_HTML;
        $discussion->messagetrust = trusttext_trusted($context);
        $discussion->groupid = -1;
        $discussion->mailnow = 0;
        $discussion->subject = 'Log-'.$filename.'.txt';
        $discussion->name = 'Log-'.$filename.'.txt';
        $discussion->timestart = 0;
        $discussion->timeend = 0;
        $discussion->timelocked = 0;
        $discussion->pinned = FORUM_DISCUSSION_UNPINNED;
        $discussion->itemid = 1;
        $discussion->attachments = 1;
        
        mtrace("Discussion element: ");
        var_dump($discussion);
        
        //Creating a post for the discussion
        $timenow = isset($discussion->timenow) ? $discussion->timenow : time();
        $post = new \stdClass();
        $post->discussion    = 0;
        $post->parent        = 0;
        $post->privatereplyto = 0;
        $post->userid        = 2;
        $post->created       = $timenow;
        $post->modified      = $timenow;
        $post->mailed        = FORUM_MAILED_PENDING;
        $post->subject       = $discussion->name;
        $post->message       = $discussion->message;
        $post->messageformat = $discussion->messageformat;
        $post->messagetrust  = $discussion->messagetrust;
        $post->attachment    = $discussion->attachments;
        $post->forum         = $forums[0]->id;
        $post->course        = $course2->id;
        $post->mailnow       = $discussion->mailnow;
        
        mtrace("Post element: ");
        var_dump($post);
        
        \mod_forum\local\entities\post::add_message_counts($post);
        $post->id = $DB->insert_record("forum_posts", $post);
        
        if (!empty($cm->id) && !empty($discussion->itemid)) {
            $text = file_save_draft_area_files($discussion->itemid, $context->id, 'mod_forum', 'post', $post->id,
                \mod_forum_post_form::editor_options($context, null), $post->message);
            
            $DB->set_field('forum_posts', 'message', $text, array('id'=>$post->id));
        }
        
        //creation of file
        $file = new \stdClass;
        $file->contextid = $context->id;
        $file->component = 'mod_forum';
        $file->filearea  = 'attachment';
        $file->itemid    = $post->id;
        $file->filepath  = '/';
        $file->filename  = 'Log-'.$filename.'.txt';
        $file->source    = 'Log-'.$filename.'.txt';
        $fs = get_file_storage();
        $file = $fs->create_file_from_string($file, $message);
        
        mtrace("Created File: ");
        var_dump($file);
        
        //adding file and creation of discussion
        $discussion->firstpost    = $post->id;
        $discussion->timemodified = $timenow;
        $discussion->usermodified = $post->userid;
        $discussion->userid       = 2;
        $discussion->assessed     = 0;
        $post->discussion = $DB->insert_record("forum_discussions", $discussion);
        $DB->set_field("forum_posts", "discussion", $post->discussion, array("id"=>$post->id));
        if (!empty($cm->id)) {
            $r = forum_add_attachment($post, $forums[0], $cm, 1);
            forum_trigger_content_uploaded_event($post, $cm, 'forum_add_discussion');
        }
        $discussion->id = $post->discussion;
        mtrace("Updated discussion: ");
        var_dump($discussion);
        
        mtrace("Discussionid: ".$discussion->id);
        
        if($discussionid = $post->discussion)
        {
            $params = array(
                'context' => $context,
                'objectid' => $discussionid,
                'other' => array(
                    'forumid' => $forums[0]->id,
                )
            );
            
            $event = \mod_forum\event\discussion_created::create($params);
            $event->add_record_snapshot('forum_discussions', $discussion);
            $event->trigger();
        } else {
            throw new \moodle_exception('couldnotadd', 'forum');
        }
    }
    
}