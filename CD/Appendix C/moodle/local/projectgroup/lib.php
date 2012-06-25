<?php
/**
 * The Library for main functionality
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 * @package mymmoodle
 */
global $CFG;
require_once $CFG->dirroot.'/lib/blocklib.php';
require_once $CFG->dirroot.'/local/projectgroup/projectgrouppage.php';
require_once $CFG->dirroot.'/local/projectgroup/context.php';

require_once $CFG->dirroot.'/local/projectgroup/lib_blackboard.php';
require_once $CFG->dirroot.'/local/projectgroup/lib_supervisor.php';
require_once $CFG->dirroot.'/local/projectgroup/lib_tasks.php';
require_once $CFG->dirroot.'/local/projectgroup/capabilityhelper.php';

/**
 * Creates the my projectgroup navigatoin bar. 
 * 
 * This method should not be called in user code. 
 * @param global_navigation navigation 
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 * @package mymmoodle
 */
 
function projectgroup_extends_navigation(global_navigation $navigation) 
{
    global $USER;
    $number_of_groups = 4;
    
    //Determines whether or not a user is supervisor for any groups, and adds the link if neccessary.
    if(get_groups_of_user($USER->id, 1))
    {
        $supervisorWallLink = $navigation->add(get_string('supervisor_for_any','local_projectgroup'), new moodle_url('/blocks/upload/viewFiles.php'));
    }
    
    $groups = get_groups_of_user($USER->id);
    if(sizeof($groups) > 0)
    {
        $type = navigation_node::TYPE_CUSTOM;
        $my_projectgroup_navigation = $navigation->add(get_string('myprojectgroup','local_projectgroup'),null,$type,null,'myprojectgroup');
        $count = 0;
        foreach ($groups as $group_id) 
        {
            $count++;
            if($count > $number_of_groups ){
                $my_projectgroup_navigation->add('Show More...', 
                    new moodle_url('/local/projectgroup/usergroups.php', 
                    array('id'=>$USER->id, 'sesskey'=>sesskey())),
                    $type,null,'show_more');
                break;
            }
            $group = get_projectgroup($group_id);
            $my_projectgroup_navigation->add($group->shortname, new moodle_url('/local/projectgroup/index.php', array('id'=>$group_id, 'sesskey'=>sesskey())),$type,null,$group->id);
            
        }
    }
}



 /**
  * Gets a projectgroup
  * @param int id of the projectgroup
  * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
  * @return stdClass an object with the group and its associative
  */
 function get_projectgroup($id)
 {
     global $DB;
     
     $projectgroup = $DB->get_record('projectgroup', array('id'=>$id));
     if(!is_object($projectgroup))
     {
         throw new moodle_exception("The projectgroup with id $id does not exist");
     }
     
     $projectgroup->members = $DB->get_records('projectgroup_members', array('projectgroup'=>$projectgroup->id));
     return $projectgroup;
 }
 
 
/**
 * Returns the members of a group
 * @param int groupID the id of a projectgroup
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 * @return array of member id's. It will be empty if there are no members in the group.
 */ 
function get_members_of_group($groupID)
{
    global $DB;
 
    if(is_string($groupID)) 
    {
        if(((int)$groupID) == 0 && trim($groupID) !== '0') {
           throw new coding_exception('Invalid type of id, expected integer got: ' . gettype($groupID));
        }
        $groupID = (int)$groupID;
    }
    
    if(!is_int($groupID))
    {
        throw new coding_exception('Invalid type of id, expected integer got: ' . gettype($groupID));
    }
 
    //if user does not exist
    get_projectgroup($groupID); //this will throw an error if it dose not exit
  
    $result = $DB->get_records('projectgroup_members',array('projectgroup'=>$groupID));
    
    $users = array();
    foreach ($result as $relationID => $relationArray) 
    {     
        $users[] = $relationArray->user;
    }
    
    return $users;
} 



/**
 * Gives the groups which a given user is a member of.
 * @param int id The ID of the user
 * @return array of group ids, it will be empty if the user do not have any.   
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 */
function get_groups_of_user($id,$role = null)
{
    global $DB;
 
    if(is_string($id)) 
    {
        if(((int)$id) == 0 && trim($id) !== '0') {
           throw new coding_exception('Invalid type of id, expected integer got: ' . gettype($id) . ' with value: "'.$id.'"');
        }
        $id = (int)$id;
    }
    
    if(!is_int($id))
    {
        throw new coding_exception('Invalid type of id, expected integer got: ' . gettype($id));
    }

 
    if(is_null($role))
    {
        $result = $DB->get_records('projectgroup_members',array('user'=>$id));
    }
    else
    {
        $result = $DB->get_records('projectgroup_members',array('user'=>$id,'role'=>$role));
    }
    
    $groups = array();
    if(is_array($result))
    {
        foreach ($result as $relationID => $relationArray) 
        {     
            $groups[] = $relationArray->projectgroup;
        }
    }
    
    return $groups;
}



