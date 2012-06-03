<?php
/**
 * Project group room. 
 * This page is the realisatoin of the virtual group room.
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 * @package mymmoodle
 */
require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot.'/lib/blocklib.php');


$id          = optional_param('id', '', PARAM_INT);
$edit        = optional_param('edit', -1, PARAM_BOOL);
$sesskey     = optional_param('sesskey', -1, PARAM_BASE64);


//$PAGE->set_pagelayout('course');
$PAGE->set_url('/local/projectgroup/index.php', array('id'=>$id));
$sitecontext = get_projectgroup_context_instance($id);

if($sitecontext)
{
    has_projectgroup_read_permission($sitecontext);
}
else 
{
    throw new coding_exception('Project group with id: '.$id.' does not exist or does not have a context associated with it.');
}

$PAGE->set_context($sitecontext);
$PAGE->blocks->add_region('content');


require_login();
if(!empty($id)) //edit existing course (load the data matching the id)
{
    try 
    {
        $projectgroup = get_projectgroup($id);
        $strprojectgroup = $projectgroup->fullname;
    }
    catch(moodle_exception $e)
    {
        $strprojectgroup = "Invalid group id: ".$id;
    }
} else {
    $strprojectgroup = "No Group";
}
$type = navigation_node::TYPE_CUSTOM;
$activenode = $PAGE->navigation->find($projectgroup->id, $type);
if(!$activenode)
{
    $myprojectgroup = $PAGE->navigation->find('myprojectgroup',$type);
    $children = $myprojectgroup->get_children_key_list();
    foreach ($children as $key => $value) {
        $inactivenode = $PAGE->navigation->find($value, $type);
        $inactivenode->make_inactive();
    }
    $myprojectgroup->add($projectgroup->shortname, new moodle_url('/local/projectgroup/index.php', 
        array('id'=>$projectgroup->id, 'sesskey'=>sesskey())),$type,null,$projectgroup->id);
}

$PAGE->set_title($strprojectgroup);
$PAGE->set_heading($strprojectgroup);

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('myprojectgroup','local_projectgroup'),new moodle_url('/local/projectgroup/usergroups.php',array('id'=>$USER->id)));

$PAGE->navbar->add($projectgroup->shortname, new moodle_url('/local/projectgroup/index.php',array('id'=>$id,'sesskey'=>sesskey())));



if (!isset($USER->editing)) {
    $USER->editing = 0;
}
if (has_projectgroup_write_permission($sitecontext)) {
    if (($edit == 1) and confirm_sesskey()) {
        $USER->editing = 1;
        // Redirect to site root if Editing is toggled on frontpage
     
    } else if (($edit == 0) and confirm_sesskey()) {
        $USER->editing = 0;

    }
} else {
    $USER->editing = 0;
}

if (has_projectgroup_write_permission($sitecontext)) {
  
    $buttons = $OUTPUT->edit_button(new moodle_url('/local/projectgroup/index.php', array('id' => $projectgroup->id)));
    $PAGE->set_button($buttons);
}

echo $OUTPUT->header();

echo $OUTPUT->heading($strprojectgroup);

echo $OUTPUT->blocks_for_region('content');

echo $OUTPUT->footer();

