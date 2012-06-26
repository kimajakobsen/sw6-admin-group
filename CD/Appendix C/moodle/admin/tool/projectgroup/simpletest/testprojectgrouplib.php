<?php
 
/** 
 * Project group admin test
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/local/projectgroup/baseprojectgrouptest.php');
require_once($CFG->dirroot . '/admin/tool/projectgroup/lib.php');
require_once($CFG->dirroot . '/admin/tool/projectgroup/projectgroup_filtering.php');

class admintool_projectgrouplib_test extends base_projectgroup_test 
{
    public  static $includecoverage = array('/admin/tool/projectgroup/lib.php');

    function testCreateEmptyGroupWithValidInput() 
    {
        global $DB;
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "123456789012345678901234567890";
        $projectgroup ->fullname = 213423;
        $projectgroup ->created = time();
        
        //Due to the object is a reference, then the short name could be changed in the function, 
        //which is unwanted. Thus a static version of the names are saved.
        $staticShortName = $projectgroup ->shortname; 
        $staticFullName = $projectgroup ->fullname;
        
        //Call the function to be tested
        $groupID = save_or_update_projectgroup($projectgroup);
        
        //Loads the created table from db 
        $result = $DB->get_record($this->groupTableName,array('id'=>$groupID));
        
        $this->assertTrue(!empty($result));
        $this->assertEqual($staticShortName, $result->shortname);
        $this->assertEqual($staticFullName, $result->fullname);
        $this->assertIsA($projectgroup->created,"int");
        
        //Row will be removed
        $this->groupID[] = $groupID;  
    }
    
    function testCreateGroupWithValidInputWithMembers() 
    {
        global $DB;
        $groupMembers = 4;  //Number of members in the group     
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "grp1";
        $projectgroup ->fullname = "gruop";
        $projectgroup ->created = time();
           
        //Due to the object is a reference, then the short name could be changed in the function, 
        //which is unwanted. Thus a static version of the names are saved.
        $staticShortName = $projectgroup ->shortname; 
        $staticFullName = $projectgroup ->fullname;   
           
        //Get valid users 
        //The numbers of users are limeted to GROUPMEMBERS
        $users = $DB->get_records_sql('SELECT id FROM mdl_user WHERE username != "guest" AND username != "admin" LIMIT 0 ,'.$groupMembers);    
        $memberArray = array();
        foreach ($users as $key => $value) {
            $memberArray[] = $value->id;
        }    
        $projectgroup ->members = $memberArray;
           
        //Call the function to be tested
        $groupID = $this->createTestGroup($projectgroup);
            
        //Loads the created table from db 
        $result = $DB->get_record($this->groupTableName,array('id'=>$groupID));
        
        $this->assertTrue(!empty($result));
        if(!empty($result)){
            $this->assertEqual($staticShortName, $result->shortname);
            $this->assertEqual($staticFullName, $result->fullname);
            $this->assertIsA((int)$result->created,"int"); // It is extrmely hard to imagine a situation where this check fails
        }
    }

    function testEditGroupChangeShortFullName() 
    {
        global $DB;  
       
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "EditGroupChangeMembers";
        $projectgroup ->fullname = "EditGroupChangeMembers";
        
        
        //Create a group
        $groupID1 = $this->createTestGroup($projectgroup);
                 
        //Loads the created table from db 
        $projectgroupToBeEdited = $DB->get_record($this->groupTableName,array('id'=>$groupID1));
        
        $projectgroupToBeEdited->shortname = "dope is the dope";
        $projectgroupToBeEdited->fullname = "dope is the mope";
  
        //$projectgroupToBeEdited now contains an id property and new members
        $groupID2 = $this->createTestGroup($projectgroupToBeEdited);
        
        $result = $DB->get_record($this->groupTableName,array('id'=>$groupID1));

        
        //It is the ID the same?
        $this->assertEqual($groupID1,$groupID2);  
        $this->assertEqual($result->shortname,"dope is the dope"); 
        $this->assertEqual($result->fullname,"dope is the mope"); 
             
    }

    function testEditGroupChangeMembers() 
    {
        global $DB; 
        $groupMembers = 4;  //Number of members in the group     
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "EditGroupChangeMembers";
        $projectgroup ->fullname = "EditGroupChangeMembers";
        
        //Due to the object is a reference, then the short name could be changed in the function, 
        //which is unwanted. Thus a static version of the names are saved.
        $staticShortName = $projectgroup ->shortname; 
        $staticFullName = $projectgroup ->fullname;
        
        //Get valid users 
        //The numbers of users are limeted to GROUPMEMBERS
        $users = $DB->get_records_sql('SELECT id FROM mdl_user WHERE username != "guest" AND username != "admin" LIMIT 0 ,'.$groupMembers);    
        $memberArray = array();
        foreach ($users as $key => $value) {
            $memberArray[] = $value->id;
        }    
        $projectgroup ->members = $memberArray;
        //Create a group
        $groupID = $this->createTestGroup($projectgroup);
                 
        //Loads the created table from db 
        $projectgroupToBeEdited = $DB->get_record($this->groupTableName,array('id'=>$groupID));
        
        //get new members
        $newusers = $DB->get_records_sql('SELECT id FROM mdl_user WHERE username != "guest" AND username != "admin" LIMIT '.$groupMembers.' , ' .$groupMembers);    
        $newmemberArray = array();
        foreach ($newusers as $key => $value) {
            $newmemberArray[] = $value->id;
        }    
        $projectgroupToBeEdited ->members = $newmemberArray;
        
        //$projectgroupToBeEdited now contains an id property and new members
        $result = $this->createTestGroup($projectgroupToBeEdited);
        
        //It is the ID the same?
        $this->assertEqual($groupID,$result);       
    }

	function testCreateEmptyGroupWithNoShortname()
	{
	    global $DB;
		$DB = $this->DBMock;
        
        $projectgroup = new stdClass();   
        $projectgroup ->fullname = "group";
        
        //the function create project group should never insert the record
        $DB->expectNever('insert_record');
        //The function should throw a coding exception
        $this->expectException('coding_exception');
 
        //call the function
        $groupID = save_or_update_projectgroup($projectgroup);
        
        //pass if groupID is not set.
        $this->assertNull($groupID);
	}
    
    function testCreateEmptyGroupWithEmptyShortname()
    {
        global $DB;
        $DB = $this->DBMock;
        
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "";
        $projectgroup ->fullname = "group";
        
        //the function create project group should never insert the record
        $DB->expectNever('insert_record');
        //The function should throw a coding exception
        $this->expectException('coding_exception');
 
        //call the function
        $groupID = save_or_update_projectgroup($projectgroup);
        
        //pass if groupID is not set.
        $this->assertNull($groupID); 
    }

	function testCreateEmptyGroupWithNoFullname()
	{
		global $DB;
        $DB = $this->DBMock;
        
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "asddss";
        
        
        //the function create project group should never insert the record
        $DB->expectNever('insert_record');
        //The function should throw a coding exception
        $this->expectException('coding_exception');
 
        //call the function
        $groupID = save_or_update_projectgroup($projectgroup);
        
        //pass if groupID is not set.
        $this->assertNull($groupID);
	}
    
	function testCreateEmptyGroupWithEmptyFullname()
    {
        global $DB;
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = 123456789011234567890;
        $projectgroup ->fullname = "";
       
        
        //Due to the object is a reference, then the short name could be changed in the function, 
        //which is unwanted. Thus a static version of the names are saved.
        $staticShortName = (string)$projectgroup ->shortname; 
        $staticFullName = (string)$projectgroup ->fullname;
        
        //Call the function to be tested
        $groupID = save_or_update_projectgroup($projectgroup);
        
        //Loads the created table from db 
        $result = $DB->get_records($this->groupTableName,array('id'=>$groupID));
        
        $this->assertTrue(!empty($result));
        $this->assertEqual($staticShortName, $result[$groupID]->shortname);
        $this->assertEqual($staticFullName, $result[$groupID]->fullname);
        $this->assertIsA($projectgroup->created,"int");
        
        //Row will be removed
        $this->groupID[] = $groupID;  
    }
	
    function testCreateGroupWithSpecialChars()
    { 
        global $DB;
        
        // all strings are allowed
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "æøåÆØÅéèëäüïíì";
        $projectgroup ->fullname = "æøåÆØÅäÄëËöÖüÜïÏéÉèÈ -.,^~~'*!\"#¤%&/()=?`´| ";
              
        $staticShortName = (string)$projectgroup ->shortname; 
        $staticFullName = (string)$projectgroup ->fullname;
        
        $groupID = save_or_update_projectgroup($projectgroup);
        
        $result = $DB->get_records($this->groupTableName,array('id'=>$groupID));
        
        $this->assertTrue(($result));
        $this->assertEqual($staticShortName, $result[$groupID]->shortname);
        $this->assertEqual($staticFullName, $result[$groupID]->fullname);
      
        //cleanup
        $this->groupID[] = $groupID;
    }
    
    function testCreateGroupWithoutTags()
    {
        global $DB; 
        
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "æøåÆØÅ>>>éèëíì";
        $projectgroup ->fullname = "æøåÆØÅäÄ";
        $projectgroup ->created = time();
        
        $staticShortName = (string)$projectgroup ->shortname; 
        $staticFullName = (string)$projectgroup ->fullname; 
        
        $groupID = save_or_update_projectgroup($projectgroup);
        
        $result = $DB->get_records($this->groupTableName,array('id'=>$groupID));
        
        $this->assertFalse(empty($result));
        $this->assertEqual($staticShortName, $result[$groupID]->shortname);
        $this->assertEqual($staticFullName, $result[$groupID]->fullname);
        
        //cleanup
        $this->groupID[] = $groupID;
    }
    
    function testCreateGroupWithTags()
    {
        global $DB; 
        
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "æøåÆØÅ<h1>>><<<<\h1>éèëíì";
        $projectgroup ->fullname = "æøåÆØÅäÄ<script>>><<< </script>";
        $projectgroup ->created = time();
        
        $staticShortName = (string)$projectgroup ->shortname; 
        $staticFullName = (string)$projectgroup ->fullname; 
        
        $groupID = save_or_update_projectgroup($projectgroup);
        
        $result = $DB->get_records($this->groupTableName,array('id'=>$groupID));
        
        $this->assertFalse(empty($result));
        $this->assertNotEqual($staticShortName, $result[$groupID]->shortname);
        $this->assertNotEqual($staticFullName, $result[$groupID]->fullname);
        
        //cleanup
        $this->groupID[] = $groupID;
    }
	
	function testCreateWithDublicateName()
	{
		global $DB;
        
        //The data group 1
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "1234567890df3456789f0";
        $projectgroup ->fullname = 213423;
        $projectgroup2 = clone($projectgroup);
        
        //Due to the object is a reference, then the short name could be changed in the function, 
        //which is unwanted. Thus a static version of the names are saved.
        $staticShortName = $projectgroup->shortname; 
        $staticFullName = $projectgroup ->fullname;
       
        //Call the function twice 
        $groupID1 = $this->createTestGroup($projectgroup);
        //Row will be removed
        $this->groupID[] = $groupID1;
        
        $this->expectException("moodle_exception"); 
        $groupID2 = $this->createTestGroup($projectgroup2);
        
        $this->assertNotNull($groupID1);
        $this->assertNull($groupID2);

        if(isset($groupID2))
        {
            $this->groupID[] = $groupID2;     
        }
	}
    
	function testCreateWithToLongShortName()
	{
		global $DB;
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "12345678901234567890123423dsfd23456s78901"; //32
        $projectgroup ->fullname = 213423;
        $projectgroup ->created = time();
        
        $this->expectException("moodle_exception"); 
        //Call the function to be tested
        $groupID = save_or_update_projectgroup($projectgroup);
        
        //Loads the created table from db 
        $result = $DB->get_record($this->groupTableName,array('id'=>$groupID));
        
        $this->assertNull($result);
 
        //Row will be removed
        if(isset($result)) {
            $this->groupID[] = $groupID;
        }  
	}
	
    function testCreateGroupWithMembersWithStringsAsID()
    {
         global $DB;
         
        //The data
        $projectgroup = new stdClass();      
        $projectgroup ->fullname = "gruop14et5"; 
        $projectgroup ->shortname = "group12ds34f3";      
        $projectgroup->members = array('Per','Gitte','Poul','Heller');
        
        //run
        $this->expectException("coding_exception"); 
        $groupID1 = $this->createTestGroup($projectgroup);
        
        $this->assertNull($groupID1);
        
    }
    
    function testCreateGroupWithMembersWithMixedTypesAsID()
    {
         global $DB;
         
        //The data
        $projectgroup = new stdClass();      
        $projectgroup ->fullname = "gruop2"; 
        $projectgroup ->shortname = "group123";
        $projectgroup->members = array('123'=>'Per','123'=>123,'id'=>'Poul',5345=>'Heller');
        
        
        //run
        $this->expectException("coding_exception"); 
        $groupID2 = $this->createTestGroup($projectgroup);
        
        $this->assertNull($groupID2);
    }
    
    function testCreateGroupWithMembersWithDuplicateID()
    {
         global $DB;
         
        //The data
        $projectgroup = new stdClass();      
        $projectgroup ->fullname = "gruop3"; 
        $projectgroup ->shortname = "group12563";
        $projectgroup->members = array('12','10','12');
        
        
        $this->expectException("coding_exception"); 
        $groupID3 = $this->createTestGroup($projectgroup);
        
        $this->assertNull($groupID3);
    }
    
    function testCreateGroupWithMembersWithArrayAndObjectsAsID()
    {
        global $DB;
 
       //The data
        $projectgroup = new stdClass();      
        $projectgroup ->fullname = "gruop4"; 
        $projectgroup ->shortname = "group45123";
        $projectgroup->members = array($projectgroup,array('24','56'),array('asdasdasd','gdfgdr'));     
        
        $this->expectException("coding_exception"); 
        $groupID4 = $this->createTestGroup($projectgroup);
        
        $this->assertNull($groupID4);
         
    }
    
    function testAddMltplUserToGrp() 
    {
        global $DB;

        $DB = $this->DBMock;
       
       
        $DB->expectCallCount('insert_record', 2);   
        $DB->expectAt(0,'insert_record', array($this->groupMemTableName, array('projectgroup'=>1,'user'=>3, 'role'=>0, 'added'=>time(), 'updated'=>time())));
        $DB->expectAt(1,'insert_record', array($this->groupMemTableName, array('projectgroup'=>1,'user'=>4, 'role'=>0, 'added'=>time(), 'updated'=>time())));
        $DB->setReturnValue('record_exists', true ,array('projectgroup',array('id'=>1)));
        $DB->setReturnValue("record_exists", true, array('user',array('id'=>3)));
        $DB->setReturnValue("record_exists", true, array('user',array('id'=>4)));
        $DB->setReturnValue('record_exists', false,array($this->groupMemTableName,'*'));
        
        add_projectgroup_members(1, array(3,4));
       
        $DB = $this->realDB;
       
    }
    
    function testAddMltplUserToGrpUsingStdClass() 
    {
        global $DB;

        $DB = $this->DBMock;
       
       
        $DB->expectCallCount('insert_record', 2);   
        $DB->expectAt(0,'insert_record', array($this->groupMemTableName, array('projectgroup'=>1,'user'=>3, 'role'=>0, 'added'=>time(), 'updated'=>time())));
        $DB->expectAt(1,'insert_record', array($this->groupMemTableName, array('projectgroup'=>1,'user'=>4, 'role'=>0, 'added'=>time(), 'updated'=>time())));
        $DB->setReturnValue('record_exists', true ,array('projectgroup',array('id'=>1)));
        $DB->setReturnValue("record_exists", true, array('user',array('id'=>3)));
        $DB->setReturnValue("record_exists", true, array('user',array('id'=>4)));
        $DB->setReturnValue('record_exists', false,array($this->groupMemTableName,'*'));
        $user3 = new stdClass();
        $user4 = new stdClass();
        $user3->user = 3;
        $user4->user = 4;
        add_projectgroup_members(1, array($user3,$user4));
       
        $DB = $this->realDB;
       
    }
    
    function testAddNoneUserToGrp ()
    {
        $groupID = $this->createTestGroup();
        $this->expectException('coding_exception');
        add_projectgroup_members($groupID, array());
        
    }
    
    function testAddAlreadyAddedUser()
    {
        global $DB;
  
        $DB = $this->DBMock;
        
        $DB->setReturnValue('record_exists', true ,array('projectgroup',array('id'=>1)));
        $DB->setReturnValue('record_exists', true ,array('user','*'));
        $DB->setReturnValue('record_exists', true ,array($this->groupMemTableName,array('projectgroup'=>1,'user'=>3)));
        $DB->setReturnValue('record_exists', false ,array($this->groupMemTableName,array('projectgroup'=>1,'user'=>4)));
        $DB->expectCallCount('insert_record', 1);   
        $DB->expect('insert_record', array($this->groupMemTableName, array('projectgroup'=>1,'user'=>4,  'role'=>0, 'added'=>time(), 'updated'=>time())));
        add_projectgroup_members(1, array(3,4));
         
        
        $DB = $this->realDB;
    }

    function testAddMembersWithDublicateID()
    {
        global $DB;
    
        $DB = $this->DBMock;
        $DB->expectMaximumCallCount('insert_record', 0);
       
        $DB->setReturnValue('record_exists', false, array($this->groupMemTableName, '*'));
        $DB->setReturnValue('record_exists', true ,array('projectgroup',array('id'=>1)));
        $this->expectException('coding_exception');
        add_projectgroup_members(1, array(2,3,4,2));
        
         
        
        $DB = $this->realDB;
    }
    
    function testAddMembersWithBadID()
    {
        global $DB;
    
        $DB = $this->DBMock;
        $DB->expectMaximumCallCount('insert_record', 0);
       
        $DB->setReturnValue('record_exists', false, array($this->groupMemTableName, '*'));
        $DB->setReturnValue('record_exists', true ,array('projectgroup',array('id'=>1)));
        $this->expectException('coding_exception');
        $user3 = new stdClass();
        $user4 = new stdClass();
        $user3->user = 3;
        $user4->noidid = 4;
        add_projectgroup_members(1, array($user3,$user4));
        
         
        
        $DB = $this->realDB;
    }
    
    function testAddMembersWithBadID2()
    {
        global $DB;
    
        $DB = $this->DBMock;
        $DB->expectMaximumCallCount('insert_record', 0);
       
        $DB->setReturnValue('record_exists', false, array($this->groupMemTableName, '*'));
        $DB->setReturnValue('record_exists', true ,array('projectgroup',array('id'=>1)));
        $this->expectException('coding_exception');
        $user3 = new stdClass();
        $user4 = new stdClass();
        $user3->user = 3;
        $user4->user = 'johndson';
        add_projectgroup_members(1, array($user3,$user4));
        
         
        
        $DB = $this->realDB;
    }
     
    function testAddNonExistantUser()
    {
        global $DB;
        
        $DB = $this->DBMock;
        

        $DB->setReturnValue("record_exists", true, array('projectgroup',array('id'=>1)));
        $DB->setReturnValue("record_exists",false, array('user',array('id'=>3)));
        $DB->setReturnValue("record_exists",true, array('user',array('id'=>4)));
        $DB->expectMaximumCallCount('insert_record', 1);
        $DB->expectCallCount('rollback_delegated_transaction', 1);
        $this->expectException('coding_exception');
        add_projectgroup_members(1,array(4,3));    
    
        $DB = $this->realDB;
    }
    
    function testAddUserUnMocked()
    {
        //Setup
        global $DB;
        
        $DB = $this->realDB;
        $groupMembers = 1;
        
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "testAddUserUnMocked";
        $projectgroup ->fullname = "testAddUserUnMocked full";
        
        $groupId = $this->createTestGroup($projectgroup);
        $memberArray = $this->getValidUserIdsFromDB($groupMembers);
        $expectedMember = array_shift(array_values($memberArray));
        
        //Run
        $result = add_projectgroup_members($groupId,$memberArray);
        
        //Assert
        $this->assertTrue($result);
        $actualMember = $DB->get_record($this->groupMemTableName,array('projectgroup'=>$groupId));
        $this->assertNotNull($actualMember);
        $this->assertEqual($actualMember->user,$expectedMember);
    }
    
    function testAddUserOverwrite()
    {
        //Setup
        global $DB;
        
        $DB = $this->realDB;
        $groupMembers = 10;
        
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "testAddUserOverwrite";
        $projectgroup ->fullname = "group";
        $memberArray = $this->getValidUserIdsFromDB($groupMembers);
        $initialMembers = array_slice($memberArray, 0, 6);
        $expectedMembers = array_slice($memberArray, 3, 7);
        $projectgroup->members = $initialMembers;
        
        $groupId = $this->createTestGroup($projectgroup);
        
        //Run
        $result = add_projectgroup_members($groupId,$expectedMembers,true);
        
        //Assert
        $this->assertTrue($result);
        $actualMembers = $DB->get_records($this->groupMemTableName,array('projectgroup'=>$groupId));
        $this->assertEqual(7, sizeof($actualMembers));
 
        $expectedMembers = array_values($expectedMembers);
        $actualMember_ids = array();
        foreach ($actualMembers as $key => $value) {
            $actualMember_ids[] = $value->user;
        }

        sort($actualMember_ids);
        sort($expectedMembers);
        $this->assertEqual($expectedMembers,$actualMember_ids);
        
        
    
    }
    
        function testAddUserNonOverwrite()
    {
        //Setup
        global $DB;
        
        $DB = $this->realDB;
        $groupMembers = 10;
        
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "testAddUserOverwrite";
        $projectgroup ->fullname = "group";
        $memberArray = $this->getValidUserIdsFromDB($groupMembers);
        $initialMembers = array_slice($memberArray, 0, 6);
        $expectedMembers = $memberArray;
        $projectgroup->members = $initialMembers;
        
        $groupId = $this->createTestGroup($projectgroup);
        
        //Run
        $result = add_projectgroup_members($groupId,$expectedMembers,false);
        
        //Assert
        $this->assertTrue($result);
        $actualMembers = $DB->get_records($this->groupMemTableName,array('projectgroup'=>$groupId));
        $this->assertEqual(10, sizeof($actualMembers));
 
        $expectedMembers = array_values($expectedMembers);
        $actualMember_ids = array();
        foreach ($actualMembers as $key => $value) {
            $actualMember_ids[] = $value->user;
        }

        sort($actualMember_ids);
        sort($expectedMembers);
        $this->assertEqual($expectedMembers,$actualMember_ids);
        
        
    
    }
    
    
    
    
    function testDeleteGroupWithMembers()
    {
        global $DB;
        //define("GROUPMEMBERS", 6);  //Number of members in the group
        $groupMembers = 6;     
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "grp3";
        $projectgroup ->fullname = "gruop";
        $projectgroup ->created = time();
        $staticShortName = $projectgroup ->shortname; 
        $staticFullName = $projectgroup ->fullname;
        
        //Get valid users (we dont want guest user, and guest do not have a picture XD)
        //The numbers of users are limeted to GROUPMEMBERS
        $users = $DB->get_records_sql('SELECT id FROM mdl_user WHERE username != "guest" AND username != "admin" LIMIT 0 , 10');
        $memberArray = array();
        foreach ($users as $key => $value) {
            $memberArray[] = $value->id;
        }  
        $memberArray = array_slice($memberArray, 0 ,$groupMembers );    
        $projectgroup ->members = $memberArray;
           
        //Create group with members
        $groupID = save_or_update_projectgroup($projectgroup);
        
        $result = delete_projectgroup($groupID);    
        
        $this->assertTrue($result);
       
        
        //If group was not deleted, then delete it.
        if(!$result){
            $this->groupID[] = $groupID;
        } 
    }
    
    function testDeleteNonExistantGroup() 
    {
        global $DB;
        //Setup
        $groupId = 1;
        $this->DBMock->setReturnValue('record_exists',false,array('projectgroup',array('id'=>$groupId)));
        $this->DBMock->setReturnValue('record_exists',true,array('projectgroup','*'));
        
        //Define expectations
        $this->DBMock->expectNever('delete_records');
        $this->DBMock->expectNever('commit_delegated_transaction');
        $this->DBMock->expectCallCount('start_delegated_transaction',1);
        $this->DBMock->expectCallCount('rollback_delegated_transaction',1);
        
        $DB = $this->DBMock;
        $this->expectException('coding_exception');
        
        //Run
        $result = delete_projectgroup($groupId);
        
        //Assert
        $this->assertNull($result);
    }

    function testRemoveAllUsersFromGroupWithOneCall(){
        global $DB;    
        $groupmembers = "6";
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "delAllGrp";
        $projectgroup ->fullname = "Delete All Members of This Group";
        $staticShortName = $projectgroup ->shortname; 
        $staticFullName = $projectgroup ->fullname;
        
        //Get valid users (we dont want guest user, and guest do not have a picture XD)
        //The numbers of users are limeted to GROUPMEMBERS
        $users = $DB->get_records_sql('SELECT id FROM mdl_user WHERE username != "guest" AND username != "admin" LIMIT 0 , '.$groupmembers);
        $memberArray = array();
        foreach ($users as $key => $value) {
            $memberArray[] = $value->id;
        }  
        $projectgroup ->members = $memberArray;
           
        //Create group with members
        $groupId = $this->createTestGroup($projectgroup);
        
        $preCount = sizeof($memberArray);
        
        remove_all_projectgroup_members($groupId);
        
        $remainingMembers = $DB->get_records($this->groupMemTableName, array("projectgroup"=>$groupId));
        
        $remainingCount = sizeof($remainingMembers);
        
        $this->assertEqual(0, $remainingCount);
    }
    
    //REMOVE PROJECT GROUP -- ID
    function testRemoveUserFromGroupValidId()
    {
        global $DB;    
        $groupmembers = 6;
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "grp5";
        $projectgroup ->fullname = "gruop";
        $staticShortName = $projectgroup ->shortname; 
        $staticFullName = $projectgroup ->fullname;
        
        //Get valid users (we dont want guest user, and guest do not have a picture XD)
        //The numbers of users are limeted to GROUPMEMBERS
        $users = $DB->get_records_sql('SELECT id FROM mdl_user WHERE username != "guest" AND username != "admin" LIMIT 0 , '.$groupmembers);
        $memberArray = array();
        foreach ($users as $key => $value) {
            $memberArray[] = $value->id;
        }  
        $projectgroup ->members = $memberArray;
           
        //Create group with members
        $groupId = $this->createTestGroup($projectgroup);
        
        $preCount = sizeof($memberArray);
        
        $firstMember = array();
        $firstMember = array_slice($memberArray, 0, 1);
        $expected = array_slice($memberArray, 1, $groupmembers-1);
        sort($expected);
        
        remove_projectgroup_members($groupId, $firstMember);
        
        $remainingMembers = $DB->get_records($this->groupMemTableName, array("projectgroup"=>$groupId));
        
        $remainingCount = sizeof($remainingMembers);
        $this->assertEqual($groupmembers - 1, $remainingCount);
        foreach ($remainingMembers as $key => $value) {
            $remainingMembers[$key] = $value->user;
        }
        $remainingMembers = array_slice($remainingMembers, 0, $remainingCount);
        sort($remainingMembers);
        $this->assertEqual(($remainingMembers),($expected));
    }

    function testRemoveMembersFromGroupWithInvalidGroupId()
    {
        global $DB;
        //Setup
        $groupId = 'some_string';
        $data = array(1,2,3);
        
        //Mock database
        $DB = $this->DBMock;
        
        //Set expectations
        $DB->expectNever('delete_records_select');
        $DB->expectNever('start_delegated_transaction');
        
        $this->expectException('coding_exception');
        
        //Run
        remove_projectgroup_members($groupId,$data);
    }
    
    function testRemoveMembersFromGroupWithValidButUnknownGroupId()
    {
        global $DB;
        //Setup
        $groupId = 1;
        $data = array(1,2,3);
        
        //Mock database
        $DB = $this->DBMock;
        $DB->setReturnValue('record_exists', false, array($this->groupTableName,array('id'=>1)));
        $DB->setReturnValue('record_exists', true,  array($this->groupTableName, '*'));
        
        //Set expectations
        $DB->expectNever('delete_records_select');
        $DB->expectOnce('start_delegated_transaction');
        $DB->expectOnce('rollback_delegated_transaction');
        $DB->expectNever('commit_delegated_transaction');
        
        $this->expectException('coding_exception');
        
        //Run
        remove_projectgroup_members($groupId,$data);
    }

    //REMOVE PROJECT GROUP -- MEMBERS
    function testRemoveMembersFromGroupEveryone()
    {
        global $DB;
        
        $groupmembers = 6;
       
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "grp4";
        $projectgroup ->fullname = "group";
        
        //Get valid users (we dont want guest user)
        //The numbers of users are limeted to GROUPMEMBERS
        $users = $DB->get_records_sql('SELECT id FROM mdl_user WHERE username != "guest" AND username != "admin" LIMIT 0 ,'.$groupmembers);   
        $memberArray = array();
        foreach ($users as $key => $value) {
            $memberArray[] = $value->id;
        }    
        $projectgroup ->members = $memberArray;
        
        //cereate the test group with members
        $groupId = $this->createTestGroup($projectgroup);
        
        //load the members from the group, (used to make sure that they where added in the first place)
        $members = $DB->get_records($this->groupMemTableName, array("projectgroup"=>$groupId));
        
        //get the id of all the members in the group $groupID
        $membersID = array();
        foreach ($members as $key => $usrStd) {
            $membersID[] = $usrStd ->user;
        } 
        
        //makes sure that we have the wanted number of members in the group
        $memberCount = sizeof($membersID);
        $this->assertEqual($memberCount , $groupmembers);
        
        remove_projectgroup_members($groupId, $membersID);
        
        //array with the members in the group. Should be empty.
        $delMembers = $DB->get_records($this->groupMemTableName, array("projectgroup"=>$groupId));
     
        $this->assertTrue(empty($delMembers));     
    }

    function testRemoveOneUserFromOneGroupNotOtherGroups()
    {
        global $DB;    
        $groupmembers = 6;
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "testRemove27Groups";
        $projectgroup ->fullname = "testRemoveOneUserFromOneGroupNotOtherGroups";
        
        //Get valid users (we dont want guest user, and guest do not have a picture XD)
        //The numbers of users are limeted to GROUPMEMBERS
        $users = $DB->get_records_sql('SELECT id FROM mdl_user WHERE username != "guest" AND username != "admin" LIMIT 0 , '.$groupmembers);
        $memberArray = array();
        foreach ($users as $key => $value) {
            $memberArray[] = $value->id;
        }  
        $projectgroup ->members = $memberArray;
        
        //Create another group with the same members
        $projectgroup2 = new stdClass();
        $projectgroup2 ->shortname = "testRemove27Groups2";
        $projectgroup2 ->fullname = "testRemoveOneUserFromOneGroupNotOtherGroups2";
        $projectgroup2 ->members = $memberArray;
           
        //Create group with members
        $groupId1 = $this->createTestGroup($projectgroup);
        
        $groupId2 = $this->createTestGroup($projectgroup2);
        
        $preCount = sizeof($memberArray);
        
        $firstMember = array();
        $firstMember = array_slice($memberArray, 0, 1);
        
        /////////////////////////////////
        // Verify that the testdata is available
        /////////////////////////////////
        $remainingMembers = $DB->get_records($this->groupMemTableName, array("projectgroup"=>$groupId2));
        $remainingCount = sizeof($remainingMembers);
        $this->assertEqual($groupmembers , $remainingCount, 'This test is inconclusive. Second test group is not created corectly');
        //VERIFYED
        
        //Run
        remove_projectgroup_members($groupId1, $firstMember);
        
        //Assert
        $remainingMembers = $DB->get_records($this->groupMemTableName, array("projectgroup"=>$groupId2));
        $remainingCount = sizeof($remainingMembers);
        $this->assertEqual($groupmembers , $remainingCount);
    }
    
    function testRemoveUserNotInGroup()
    {
        global $DB;
        $groupmembers =  6;  //Number of members in the group 
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "grp6";
        $projectgroup ->fullname = "group";
        $projectgroup ->created = time();
        $id = 1;
        
        $users = $DB->get_records_sql('SELECT id FROM mdl_user WHERE id != '.$id.' LIMIT 0 , 10');
        $memberArray = array();
        foreach ($users as $key => $value) {
            $memberArray[] = $value->id;
        }  
        $memberArray = array_slice($memberArray, 0 ,$groupmembers);    
        $projectgroup ->members = $memberArray;
           
        //Create group with members
        $groupId = $this->createTestGroup($projectgroup);
        
        $memberssToRemove = array($id);
        
        $this->expectException("coding_exception");
        remove_projectgroup_members($groupId, $memberssToRemove);
    }
    
    function testRemoveNoUserFromGroup()
    {
        global $DB;
        //Setup
        $groupId = 1;
        $data = array();
        
        //Mocking up database
        $DB = $this->DBMock;
        
        //Set expectations
        $DB->expectNever('start_delegated_transaction');
        $DB->expectNever('delete_records_select');
        $this->expectException('coding_exception');
        
        //Run
        remove_projectgroup_members($groupId,$data);
    }
    
    function testRemoveSomethingThatIsNotASetOfMembersFromGroup1()
    {
        global $DB;
        //Setup
        $groupId = 1;
        $data = array(1,2,'johnson');
        
        $DB = $this->DBMock;
        $DB->expectNever('delete_records_select');
        $DB->expectNever('count_records_select');
        $DB->expectNever('record_exists');
        $this->expectException('coding_exception');
        
        //Run
        remove_projectgroup_members($groupId,$data);
    }
        
    function testRemoveSomethingThatIsNotASetOfMembersFromGroup2()
    {
        global $DB;
        //Setup
        $groupId = 1;
        $data = new stdClass();
        $data->id = 1;
        $data->ids = array(1);
        $data->member = 1;
        $data->members = array(1);
        
        $DB = $this->DBMock;
        $DB->expectNever('delete_records_select');
        $DB->expectNever('count_records_select');
        $DB->expectNever('record_exists');
        $this->expectException('coding_exception');
        
        //Run
        remove_projectgroup_members($groupId,$data);
    }
    
    function testRemoveMembersFromGroupFailOnDelete()
    {
        global $DB;
        //Setup
        $groupId = 1;
        $data = array(1,2,3);
        
        //Mock database
        $DB = $this->DBMock;
        $DB->setReturnValue('record_exists', true, array($this->groupTableName,array('id'=>1)));
        $DB->setReturnValue('record_exists', false,  array($this->groupTableName, '*'));
        $DB->setReturnValue('count_records_select', sizeof($data),  array($this->groupMemTableName, '*'));
        $DB->throwOn('delete_records_select', new dml_exception('This is a test dml exception'));
        
        //Set expectations
        $DB->expectOnce('start_delegated_transaction');
        $DB->expectOnce('rollback_delegated_transaction');
        $DB->expectNever('commit_delegated_transaction');
        
        $this->expectException('dml_exception');
        
        //Run
        remove_projectgroup_members($groupId,$data);
    }
    
    function testGetProjectGroupsLimit() {
        global $DB;  
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "testGetProjectGroups1";
        $projectgroup ->fullname = "group";
        $staticShortName = $projectgroup ->shortname; 
        $staticFullName = $projectgroup ->fullname;
        
        $projectgroup2 = new stdClass();
        $projectgroup2 ->shortname = "testGetProjectGroups2";
        $projectgroup2 ->fullname = "group";
        
           
        //Create group with members
        $groupID = $this->createTestGroup($projectgroup);
        $this->createTestGroup($projectgroup2);
        
        $result  = get_projectgroups(array('limit'=>1));
        
        $this->assertEqual(sizeof($result),1);
    }
    
    function testGetProjectGroupsWithFiltering() {
        global $DB;
        global $SESSION;
        //Mock::generate('projectgroup_filtering', 'mock_projectgroup_filtering');
        
        //The data
        $projectgroup = new stdClass();
        $projectgroup ->shortname = "test20Filtering";
        $projectgroup ->fullname = "group";
        $staticShortName = $projectgroup ->shortname; 
        $staticFullName = $projectgroup ->fullname;
        
        $SESSION->projectgroup_filtering = array('shortname'=>array(array('operator'=>2,'value'=>$staticShortName)));
        $filtering = new projectgroup_filtering();
           
        //Create group with members
        $groupID = $this->createTestGroup($projectgroup);
        
        $result  = get_projectgroups(array(),$filtering);
        $result = array_pop($result);
        
        $this->assertEqual($groupID,         $result->id);
        $this->assertEqual($staticShortName, $result->shortname);
        $this->assertEqual($staticFullName,  $result->fullname);
        unset($SESSION->projectgroup_filtering);
    }
}
