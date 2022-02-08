<?php
    $capabilities = array(
 
     'block/my_consent_block:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        ),
 
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ), 
 
    'block/my_consent_block:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
 
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

            
        'block/my_consent_block:download' => array(
            'riskbitmask' => RISK_SPAM | RISK_XSS,
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'researcher' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'moodle/site:manageblocks'
        ),

);