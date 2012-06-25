<?php
/**
 * This pages shows a list of members in the project group. 
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */ 
require_once('../../../config.php');
require_once($CFG->dirroot."/local/projectgroup/lib.php");
require_once("lib.php");
require_once($CFG->libdir .'/tablelib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));
$id      = required_param('id', PARAM_INT); // project group id

$projectgroup = get_projectgroup($id);
$nameMaxLength = 80;
if(strlen($projectgroup->fullname) > $nameMaxLength) {
    $projectgroup->fullname = substr($projectgroup->fullname, 0,$nameMaxLength-3) . '...';
}



require_login(); 
$PAGE->set_pagelayout('admin');
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/projectgroup/users.php');

$PAGE->set_title($projectgroup->fullname);
$PAGE->set_heading($PAGE->title);

echo $OUTPUT->header();
echo $OUTPUT->heading($projectgroup->shortname . ': ' . get_string('groupoverview','tool_projectgroup'));


$editgroupurl = '<a href="'.new moodle_url('/admin/tool/projectgroup/edit.php',array('id'=>$id,'clearsession'=>1)).'">'.get_string('editprojectgroup', "tool_projectgroup").'</a>';
$viewgroupurl = '<a href="'.new moodle_url('/local/projectgroup/index.php',array('id'=>$id)).'">'.get_string('viewprojectgroup', "tool_projectgroup").'</a>';

echo $OUTPUT->heading($editgroupurl);
echo $OUTPUT->heading($viewgroupurl);


$table = new flexible_table($projectgroup->shortname.'-members');


$table->define_columns(array('fullname','email','role','added','actions'));
$table->define_headers(array(get_string('fullname','tool_projectgroup'),
                             get_string('email','tool_projectgroup'),
                             get_string('role','tool_projectgroup'),
                             get_string('addedtogroup','tool_projectgroup'),
                             get_string('actions','tool_projectgroup'),));

$table->define_baseurl('/admin/tool/projectgroup/users.php');

$table->set_attribute('class', 'generaltable generalbox boxaligncenter');

$table->setup();

//$table->pagesize(5000, 1000);
$projectgroup->members = array_values($projectgroup->members);
$memberIds = array_map(function ($member) {return $member->user;}, $projectgroup->members);
$members = user_get_users_by_id($memberIds);

$mergedArray = array_map(null, $members, $projectgroup->members);

function sort_by_role_then_name($a, $b){
    $ar = $a[1]->role;
    $br = $b[1]->role;
    if ($ar == $br) {
        $al = strtolower($a[0]->firstname);
        $bl = strtolower($b[0]->firstname);
        return ($al > $bl) ? +1 : -1;
    }
    return ($ar < $br) ? +1 : -1;
    
}

usort($mergedArray, "sort_by_role_then_name");

if(sizeof($mergedArray)) 
{
    $i = 0;
    foreach ($mergedArray  as  $member) {
        $fullname = $member[0]->firstname . ' ' . $member[0]->lastname;
        $edit = '<a title="Remove '.$fullname.'" href="'.new moodle_url('/admin/tool/projectgroup/removemember.php', array('user'=>$member[1]->user,'group'=>$projectgroup->id, 'sesskey'=>sesskey())).'"><img'.
                     ' src="'.$OUTPUT->pix_url('t/delete') . '" class="iconsmall" alt="Remove" /></a>';
        if($member[1]->role == 0){
            $roleName = get_string('student','tool_projectgroup');
        }elseif($member[1]->role == 1){
            $roleName = get_string('advisor' ,'tool_projectgroup');
        }else{
            $roleName = '-';
        }
        
        
        $table->add_data(array($fullname, $member[0]->email, $roleName, userdate($member[1]->added),$edit));
        $i++;
    }
    
    
    echo '<div class="clear"></div>';
    $table->finish_output();
}
else 
{
    echo html_writer::tag('h2',get_string('noGroupMembers','tool_projectgroup'),array('class'=>'main'));
}
echo $OUTPUT->heading($editgroupurl);
echo $OUTPUT->action_link(new moodle_url('/admin/tool/projectgroup/index.php'),'Back to index');
echo $OUTPUT->footer();