/**
 * Adds the default blocks to the project group
 * @param projectgroup A project group in form of a stdClass with the property id
 * @return null
 */
function blocks_add_default_projectgroup_blocks($projectgroup) 
{
    global $CFG;
    if (!empty($CFG->defaultprojectgroupblocks)) 
    {
        $blocknames = blocks_parse_default_projectgroup_blocks_list($CFG->defaultprojectgroupblocks);

    } 
    else 
    {
        $formatconfig = $CFG->dirroot.'/local/projectgroup/config.php';
        $format = array(); // initialize array in external file
        if (is_readable($formatconfig)) 
        {
            include($formatconfig);
        }
        if (!empty($format['defaultprojectgroupblocks'])) 
        {
            $blocknames = blocks_parse_default_projectgroup_blocks_list($format['defaultprojectgroupblocks']);

        } 
        else if (!empty($CFG->defaultprojectgroupblocks))
        {
            $blocknames = blocks_parse_default_projectgroup_blocks_list($CFG->defaultprojectgroupblocks);

        }
        else 
        {
            throw new coding_exception('No default blocks for project groups, perhaps "$format[\'defaultprojectgroupblocks\']" is missing in local/projectgroup/config.php');
        }
    }

    $pagetypepattern = 'local-projectgroup-index';
    
    $page = new projectgroup_page();
    $page->set_projectgroup($projectgroup);
    
    $context = get_projectgroup_context_instance($projectgroup->id);
    blocks_delete_all_for_context($context->id);
    $page->blocks->add_blocks($blocknames, $pagetypepattern);
}


/**
 * Parses the default projectgroup blocks from a string. 
 * @param string blockstring A commasepareted list of blocks. 
 * @return array Associative array with block regions as keys, each entry is
 * an array of the blocks that should be in the region (given as key in the outer array)
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 */
function blocks_parse_default_projectgroup_blocks_list($blockstring)
{
    $blockregioncenter = 'content';
    $blocks = array();
    $bits = explode(':', $blockstring);
    if (!empty($bits)) {
        $leftbits=trim(array_shift($bits));
        if ($leftbits != '') {
            $blocks[BLOCK_POS_LEFT] = explode(',', $leftbits);
        }
    }
    if (!empty($bits)) {
        $middlebits=trim(array_shift($bits));
        if ($middlebits != '') {
            $blocks[$blockregioncenter] = explode(',', $middlebits);
        }
    }
    if (!empty($bits)) {
        $rightbits=trim(array_shift($bits));
        if ($rightbits != '') {
            $blocks[BLOCK_POS_RIGHT] = explode(',', $rightbits);
        }
    }
    return $blocks;
}

/**
 * Verify that the currently logged in user has read permission to the projectgroup with the context given as input
 * @param context context The context belonging to the project group to which read permissions are verified
 * @param capability_helper capability_helper For testing purpose only
 * @throws required_capability_exception if the currently logged in user is not allowed to read projectgroup with
 * the context given as input
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 */
function has_projectgroup_read_permission(context $context, capability_helper $capability_helper = null){
    global $USER;
    if(is_null($capability_helper))
    {
        $capability_helper = new capability_helper();
    }
    $userId = $USER->id;
    $groupId = $context->getProjectGroupId();
    
    $userGroups = $capability_helper->get_groups_of_user($userId);
    $parents = $context->get_parent_contexts();
    $topParent = array_shift($parents);
    
    
    $has_cap = $capability_helper->has_capability('local/projectgroup:edit', $topParent); // We check the edit capability to make sure admins can edit. 
    $is_member = in_array($groupId, $userGroups);
    
    if(!$has_cap && !$is_member){
        throw new required_capability_exception($context, 'local/projectgroup:view', 'nopermissions', '');
    }
}

/**
 * Verify that the currently logged in user has write permission to the projectgroup with the context given as input
 * @param context context The context belonging to the project group to which write permissions are verified
 * @throws required_capability_exception if the currently logged in user is not allowed to write projectgroup with
 * the context given as input
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 */
function has_projectgroup_write_permission(context $context){
    /// Users must be able to read before they can write
    try {
        has_projectgroup_read_permission($context);
        return true;
    }
    catch (required_capability_exception $e)
    {
        // This warning is ignored
        return false;
    }
    
    
    // Add more restrictive code if needed
    
}

