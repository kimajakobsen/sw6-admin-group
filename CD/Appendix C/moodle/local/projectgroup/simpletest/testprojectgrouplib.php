<?php

/**
 * Unit tests for Project groups administrative tool
 *
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/local/projectgroup/baseprojectgrouptest.php');
require_once($CFG->dirroot . '/local/projectgroup/lib.php');

class local_projectgrouplib_test extends base_projectgroup_test 
{
    public  static $includecoverage = array('/local/projectgroup/lib.php');

    function testGetMembersOfGroupValidInput()
    {
         global $DB;
         
         $projectgroup = new stdClass();
         $projectgroup ->shortname = "groupWithMembers";
         $projectgroup ->fullname = "TestGetMembersOfGroup";
         
         //get existing user
         $userIDs = $this->getValidUserIdsFromDB(5);
         $projectgroup ->members = $userIDs;     
         
         //create group group
         $groupID = $this->createTestGroup($projectgroup);
         
         //call function with user id
         $arrayOfUsers = get_members_of_group($groupID);
      
         sort($arrayOfUsers);
         sort($userIDs);
      
         //Compare groups of user with the group we added the user to.   
         $this->assertEqual($arrayOfUsers,$userIDs);
         
         //cleanup
         $this->groupID[] = $groupID;  
    }



    function testGetGroupsOfUserValidInput()
    {
         global $DB;
         
         $projectgroup = new stdClass();
         $projectgroup ->shortname = "groupWithaMember";
         $projectgroup ->fullname = "TestCaseGetGroupOfUser";
         
         //get existing user
         $userID = $this->getValidUserIdsFromDB(1);
         $projectgroup ->members = $userID;     
         
         //create group group
         $groupID = $this->createTestGroup($projectgroup);
         
         //call function with user id
         $arrayOfGroups = get_groups_of_user($userID[0]);
      
         //Compare groups of user with the group we added the user to.   
         $correctGroup = in_array($groupID, $arrayOfGroups); 
         $this->assertTrue($correctGroup);
         
         //cleanup
         $this->groupID[] = $groupID;  
         
    }




    function testGetGroupsOfUserWithInvalidIdTypeString()
    {      
        global $DB;
         
        $this->expectException("coding_exception");
        //call function with user id
        $arrayOfGroups = get_groups_of_user("john");
         
         //Compare groups of user with the group we added the user to.
        $this->assertNull($arrayOfGroups);
    }
    
    function testGetGroupsOfUserNoGroups()
    {
        global $DB;
        $DB = $this->DBMock;
        
        $DB->setReturnValue('record_exists', true ,array('user',array('id'=>1)));
        $DB->setReturnValue('get_records', array() ,array('projectgroup_members',array('user'=>1)));
        
        //call funciton with user id
        $arrayOfGroups = get_groups_of_user(1);
        
        //expect an empty array
        $this->assertEqual($arrayOfGroups,array());
    }
    
    function testGetGroupsOfUserWithRole()
    {
        global $DB;
        $DB = $this->DBMock;
        $resultingObj = new stdClass();
        $resultingObj->role = 1;
        $resultingObj->user = 1;
        $resultingObj->projectgroup = 1;
        $resultingObj->id = 1;
        $resultingObj->added = 0;
        $resultingObj->updated = 0;
        
        $DB->setReturnValue('record_exists', true ,array('user',array('id'=>1)));
        $DB->setReturnValue('get_records', array($resultingObj) ,array('projectgroup_members',array('user'=>1,'role'=>1)));
        
        //call funciton with user id
        $arrayOfGroups = get_groups_of_user(1,1);
        
        //expect an empty array
        $this->assertEqual($arrayOfGroups,array(1));
    }
    
    function testGetGroupsOfUserWithRoleNoGroups()
    {
        global $DB;
        $DB = $this->DBMock;
        
        $DB->setReturnValue('record_exists', true ,array('user',array('id'=>1)));
        $DB->setReturnValue('get_records', array() ,array('projectgroup_members',array('user'=>1,'role'=>1)));
        
        //call funciton with user id
        $arrayOfGroups = get_groups_of_user(1,1);
        
        //expect an empty array
        $this->assertEqual($arrayOfGroups,array());
    }
    
    function testGetGroupsOfNonexistingUser()
    {
        global $DB;
        $DB = $this->DBMock;
        
        $DB->setReturnValue('record_exists', false ,array('user',array('id'=>1)));
        
        //Below line is how it is designed to work, but callers expect an empty array
        //$this->expectException("coding_exception");
        
        //call funciton with user id
        $arrayOfGroups = get_groups_of_user(1);
        $this->assertEqual($arrayOfGroups,array());
    }

    function testGetProjectGroup() {
        global $DB;
        $groupMembers = 6;  
        $stdRole = 0;   
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "testGetProjectGroup";
        $projectgroup ->fullname = "gruop";
        $staticShortName = $projectgroup ->shortname; 
        $staticFullName = $projectgroup ->fullname;
        
        //Get valid users (we dont want guest user, and guest do not have a picture XD)
        //The numbers of users are limeted to GROUPMEMBERS
        $memberIdArray = $this->getValidUserIdsFromDB($groupMembers);
        
        $projectgroup ->members = $memberIdArray;
           
        //Create group with members
        $before  = time();
        $groupID = $this->createTestGroup($projectgroup);
        $after   = time();
        $result  = get_projectgroup($groupID);
        
        //
        $actualMembers = array();
        foreach ($result->members as $value) {
            $actualMembers[] = $value->user;
        }
        
        $this->assertEqual($groupID,         $result->id);
        $this->assertEqual($staticShortName, $result->shortname);
        $this->assertEqual($staticFullName,  $result->fullname);
        $this->assertEqual(sort($memberIdArray),   sort($actualMembers));
        $this->assertWithinMargin($after,    $result->created,$after-$before);
        
        foreach ($result->members as $value) 
        {
            $this->assertEqual($stdRole,  $value->role);
            $this->assertWithinMargin($after, $value->added, $after-$before);
            $this->assertWithinMargin($after, $value->updated,$after-$before);
        }
    }
    
    function testGetNonExistantGroup() 
    {
        global $DB;
        //Setup
        $groupId = 1;
        $this->DBMock->setReturnValue('get_record',NULL,array('projectgroup',array('id'=>$groupId)));
                
        $DB = $this->DBMock;
        $DB->expectCallCount('update_record',0);
        $DB->expectNever('insert_record');
        $this->expectException('moodle_exception');
        //Run
        $result = get_projectgroup($groupId);
        
        //Assert
        $this->assertNull($result);
    }
    
    function testBlocksParseDefaultProjectgroupBlocksListEmpty() 
    {
        $result = blocks_parse_default_projectgroup_blocks_list('');
        
        $this->assertNotNull($result);
        
        $this->assertFalse($result);
    }
    
    function testBlocksParseDefaultProjectgroupBlocksListOneInEach() 
    {
        $expectedPre = array('a');
        $expectedContent = array('b');
        $expectedPost = array('c');
        $data = $expectedPre[0].':'.$expectedContent[0].':'.$expectedPost[0];
        $result = blocks_parse_default_projectgroup_blocks_list($data);
        
        $this->assertNotNull($result);
        
        $this->assertTrue(array_key_exists('side-pre',  $result),print_r($result,true));
        $this->assertTrue(array_key_exists('content',   $result),print_r($result,true));
        $this->assertTrue(array_key_exists('side-post', $result),print_r($result,true));
        
        $this->assertEqual($expectedPre,($result['side-pre']));
        $this->assertEqual($expectedContent,($result['content']));
        $this->assertEqual($expectedPost,($result['side-post']));
    }
    
    function testBlocksParseDefaultProjectgroupBlocksListOneInLast() 
    {
        $expectedPost = array('c');
        $data = '::'.$expectedPost[0];
        $result = blocks_parse_default_projectgroup_blocks_list($data);
        
        $this->assertNotNull($result);
        
        $this->assertFalse(array_key_exists('side-pre', $result));
        $this->assertFalse(array_key_exists('content', $result));
        $this->assertTrue(array_key_exists('side-post', $result));
        
        $this->assertEqual($expectedPost,($result['side-post']));
    }
    
    function testBlocksParseDefaultProjectgroupBlocksListOneInFirstNoColons() 
    {
        $expectedPre = array('a');
        $data = $expectedPre[0];
        $result = blocks_parse_default_projectgroup_blocks_list($data);
        
        $this->assertNotNull($result);
        
        $this->assertTrue(array_key_exists('side-pre', $result));
        $this->assertFalse(array_key_exists('content', $result));
        $this->assertFalse(array_key_exists('side-post', $result));
        
        $this->assertEqual($expectedPre,($result['side-pre']));
    }
    
    function testHasProjectgroupReadPermissionForMember()
    {
        Mock::generate('context_projectgroup', 'mockContext');
        $context = new mockContext();
        $context->setReturnValue('getProjectGroupId',1);
        $context->setReturnValue('get_parent_contexts',array($context));
        Mock::generate('capability_helper', 'helperMock');
        $capability_helper = new helperMock();
        $capability_helper->setReturnValue('has_capability', false);
        $capability_helper->setReturnValue('get_groups_of_user',array(1));
        
        has_projectgroup_read_permission($context,$capability_helper);
    }
    
    function testHasProjectgroupReadPermissionForNonMemberAdmin()
    {
        Mock::generate('context_projectgroup', 'mockContext');
        $context = new mockContext();
        $context->setReturnValue('getProjectGroupId',1);
        $context->setReturnValue('get_parent_contexts',array($context));
        Mock::generate('capability_helper', 'helperMock');
        $capability_helper = new helperMock();
        $capability_helper->setReturnValue('has_capability', true);
        $capability_helper->setReturnValue('get_groups_of_user',array(2));
        
        has_projectgroup_read_permission($context,$capability_helper);
    }
    
    function testHasProjectgroupReadPermissionForNonMemberNonAdmin()
    {
        Mock::generate('context_projectgroup', 'mockContext');
        $context = new mockContext();
        $context->setReturnValue('getProjectGroupId',1);
        $context->setReturnValue('get_parent_contexts',array($context));
        Mock::generate('capability_helper', 'helperMock');
        $capability_helper = new helperMock();
        $capability_helper->setReturnValue('has_capability', false);
        $capability_helper->setReturnValue('get_groups_of_user',array(2));
        
        $this->expectException('required_capability_exception');
        has_projectgroup_read_permission($context,$capability_helper);
    }
}
