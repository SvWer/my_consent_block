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
 * Strings for component 'block_html'
 *
 * @package   block_my_consent_block
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['block_my_consent_block:addinstance'] = 'Add a new consent block';
$string['block_my_consent_block:myaddinstance'] = 'Add a new consent block to Dashboard';
$string['block_my_consent_block'] = 'consent block';
$string['pluginname'] = 'DiSEA Consent block';

$string['config_title'] = 'Course for Logdata';
$string['config_text'] = 'Please insert the Course ID of the course in which the data should be stored.';

$string['config_key_title'] = 'Public key for encrypting the log data';
$string['config_key_text'] = 'Please insert the public key.';

$string['config_consent_text'] =  'Your Consent';
$string['config_consent_description'] = 'Please enter your consent in this field as html formatted text.';

$string['config_counter_title'] = 'Counter for display of consent';
$string['config_counter_text'] = 'This counter can be used to control when the consent should be shown again to everyone.';

$string['agree'] = '<strong>Ich willige ein</strong>, dass meine Moodle Logdaten, wie auch die Pr&uuml;fungsdaten an das DiSEA-Projekt weitergegeben, gespeichert und zu Forschungszwecken genutzt werden.';
$string['disagree'] = '<strong>Ich willige nicht ein</strong>, dass meine Moodle Logdaten, wie auch die Pr&uuml;fungsdaten an das DiSEA-Projekt weitergegeben, gespeichert und zu Forschungszwecken genutzt werden.';
$string['no_choice'] = 'Please choose one the the answers!';

$string['database_insert'] = 'Successfully added to database';
$string['database_update'] = 'Database successfully updated';

$string['edit'] = 'Edit consent';

$string['choice_no'] = 'You declined the consent';
$string['choice_yes'] = 'You accepted the consent';

$string['log_task_name'] = 'Disea Log Task';
$string['decline_task_name'] = 'Disea decline Task';


$string['messageprovider'] = 'Disea Message Provider';
$string['messageprovider:logdata_disea'] = 'Disea Message Provider';

$string['download'] = 'Download';
$string['back'] = 'Back';
$string['delete'] = 'Delete';

//Privacy API
$string['privacy:metadata:disea_consent'] = 'Information about the users choice of consent within different courses about the usage of his/her data for scientific research';
$string['privacy:metadata:disea_consent:userid'] = 'The ID of the user.';
$string['privacy:metadata:disea_consent:courseid'] = 'The ID of the course of the user';
$string['privacy:metadata:disea_consent:choice'] = 'The users choice for the disea consent';

$string['privacy:data'] = 'Data of user for DiSEA consent';
