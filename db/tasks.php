<?php

/**
 * Version details.
 *
 * {log_task} schedule definition
 *
 * @package    my_consent_block
 * @author	   Sven
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$tasks = array(
    array(
        'classname' => 'block_my_consent_block\task\log_task',
        'blocking'  => 0,
        'minute'    => '1',
        'hour'      => '2',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '1',
        'disabled'  => 0,
    ),
    array(
        'classname' => 'block_my_consent_block\task\decline_task',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '2',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '1',
        'disabled'  => 0,
    ),
);