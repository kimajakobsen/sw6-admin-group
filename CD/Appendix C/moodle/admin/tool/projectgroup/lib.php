<?php
global $CFG;
require_once $CFG->dirroot.'/local/projectgroup/lib.php';
require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));
define("PROJECTGROUPS_PER_PAGE", 50);

/**  
 * Creates a project group from an object. 
 * Defines created date if created date was not assigned already.
 * If members param is defined it called add_projectgroup_members
 * @param stdClass group with shortname(string) and fullname(string). created(int) and members(array[int]) are optional 
 * @return mixed on succes return id, else it throws an exception.
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
function save_or_update_projectgroup($group)
{
	global $DB;
    
    if(!is_object($group))
    {
        throw new coding_exception('did not recive an object');
    }
    
    //Remove tags from names
    if(isset($group->shortname))
    {
        $group->shortname = (string)($group->shortname);
        if(is_string($group->shortname) && strlen($group->shortname) > 0)
        {
            $group->shortname = strip_tags($group->shortname);
        }
        else 
        {
            throw new coding_exception('shortname is not a string longer then 0');    
        }
    }
    else 
    {
        throw new coding_exception('shortname not defined');
    }
    
    if(isset($group->fullname))
    {
        $group->fullname = (string)($group->fullname);
        if(is_string($group->fullname))
        {
            $group->fullname = strip_tags($group->fullname);
        }
        else 
        {
            throw new coding_exception('fullname is not a string');    
        }
    }
    else 
    {
        throw new coding_exception('fullname not defined');
    }
   
    //is members in the right format
    if(!empty($group->members))
    {
         if(!is_array($group->members))
         {
             throw new coding_exception('members is not an array');
         }
         
       
         foreach ($group->members as $key => &$id) {           
             if(is_string($id))
             {
                 $id = (int)$id;
             }
             
             if(!is_int($id) && !is_object($id))
             {
                 throw new coding_exception('id in members array must be an int');
             }
         }
         
    }
   
    //set created if not set
	if(isset($group->created))
    {
        $group->created = (int)$group->created;
        if(!is_int($group->created))
        {
            throw new coding_exception('created(unixtimestamp) is not a int');
        }
    }
    else 
    {
        $group->created = time();
    }    
    
    if(isset($group->id))//edit
    {
        $DB->update_record("projectgroup", $group);
        $groupID = $group->id;
        try
        {
            if(isset($group->members))
            {
                
                add_projectgroup_members($group->id, $group->members,true);
            }
        }
        catch(moodle_exception $e)
        {
            $DB->delete_records("projectgroup",array("id"=>$group->id));  
            throw $e;
        }  
    }
    else //add
    {
        $transaction = $DB->start_delegated_transaction();
        try
        {
            try 
            {
                $groupID = $DB->insert_record("projectgroup",$group,true);
            }
            catch (dml_exception $e)
            {
                throw new dml_exception('Unable to add project group with short name: "'.$group->shortname.'" Possible duplication.');
            }
            if(isset($group->members))
            {
                add_projectgroup_members($groupID, $group->members);
            }
            $group->id = $groupID;
            blocks_add_default_projectgroup_blocks($group);
        }
        catch(moodle_exception $e)
        {
            $DB->rollback_delegated_transaction($transaction, $e); 
            throw $e;
        }  
        $DB->commit_delegated_transaction($transaction);
    }

    return $groupID; 
}





/**
 * Adds the users to the group. 
 * @param int group_id The group id 
 * @param array(int/object) users List of user ids or list of object with id property
 * @param bool overwrite deletes the old users and adds $users
 * @return bool true on success, false otherwise
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
function add_projectgroup_members($group_id, $users, $overwrite=false)
{
    global $DB;
    if(!is_array($users) || (empty($users) && !$overwrite))
    {
        throw new coding_exception('Invalid list of users. Got this: '.print_r($users,true));
    }
    
    // Convert list of objects to list of ids
    foreach ($users as $key => $value) 
    {
        if(is_object($value)){
            if(!isset($value->user))
            {
                throw new coding_exception('Invalid list of users: '.print_r($users,true));
            }
            if(!isset($value->role))
            {
                $users[$key]->role = 0;
            }
        }
        else
        {
            
            $userObj = new stdClass();
            $userObj->user = $value;
            $userObj->role = 0;
            $users[$key] = $userObj;
        }
    }
    
    // Chech for duplicates
    if(sizeof($users) 
    
    != 
    
    sizeof(array_unique($users, SORT_REGULAR)))
    {
        throw new coding_exception('The usr array contained duplicate');
    }
    
    // Check group id is there.   (redundant)
    if(is_null($group_id) || $group_id < 1)
    {
        throw new coding_exception('Invalid group id');
    }
    
    // Check that the project group exists
    if(!$DB->record_exists('projectgroup', array('id'=>$group_id)))
    {
        throw new coding_exception('Group did not exist with hid ' . $group_id );
    }
    
    // Start transaction 
    $transaction = $DB->start_delegated_transaction();
   

    if($overwrite)//Delete existing members
    {
        
      remove_all_projectgroup_members($group_id);

        
    }

    foreach ($users as $member) 
    {
        
        // Check that user exist
        if(!$DB->record_exists('user', array('id'=>$member->user)))
        {
            // rollback
            $e = new coding_exception('Invalid list of users');
            $DB->rollback_delegated_transaction($transaction, $e);
            throw $e;
        }
        
        // Check that user is not already member
        if($overwrite || !$DB->record_exists('projectgroup_members',array('projectgroup'=>$group_id,'user'=>$member->user)))
        {
            
            // If he is already member we don't throw an exception
            $DB->insert_record('projectgroup_members', array('projectgroup'=>$group_id,'user'=>$member->user,'role'=>$member->role, 'added'=>time(), 'updated'=>time()));
        }      
    }
    
    // Commit
    $DB->commit_delegated_transaction($transaction);
    
    return true;
    
}

/**
 * Deletes a project group
 * @param int group_id The id of the group to delete
 * @return bool true
 * @throws exception when the group can not be deleted
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
function delete_projectgroup($group_id)
{
    global $DB;
    if(!is_int((int)$group_id)) {
        throw new coding_exception('Failed to delete group. Supplied id is not a valid id. Id received: "' . $group_id . '"');
    }
    
    $transaction = $DB->start_delegated_transaction();
    try 
    {
        if(!$DB->record_exists('projectgroup',array('id' => $group_id))) {
            throw new coding_exception('Unable to delete group with id: "'.$group_id.'", it does not exist');
        }
        $DB->delete_records('projectgroup_members',array('projectgroup' => $group_id));
        $DB->delete_records('projectgroup',array('id' => $group_id));
    }
    catch(Exception $e)
    {
        $DB->rollback_delegated_transaction($transaction,$e);
        throw $e;
    }
    $DB->commit_delegated_transaction($transaction);
    return true;
}

 /**
 * Removes the users from the group. The users to remove must be members of the group prior to the call 
 * of this function 
 * @param int group_id The group id 
 * @param array(int) users list of user ids to be removed, must be non-empty. 
 * @return bool true on success, false otherwise
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
function remove_projectgroup_members($group_id, $users)
{
    global $DB;
    
    if(((int)$group_id) == 0 && $group_id !== 0) 
    {
        throw new coding_exception('Failed, supplied id is not a valid id. Id received: "' . $group_id . '"');
    }
    if(!is_array($users)) 
    {
        throw new coding_exception('Failed, trying to remove something else than members');
    }
    if(empty($users)) 
    {
        throw new coding_exception('Failed, trying to remove an empty set of members');
    }
    foreach ($users as $user) 
    {
    	if(((int)$user) == 0 && $user !== 0) 
    	{
            throw new coding_exception('Failed, supplied user id is not a valid id. Id received: "' . $user . '"');
        }
    }
    
    $transaction = $DB->start_delegated_transaction();
    try 
    {
        if(!$DB->record_exists('projectgroup',array('id' => $group_id))) {
            throw new coding_exception('The group with id: "'.$group_id.'", does not exist');
        }
        $param = array('projectgroup' => $group_id);
        $ids = implode(', ',$users);
        
        $membersThatWillBeDeleted = $DB->count_records_select('projectgroup_members', "user in (" . $ids . ") and projectgroup = ".$group_id); //It seem that the $param argument does not work in this function :/
        
        if($membersThatWillBeDeleted != sizeof($users)) {
            throw new coding_exception('The group with id: "'.$group_id.'", does not contain all the group members: "'.$ids.'". Number of memers in group: '.$membersThatWillBeDeleted);
        }
        $DB->delete_records_select("projectgroup_members","user in (" . $ids . ") and projectgroup = ".$group_id); //It seem that the $param argument does not work in this function :/
    }
    catch(Exception $e)
    {
        $DB->rollback_delegated_transaction($transaction,$e);
        throw $e;
    }
    $DB->commit_delegated_transaction($transaction);
    return true;
}

/**
 * removes all users from the group. 
 * @param int group_id The group id  
 * @return bool true on success, false otherwise
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
function remove_all_projectgroup_members($group_id){
    
    global $DB;
    
    if(!is_int((int)$group_id)) {
        throw new coding_exception('Failed, supplied id is not a valid id. Id received: "' . $group_id . '"');
    }
    
    $transaction = $DB->start_delegated_transaction();
    try 
    {
        if(!$DB->record_exists('projectgroup',array('id' => $group_id))) {
            throw new coding_exception('the group with id: "'.$group_id.'", does not exist');
        }

        $users = $DB->get_records("projectgroup_members", array('projectgroup'=>$group_id));
        if(!empty($users)) 
        {
            $delete_users = sanitize_user_array($users);
            
            remove_projectgroup_members($group_id, $delete_users);
        }
    }
    catch(Exception $e)
    {
        $DB->rollback_delegated_transaction($transaction,$e);
        throw $e;
        return false;
    }
    $DB->commit_delegated_transaction($transaction);
    return true;
}



/**
 * Get a number of project groups
 * @param parms array An associative array, keys allowed:
 *          limit  : The maximum number of project groups to return.
 *          offset : Offset in the projectgroup database.
 * @param filtering projectgroup_filtering Filter used to select projectgroups.
 * @return array of stdClasses that has the following fields:
 *          id        : Identifier (unique)
 *          shortname : Short name (unique)
 *          fullname  : Longer and more descriptive name
 *          created   : Timestamp indicating the creation date of group (formatted as the time() function)
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
 function get_projectgroups($params,$filtering = null)
 {
     global $DB;
     global $SESSION;
     if(isset($filtering)) {
        list($where,$whereParams) = $filtering->get_sql_filter();
     }
     else {
         $where = null;
         $whereParams = null;
     }
     $limit = PROJECTGROUPS_PER_PAGE;
     $offset = 0;
     if(isset($params["limit"]))
     {
         $limit = $params["limit"];
     }
     
     if(isset($params["offset"]))
     {
         $offset = $params["offset"];
     }
     
     return $DB->get_records_select('projectgroup', $where,$whereParams,'','*', $offset, $limit);
 }
 
/**
 * Get list of groups
 * @param filtering projectgroup_filtering Filter used to select projectgroups to be counted.
 * @return int - number of project groups.
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
 function get_projectgroup_count($filtering = null)
 {
     global $DB;
     if(isset($filtering)) {
        list($where,$whereParams) = $filtering->get_sql_filter();
     }
     else {
         $where = null;
         $whereParams = null;
     }
     return $DB->count_records_select('projectgroup',$where,$whereParams);
 } 
  
 /**
  * Cleans an array of users
  * @param $users array The array of users to sanitize
  * @return array Sanitized array
  * @throws coding_exception If the array is not consisting of users or not an array at all
  * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
  * @package mymmoodle
  */
 function sanitize_user_array($users) 
 {
    $result = array();
    if(!is_array($users)) 
    {
        throw new coding_exception('Failed, not given a set of users');
    }
    if(empty($users)) 
    {
        throw new coding_exception('Failed, user set is empty');
    }
    foreach ($users as $user) 
    {
        if(is_string($user)) {
            if(((int)$user) == 0 && trim($user) !== '0') {
               throw new coding_exception('Failed, supplied user id is not a valid id. Id received: "' . $user . '"');
            }
            $result[] = $user;
        }
        else if(is_int($user)) {
            $result[] = $user;
        } 
        else if(is_object($user)) {
            
            if(!isset($user->user)) {
                throw new coding_exception('Failed, supplied user did not have "user" field. User received: "' . $user . '"');
            }
            $result[] = $user->user;
        }
        else 
        {
            throw new coding_exception('Failed, supplied user id is not a valid id. Id received: "' . $user . '"');
        }
    }
    
    return $result;
 }
 
 /**
  * Get selecting data (HTML select) for the project groups obeying the filtering
  * @param filterring projectgroup_filtering 
  * @return array Associative array that can be used to create an HTML select tag with the entries as options
  * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
  * @package mymmoodle
  */
 function get_projectgroup_selection_data($filtering) 
 {
    global $DB;

    // get the SQL filter
    list($sqlwhere, $params) = $filtering->get_sql_filter();

    $projectgrouplist = array('aprojectgroups'=>false);
    $projectgrouplist['aprojectgroups'] = $DB->get_records_select_menu('projectgroup', $sqlwhere, $params);

    return $projectgrouplist;
 }
 


 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 