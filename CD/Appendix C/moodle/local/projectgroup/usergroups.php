<?php
/**
 * This page display all the project groups the user is a member of. 
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 * @package mymmoodle
 */
require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot.'/lib/blocklib.php');
require_once($CFG->dirroot.'/admin/tool/projectgroup/lib.php');

$id         = optional_param('id', $USER->id, PARAM_INT);


$PAGE->set_url('/local/projectgroup/usergroups.php', array('id'=>$id));
$sitecontext = get_context_instance(CONTEXT_USER, $USER->id);
$PAGE->set_context($sitecontext);

require_login();

$groups = get_groups_of_user($id);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('myprojectgroup', 'local_projectgroup'));

echo '<table class="generalbox editcourse boxaligncenter"><tr class="header">';
    echo '<th class="header" scope="col">'.get_string('shortname', "tool_projectgroup").'</th>';
    echo '<th class="header" scope="col">'.get_string('fullname', "tool_projectgroup").'</th>';
    echo '</tr>';

if(sizeof($groups)){

    foreach($groups as $groupId){
        $projectgroup = get_projectgroup($groupId);
        //echo $groupObj->shortname . '<br/>';
        
        echo '<tr>';
        echo '<td><a href='. new moodle_url('/local/projectgroup/index.php',array('id'=>$projectgroup->id, 'sesskey'=>sesskey())).">" . $projectgroup ->shortname . '</a></td>';
        echo '<td width="70%" style="max-width: 20px; overflow-x:hidden;">'. $projectgroup->fullname . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}else{
    echo html_writer::tag('h2',get_string('nogroups','local_projectgroup'),array('class'=>'main'));
}
echo $OUTPUT->footer();