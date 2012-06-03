<?php
/**
 * The index page for the administrative page and displays a list of project groups. 
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
require_once("../../../config.php");
require_once("lib.php");
require_once 'projectgroup_form.php';
require_once 'projectgroup_filtering.php';
  
require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));


$page = optional_param("page", 0, PARAM_INT);
$site = get_site();
$perpage = PROJECTGROUPS_PER_PAGE;

$systemcontext = get_context_instance(CONTEXT_SYSTEM);

$strprojectgroup = get_string('projectgroup', "tool_projectgroup");

$baseurl = '/admin/tool/projectgroup/index.php';

$PAGE->set_url($baseurl);
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');

$PAGE->set_title("$site->shortname: $strprojectgroup");
$PAGE->set_heading("$strprojectgroup");

//create the user filter form
$filtering = new projectgroup_filtering();



//get the data from the form
$data = get_projectgroup_selection_data($filtering);
$data['fields'] = $filtering->get_fields();
$editform = new projectgroup_form(NULL,$data);

if ($data = $editform->get_data()) 
{
    if (!empty($data->addfilter)) 
    {
        foreach($filtering->get_fields() as $fname=>$field) 
        {
            $datafilt = $field->check_data($data);
            if ($datafilt === false) 
            {
                continue; // nothing new
            }
            if (!array_key_exists($fname, $SESSION->projectgroup_filtering)) 
            {
                $SESSION->projectgroup_filtering[$fname] = array();
            }
            $SESSION->projectgroup_filtering[$fname][] = $datafilt;
        }
        
    }
    else if (!empty($data->removeallfilters)) 
    {
        $SESSION->projectgroup_filtering = array();  
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
                unset($SESSION->projectgroup_filtering[$fname][$i]);
            }
            if (empty($SESSION->projectgroup_filtering[$fname])) 
            {
                unset($SESSION->projectgroup_filtering[$fname]);
            }
        }
    } 
    $data =  get_projectgroup_selection_data($filtering);
    $data['fields'] = $filtering->get_fields();
    $editform = new projectgroup_form(NULL,$data );
}
$pgroups = get_projectgroups(array('offset'=> $page * $perpage, 'limit' => $perpage),$filtering);

echo $OUTPUT->header();
echo $OUTPUT->heading($strprojectgroup);

echo $OUTPUT->paging_bar(get_projectgroup_count($filtering), $page, $perpage, $baseurl);
$addnewgurl = '<a href="'.$CFG->wwwroot.'/admin/tool/projectgroup/edit.php?clearsession=1">'.get_string('addnewprojectgroup', "tool_projectgroup").'</a>';


$editform->display();
echo $OUTPUT->heading($addnewgurl);
if(sizeof($pgroups)) 
{
    echo '<table class="generalbox editcourse boxaligncenter"><tr class="header">';
    echo '<th class="header" scope="col">'.get_string('shortname', "tool_projectgroup").'</th>';
    echo '<th class="header" scope="col">'.get_string('fullname', "tool_projectgroup").'</th>';
    echo '<th class="header" scope="col">'.get_string('actions', "tool_projectgroup").'</th>';
    echo '</tr>';
    
    foreach($pgroups as $projectgroup){
        echo '<tr>';
        echo '<td><a href='. new moodle_url('/admin/tool/projectgroup/users.php',array('id'=>$projectgroup->id)).">" . $projectgroup ->shortname . '</a></td>';
        echo '<td width="70%" style="max-width: 20px; overflow-x:hidden;">'. $projectgroup->fullname . '</td>';
        echo '<td> <a title="'.get_string('editprojectgroup','tool_projectgroup').': '.$projectgroup->shortname.'" href="' . new moodle_url('edit.php',array('id'=>$projectgroup->id,'clearsession'=>1, 'sesskey'=>sesskey())).'"><img'.
                     ' src="'.$OUTPUT->pix_url('t/edit') . '" class="iconsmall" alt="'.get_string('editprojectgroup','tool_projectgroup').': '.$projectgroup->shortname.'" /></a>
                     <a title="'.get_string('deletegroup','tool_projectgroup').': '.$projectgroup->shortname.'" href="'.new moodle_url('/admin/tool/projectgroup/delete.php', array('id'=>$projectgroup->id, 'sesskey'=>sesskey())).'"><img'.
                     ' src="'.$OUTPUT->pix_url('t/delete') . '" class="iconsmall" alt="'.get_string('deletegroup','tool_projectgroup').': '.$projectgroup->shortname.'" /></a>
                      </td>';
        echo '</tr>';
    }
    
    echo '</table>';
}
else {
    echo html_writer::tag('h2',get_string('noProjectGroups','tool_projectgroup'),array('class'=>'main'));
}



echo $OUTPUT->heading($addnewgurl);
echo '<br />';
echo '<br />';
echo '<br />';

echo '<br />';
echo '<br />';
echo '<br />';


















echo $OUTPUT->footer();
