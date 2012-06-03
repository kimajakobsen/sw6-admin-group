<?php
/**
 * Unit tests for Project group Library
 *
 * @author Rasmus Prentow et. al. 
 * @package mymmoodle
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/local/projectgroup/lib.php');
require_once($CFG->dirroot . '/admin/tool/projectgroup/lib.php');

class base_projectgroup_test extends UnitTestCase 
{
    protected $groupID = array(); //Contains all the group id's that need to be deleted in the teardown
    protected $groupTableName = "projectgroup";
    protected $groupMemTableName = "projectgroup_members";

    function setUp() 
    {
        global $DB;
        Mock::generate(get_class($DB), 'mockDB');
        $this->realDB = $DB;
        $this->DBMock = new mockDB();
    }

    function tearDown() 
    {
        global $DB;
        foreach ($this->groupID as $key => $value) { 
            $DB->delete_records($this->groupTableName,array("id"=>$value));  
            $DB->delete_records($this->groupMemTableName,array("projectgroup"=>$value));
            if(defined('DEBUG')) {
                echo "The following group has been deleted " . $value . "<br>";
            }
            
            //The id of the removed group is deleted from the groupID array 
            $this->groupID = array_diff($this->groupID,array($key=>$value));      
        }
        $DB = $this->realDB;
       
    }

    protected function createTestGroup($projectgroup = null){
        if(is_null($projectgroup)) 
        {
            $projectgroup = new stdClass();
            $projectgroup ->shortname = "testgroup1234";
            $projectgroup ->fullname = "testAddGroupMembers";
            $projectgroup->created = time();
        }       
        $groupID = save_or_update_projectgroup($projectgroup);       
        $this->groupID[] = $groupID;
        
        return $groupID;
    }
    
    
    protected function getValidUserIdsFromDB($amount) 
    {
        global $DB;
        $users = $DB->get_records_sql('SELECT id FROM mdl_user WHERE username != "guest" AND username != "admin" LIMIT '.$amount);
        $memberArray = array();
        foreach ($users as $key => $value) 
        {
            $memberArray[] = $value->id;
        }
        return $memberArray;
    }
}
