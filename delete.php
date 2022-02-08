<?php
require_once('../../config.php');
global $CFG;




require_login();

$context = context_system::instance();
if (has_capability('block/my_consent_block:download', $context)) {
    $coursecontextid= optional_param('context', 0, PARAM_INT);
    $irgendeine_id = optional_param('id', 0, PARAM_INT);
    $fname = optional_param('fname', 0, PARAM_TEXT);
    $courseid = optional_param('courseid',NULL, PARAM_INT);
    
    $fs = get_file_storage();
    
    $storedfile = $fs->get_file($coursecontextid,
        'my_consent_block',
        'disea',
        $irgendeine_id,
        '/',
        $fname);
    if($storedfile) {
        $storedfile->delete();
    }
    redirect($CFG->wwwroot.'/blocks/my_consent_block/list_files.php?id='.$courseid);
}
    