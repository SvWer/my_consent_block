<?php
require_once('../../config.php');


require_login();

$context = context_system::instance();
if (has_capability('block/my_consent_block:download', $context)) {
    $coursecontextid= optional_param('context', 0, PARAM_INT);
    $irgendeine_id = optional_param('id', 0, PARAM_INT);
    $fname = optional_param('fname', 0, PARAM_TEXT);
    
    $fs = get_file_storage();
    
    $storedfile = $fs->get_file($coursecontextid,
        'my_consent_block',
        'disea',
        $irgendeine_id,
        '/',
        $fname);
    send_stored_file($storedfile, null, 0, true);
   
}
    