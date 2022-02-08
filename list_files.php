<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'block_my_consent_block'
 *
 * @package   block_my_consent_block
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $CFG, $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/blocks/my_consent_block/list_files.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('pluginname', 'block_my_consent_block'));

$courseid = optional_param('id',NULL, PARAM_INT);
$courseurl = $CFG->wwwroot.'/course/view.php?id='.$courseid;

echo $OUTPUT->header();
echo "<h2>LogDaten:</h2>";
echo '<a class="btn btn-primary" href="'.$courseurl.'">'.get_string('back', 'block_my_consent_block').'</a><br><br>';
$context = context_system::instance();

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'my_consent_block', 'disea');

foreach ($files as $file) {
    $filename = $file->get_filename();
    if($filename === "."){
        //don't print
    } else {
        $url = $CFG->wwwroot.'/blocks/my_consent_block/download.php?context='.$context->id.'&id='.$file->get_itemid().'&fname='.$filename.'&courseid='.$courseid;
        $del_url = $CFG->wwwroot.'/blocks/my_consent_block/delete.php?context='.$context->id.'&id='.$file->get_itemid().'&fname='.$filename.'&courseid='.$courseid;
        echo $filename;
        echo '  <a class="btn btn-primary" href="'.$url.'">'.get_string('download', 'block_my_consent_block').'</a>';
        echo '  <a class="btn btn-primary" href="'.$del_url.'">'.get_string('delete', 'block_my_consent_block').'</a><br>';
    }
}
echo '<a class="btn btn-primary" href="'.$courseurl.'">'.get_string('back', 'block_my_consent_block').'</a><br><br>';
echo $OUTPUT->footer();