<?php 
global $CFG;
require_once($CFG->dirroot.'/local/projectgroup/context.php');


/**
 * This defines the capabilities used for the project groups. 
 * Only edit is used currently for now and is per default assigned to administrative personel.
 * 
 */


$capabilities = array(
    'local/projectgroup:edit' => array(
        'riskbitmask'  => RISK_SPAM,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_PROJECTGROUP,
        'archetypes'   => array(
            'teacher'        => CAP_ALLOW,
            'manager'         => CAP_ALLOW
        )
    ),
    'local/projectgroup:use' => array(
        'riskbitmask'  => RISK_PERSONAL | RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_PROJECTGROUP,
        'archetypes'   => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    )
    , 
    'local/projectgroup:view' => array(
        'riskbitmask'  => RISK_PERSONAL,
        'captype'      => 'read',
        'contextlevel' => CONTEXT_PROJECTGROUP,
        'archetypes'   => array(
            'student'          => CAP_ALLOW,
            'guest'            => CAP_ALLOW,
            'teacher'          => CAP_ALLOW,
            'editingteacher'   => CAP_ALLOW,
            'manager'          => CAP_ALLOW
        )
    )
 
    
);

 