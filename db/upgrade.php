<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_block_my_consent_block_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    if($oldversion < 2020061511) {
        $table = new xmldb_table('disea_consent_all');
        //Define fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('choice', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        // Add primary key
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if(!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_block_savepoint(true, 2020061511, 'my_consent_block');
    }
}