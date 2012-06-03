<?php
/**
 * This page is used to delete project groups.
 *
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
require_once('../../../config.php');
require_once('lib.php');

require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));

$id    = optional_param('id', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash

$sitecontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($sitecontext);
$PAGE->set_url('/admin/tool/projectgroup/delete.php');
$returnurl = 'delete.php';

$heading = get_string('deletegroup','tool_projectgroup');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);


if ($id and confirm_sesskey()) {             
        
        
        $pgroup = get_projectgroup($id);

      
        if ($confirm != md5($id)) {
            echo $OUTPUT->header();
            $fullname = $pgroup->fullname;
            echo $OUTPUT->heading($heading);
            $optionsyes = array('id'=>$id, 'confirm'=>md5($id), 'sesskey'=>sesskey());
            echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"), new moodle_url('delete.php', $optionsyes), 'delete.php');
            echo $OUTPUT->footer();
            die;
        } else if (data_submitted()) {
            try {
                delete_projectgroup($pgroup->id);
                session_gc(); // remove stale sessions
                redirect('index.php', '' , 2);
                
            } catch (moodle_exception $e){
                echo $OUTPUT->header();
                echo $OUTPUT->notification(get_string('deletednot','tool_projectgroup'), 'notifyproblem');
        
               
            }

        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('deletednot','tool_projectgroup'),'notifyproblem');
            
        }
} else {
     session_gc(); 
    redirect('index.php', '' , 2);
            
}


     
?> 
        