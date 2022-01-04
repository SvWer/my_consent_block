<?php

namespace block_my_consent_block\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements 
    \core_privacy\local\metadata\provider, 
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {
    
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'disea_consent', 
            [
                'userid' => 'privacy:metadata:disea_consent:userid',
                'courseid' => 'privacy:metadata:disea_consent:courseid',
                'choice' => 'privacy:metadata:disea_consent:choice',
            ],
            'privacy:metadata:disea_consent'
        );
        return $collection;
    }
    
    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist)
    {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_BLOCK) {
            return;
        }
        
        $params = [
            'blockname' => 'my_consent_block',
            'instanceid' => $context->instanceid,
        ];
        
        $sql = "SELECT dc.userid
                FROM {disea_consent} dc
                JOIN (
                    SELECT ctx.id, ctx.contextlevel, ctx.instanceid
                    FROM {context} ctx
                    JOIN {block_instances} bi
                    ON bi.parentcontextid = ctx.id
                    WHERE bi.blockname = :blockname
                    AND bi.id = :instanceid) ct
                ON dc.courseid = ct.instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
    }
    
     /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        
        $sql = "SELECT distinct ctx.id FROM {context} ctx
                JOIN (SELECT bi1.id, bi1.blockname, bi1.parentcontextid, ctx1.instanceid as course FROM {block_instances} bi1
	               JOIN {context} ctx1 ON bi1.parentcontextid = ctx1.id
                   JOIN {disea_consent} dc ON ctx1.instanceid = dc.courseid
                   WHERE bi1.blockname = :blockname 
                   AND dc.userid = :userid) t 
                ON ctx.instanceid = t.id
                WHERE ctx.contextlevel = :contextlevel";
        $params = [
            'userid'    => $userid,
            'contextlevel' => CONTEXT_BLOCK,
            'blockname' => 'my_consent_block'
        ];
        
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    } 
    
    public static function delete_data_for_users(approved_userlist $userlist)
    {
        global $DB;
        $context = $userlist->get_context();
        $sql = "SELECT dc.courseid
                 FROM mdl_block_instances bi
                 JOIN mdl_context ctx ON ctx.id = bi.parentcontextid
                 JOIN mdl_disea_consent dc ON ctx.instanceid = dc.courseid
                 WHERE bi.id = ".$context->instanceid;
        $dc = array_values($DB->get_records_sql($sql));
        
        $userids = $userlist->get_userids();
        foreach ($userids as $user) {
            $DB->delete_records('disea_consent',['courseid'=> $dc, 'userid'=>$user]);
        }
    }
    
    public static function delete_data_for_all_users_in_context(\context $context)
    {
        global $DB;
        
        if($context->contextlevel != CONTEXT_BLOCK)
        {
            return;
        }
         $sql = "SELECT dc.id
                 FROM mdl_block_instances bi
                 JOIN mdl_context ctx ON ctx.id = bi.parentcontextid
                 JOIN mdl_disea_consent dc ON ctx.instanceid = dc.courseid
                 WHERE bi.id = ".$context->instanceid;
         $dc = $DB->get_records_sql($sql);
         if(!$dc) {
             return;
         }
         foreach ($dc as $d) {
             $DB->delete_records('disea_consent', ['id' => $d]);
         }
         
    }

    public static function export_user_data(approved_contextlist $contextlist)
    {
        global $CFG, $DB;
        if (empty($contextlist)) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        
        $instanceids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_BLOCK) {
                $carry[] = $context;
            }
            return $carry;
        }, []);
        if (empty($instanceids)) {
            return;
        }
        //Für jeden Kontext (muss ich holen)
        foreach ($instanceids as $iids) {
            //Für den User Daten holen
            $sql = "SELECT dc.*
                 FROM mdl_block_instances bi
                 JOIN mdl_context ctx ON ctx.id = bi.parentcontextid
                 JOIN mdl_disea_consent dc ON ctx.instanceid = dc.courseid
                 WHERE dc.userid = ".$userid." AND bi.id = ".$iids->instanceid;
            $dc = array_values($DB->get_records_sql($sql));
            //Objekt draus machen
            $data = (Object) [
                'userid' => $userid,
                'courseid' => $dc[0]->courseid,
                'choice' => $dc[0]->choice,
                'timecreated' => $dc[0]->timecreated,
                'timemodified' => $dc[0]->timemodified
            ];
            
            //writer verwenden
            writer::with_context($iids)->export_data([get_string('privacy:data', 'block_my_consent_block')], $data);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist)
    {
        global $DB;
        if(empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach($contextlist->get_contexts() as $context) {
            $sql = "SELECT dc.courseid
                 FROM mdl_block_instances bi
                 JOIN mdl_context ctx ON ctx.id = bi.parentcontextid
                 JOIN mdl_disea_consent dc ON ctx.instanceid = dc.courseid
                 WHERE bi.id = ".$context->instanceid;
            $dc = array_values($DB->get_records_sql($sql));
            var_dump("dc: ");
            var_dump($dc[0]->courseid);
            $DB->delete_records('disea_consent', ['courseid'=> $dc[0]->courseid,'userid'=>$userid]);
        }
        
    }

}