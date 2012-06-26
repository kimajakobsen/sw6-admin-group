<?php
/**
 * The page udes for editing project groups
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */ 
require_once('../../../config.php');
require_once('lib.php');
require_once('edit_form.php');
require_once('projectgroup_user_filtering.php');

require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));

$id         = optional_param('id', '', PARAM_INT);
$cancel     = optional_param('cancel',0,PARAM_ALPHANUM);
$clearsession           =  optional_param('clearsession',0,PARAM_INT);



if($clearsession){
   $SESSION->bulk_users= array();
    $SESSION->user_filtering = array(); 
    session_gc(); 
}

if($cancel){
    $SESSION->bulk_users= array();
    session_gc();
    redirect('index.php');
}




$PAGE->set_pagelayout('admin');
$PAGE->set_url('/admin/tool/projectgroup/edit.php');
$sitecontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($sitecontext);


require_login();



if(!empty($id)) 
{
    $projectgroup = get_projectgroup($id);
    $heading =  $projectgroup->shortname . ': ' . get_string('edit');

    $PAGE->url->param('id',$id);
    if($clearsession && empty($SESSION->bulk_users))
    {
        foreach($projectgroup->members as $m)
        {
            $SESSION->bulk_users[$m->user] = $m->user;           
        }    
    }
} 
else //add new projectgroup
{
    $heading =  get_string('createnewprojectgroup','tool_projectgroup');
	$projectgroup = null;
}


$PAGE->set_title($heading);
$PAGE->set_heading($heading);



// create the user filter form
$ufiltering = new projectgroup_user_filtering();



//get the data from the from
$data = array_merge(get_selection_data($ufiltering), array('projectgroup'=>$projectgroup));
$data['fields'] = $ufiltering->get_fields();

$editform = new projectgroup_edit_form(NULL,$data );

if ($data = $editform->get_data()) {

    if (!empty($data->addall)) //If add button is pressed
    {
        add_selection_all($ufiltering);
    } 
    else if (!empty($data->addsel)) //If add selected is pressed
    {
        if (!empty($data->ausers)) 
        {
            if (in_array(0, $data->ausers)) 
            {
                add_selection_all($ufiltering);
            } 
            else 
            {
                foreach($data->ausers as $userid) 
                {
                    if ($userid == -1) 
                    {
                        continue;
                    }
                    if (!isset($SESSION->bulk_users[$userid])) 
                    {
                        $SESSION->bulk_users[$userid] = $userid;
                    } 
                }
            }
        }
    } 
    else if (!empty($data->removeall)) // remove all
    {
        $SESSION->bulk_users = array();
    } 
    else if (!empty($data->removesel)) //remove selected
    {
        if (!empty($data->susers)) 
        {
            if (in_array(0, $data->susers)) 
            {
                $SESSION->bulk_users= array();
            } 
            else 
            {
                foreach($data->susers as $userid) 
                {
                    if ($userid == -1) 
                    {
                        continue;
                    }
                    unset($SESSION->bulk_users[$userid]);
                }
            }
        }
    }
    
    else if(!empty($data->submitbutton)) //submitbutton is pressed
    {
        if(!empty($SESSION->bulk_users))
        {
            $data->members = $SESSION->bulk_users;
        }       
        
        if(isset($data->advisors))
        {
            foreach($data->members as $key => $member){
                $mId = $member;
                $member = new stdClass();
                $member->user = $mId;
                if(in_array($mId, $data->advisors)){
                    $member->role = 1;
                }else{
                    $member->role = 0;
                }
                $data->members[$key] = $member;
            }
        }
        $projectgroupID = save_or_update_projectgroup($data);
   
        $url = new moodle_url($CFG->wwwroot.'/admin/tool/projectgroup/users.php',array('id'=>$projectgroupID));
        $SESSION->bulk_users= array(); //empty bulk.
        session_gc(); 
        redirect($url);
    } 
    else if (!empty($data->addfilter)) 
    {
   
        foreach($ufiltering->get_fields() as $fname=>$field) 
        {
            $datafilt = $field->check_data($data);
            if ($datafilt === false) 
            {
                continue; // nothing new
            }
            if (!array_key_exists($fname, $SESSION->user_filtering)) 
            {
                $SESSION->user_filtering[$fname] = array();
            }
            $SESSION->user_filtering[$fname][] = $datafilt;
        }
        
    }
    else if (!empty($data->removeallfilters)) 
    {
        $SESSION->user_filtering = array();  
    } 
    else if (!empty($data->removeselectedfilters) and !empty($data->filter)) 
    {
        foreach($data->filter as $fname=>$instances) 
        {
            foreach ($instances as $i=>$val) {
                
                if (empty($val)) 
                {
                    continue;
                }
                unset($SESSION->user_filtering[$fname][$i]);
            }
            if (empty($SESSION->user_filtering[$fname])) 
            {
                unset($SESSION->user_filtering[$fname]);
            }
        }
    } else {
        echo 'none';
    }
    
    $data = array_merge(get_selection_data($ufiltering), array('projectgroup'=>$projectgroup,'returnto'=>$returnto));
    $data['fields'] = $ufiltering->get_fields();
    $editform = new projectgroup_edit_form(NULL,$data );

    unset($_POST); //ask Rasmus, this is good practice. ->kim
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
$editform->display();
echo $OUTPUT->footer();
