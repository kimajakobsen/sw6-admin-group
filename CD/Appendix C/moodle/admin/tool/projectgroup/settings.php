<?php
/**
 * Adds the project group administratoin links to the moodle administration toolbar
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */ 
$ADMIN->add('root', new admin_category('projectgroup', 'Project groups'));
$ADMIN->add('projectgroup', new admin_externalpage('toolprojectgrouplist', 'List of Project Groups', $CFG->wwwroot.'/'.$CFG->admin.'/tool/projectgroup/index.php'));
$ADMIN->add('projectgroup', new admin_externalpage('toolprojectgroupaddnew', 'Add New Project Group', $CFG->wwwroot.'/'.$CFG->admin.'/tool/projectgroup/edit.php?clearsession=1'));

