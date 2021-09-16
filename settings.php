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
 * Strings for component 'block_html', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   block_my_consent_block
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
    
   
if($ADMIN->fulltree) {
    //Consent
    $settings->add(new admin_setting_configtextarea('block_my_consent_block/consent_text',
        get_string('config_consent_text', 'block_my_consent_block'),
        get_string('config_consent_description', 'block_my_consent_block'),
        '', PARAM_RAW,80));
    //Couseid
    $settings->add(new admin_setting_configtext('block_my_consent_block/courseid',
        get_string('config_title', 'block_my_consent_block'),
        get_string('config_text', 'block_my_consent_block'),
        '', PARAM_INT));
    //public key
    $settings->add(new admin_setting_configtextarea('block_my_consent_block/pub_key',
        get_string('config_key_title', 'block_my_consent_block'),
        get_string('config_key_text', 'block_my_consent_block'),
        '', PARAM_RAW,80));
}




// if($hassiteconfig) {
//     $ADMIN->add('blocks', new admin_category('Disea_consent_settings', 'block_my_consent_block'));
//     $settingspage = new admin_settingpage('manage__disea_consent', 'disea_consent');
    
//     if($ADMIN->fulltree) {
//         $settingspage->add(new admin_setting_configcheckbox('block_my_consent_block/showcourses',
//             get_string('showcourses', 'block_my_consent_block'),
//             get_string('showcoursesdesc', 'block_my_consent_block'),
//             0));
//         $settingspage->add(new admin_setting_configtext('block_my_consent_block/showcourses', 'Erster Hinweistext', 'Zweiter Hinweistext',
//             'noreply@localhost.com', PARAM_EMAIL));
//     }
//     $ADMIN->add('localplugins', $settingspage);
// }


