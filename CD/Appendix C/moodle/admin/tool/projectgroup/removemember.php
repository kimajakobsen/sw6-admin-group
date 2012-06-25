<?php
/**
 * The page is used for removing members from groups. 
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */ 
require_once('../../../config.php');
require_once('lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));

$userId    = required_param('user', PARAM_INT);
$groupId    = required_param('group', PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash



$PAGE->set_pagelayout('admin');
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);

$heading = get_string('removeuser','tool_projectgroup');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$PAGE->set_url('/admin/tool/projectgroup/removemember.php');

$myurl = 'removemember.php';
$returnurl = 'users.php?id='.$groupId;

if ($userId and $groupId and confirm_sesskey()) {              // Delete a selected user, after confirmation

    $pgroup = get_projectgroup($groupId);
    $member = null;
    foreach ($pgroup->members as $value) {
        if($value->user == $userId) {
            $member = $value;
            break;
        }
    }
    if(is_null($member)) {
        session_gc(); // remove stale sessions
        echo $OUTPUT->header();
        echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $pgroup->fullname));
    }
    else 
    {
        $users  = array_values(user_get_users_by_id(array($userId)));
        $user   = $users[0];

        if ($confirm != md5($userId.' '.$groupId)) {
            echo $OUTPUT->header();
            $fullname = $user->firstname . ' ' . $user->lastname;
            $groupname = $pgroup->fullname;
            echo $OUTPUT->heading(get_string('removeuser', 'tool_projectgroup'));
            $optionsyes = array('user'=>$userId,'group'=>$groupId, 'confirm'=>md5($userId.' '.$groupId), 'sesskey'=>sesskey());
            echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname' from '$groupname'"), 
                new moodle_url($myurl, $optionsyes), $returnurl);
                
            echo $OUTPUT->footer();
            die;
        } else if (data_submitted()) {
            if (remove_projectgroup_members($groupId,array($userId))) {
                session_gc(); // remove stale sessions
                redirect($returnurl);
            } else {
                session_gc(); // remove stale sessions
                echo $OUTPUT->header();
                echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $pgroup->fullname));
            }
        }
    }
}
else 
{
	
     session_gc(); // remove stale sessions
    redirect('index.php', '' , 2);
}
        
?>