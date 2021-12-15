<?php
require_once('../../config.php');


require_login();

// if (!has_capability(...)) { // Hier müsst ihr eure eigene neu erfundene Capability verwenden
//     // TODO: Error
// }

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
    