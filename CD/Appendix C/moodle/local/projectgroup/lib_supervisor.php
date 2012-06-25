<?php

require_once($CFG->dirroot ."/calendar/lib.php");

function actionCreateMeeting()
{
    if (isset($_POST['formStatus']) == 1){
    if ($_POST['formStatus'] == 'TRUE'){

      $participants = explode(',',$_POST['to']);

      $starttime = mktime(
        (int)substr($_POST['start_time'], -5, 2),
        (int)substr($_POST['start_time'], -2, 2),
        0,
        (int)substr($_POST['date'], -10, 2),
        (int)substr($_POST['date'], -7, 2),
        (int)substr($_POST['date'], -4, 4)
    );

      global $USER;
      global $DB;

      $activity = new stdClass;

      $activity->ref_id = $_POST['groupid'];

      $activity->ref_type = 2;
      $activity->updated = time();

      //Creates new activity ID
      $activity_id = $DB->insert_record("activities", $activity);

      //Inserts new meeting into 
      $newmeeting =  new stdClass;
      $newmeeting->activity_id = $activity_id;
      $newmeeting->timestamp = time();
      $newmeeting->create_by = $USER->id;

      $DB->insert_record("supervisor_meetings", $newmeeting); 

      //Creates events for each participants
      foreach ($participants as $userid) {
      $event = new stdClass;
      $event->name         = $_POST['title'];
      $event->description  = $_POST['description'];
      $event->courseid     = 0;
      $event->userid       = $userid;
      $event->modulename   = 'supervisor';
      $event->instance     = 5;
      $event->eventtype    = 'meeting';
      $event->timestart    = $starttime;
      $event->visible      = 1;
      $event->timeduration = ((int)substr($_POST['duration'], -5, 2)*60*60)+((int)substr($_POST['duration'], -2, 2)*60);

      $eventid = calendar_event::create($event)->id;//
      

      $newevent = new stdClass;
      $newevent->activity_id = $activity_id;
      $newevent->event_id = $eventid;
      $newevent->location = $_POST['location'];

      $DB->insert_record("supervisor_events", $newevent);
      }
      echo '<script type="text/javascript">
      alert("Meeting created!");
         </script>';
      
      $_POST['formStatus'] = 'FALSE'; //This is to avoid resubmitting after going into af new page and then going back
    }
  }
}

function actionEditMeeting()
{
  if (isset($_POST['editStatus']) == 1){
    if ($_POST['editStatus'] == 'TRUE'){
      global $USER;
      global $DB;
      $activityid = $_POST['activity_id'];
      $usersOLD = json_decode($_POST['usersBEFORE']); //userids that were there before the update
      $usersNOW = explode(',',$_POST['to2']); //the updated list of participant ids
      $usersNEW = array_diff($usersNOW, $usersOLD); //userids that needs to be added
      $usersDEL = array_diff($usersOLD, $usersNOW); //userids that needs to be deleted
      $eventsOLDidO = $DB->get_records("supervisor_events",array('activity_id'=>$activityid)); //old event id objects
      $eventsOLDO = []; //old event (moodle) objects
      $eventsOLD = []; //old events ids
      $eventsDEL = []; //ids of events that needs to be deleted
      $eventsEDIT = []; //ids of events that needs to be deleted

      $starttime = mktime(
            (int)substr($_POST['start_time2'], -5, 2),
            (int)substr($_POST['start_time2'], -2, 2),
            0,
            (int)substr($_POST['date2'], -10, 2),
            (int)substr($_POST['date2'], -7, 2),
            (int)substr($_POST['date2'], -4, 4)
        );

      //Finds all the current events
      foreach ($eventsOLDidO as $event) {
          //makes an array of current eventid's
          array_push($eventsOLD, $event->event_id);
          //makes an array of current eventobjects
          $eventOBJECT = $DB->get_record("event",array('id'=>$event->event_id));
          array_push($eventsOLDO, $eventOBJECT);
      }

      //Finds all the events that needs to be deleted
      foreach ($eventsOLDO as $event) {
          foreach ($usersDEL as $user) {
              if ($event->userid == $user) {
                  array_push($eventsDEL, $event->id);
              }
          }
      }

      //find all the events that needs to be edited
      $eventsEDIT = array_diff($eventsOLD, $eventsDEL);

      //removes the events in the database
      foreach ($eventsDEL as $eventid) {
          $DB->delete_records("event", array('id'=>$eventid));
          $DB->delete_records("supervisor_events", array('event_id'=>$eventid));
      }
      //Updates existing events

      foreach ($eventsEDIT as $eventid) {
          
          $event = new stdClass;
          $event->id = $eventid;
          $event->name = $_POST['title2'];
          $event->description = $_POST['description2']['text'];
          $event->timestart = $starttime;
          $event->timeduration = ((int)substr($_POST['duration2'], -5, 2)*60*60)+((int)substr($_POST['duration2'], -2, 2)*60);

          $DB->update_record("event", $event, $bulk=false);
          $supervisor_event = new stdClass;
          $supervisor_event->id = $DB->get_record("supervisor_events", array('event_id'=>$eventid))->id;
          $supervisor_event->location = $_POST['location2'];
          $DB->update_record("supervisor_events", $supervisor_event, $bulk=false);
      }

      //Create new events for new users
      foreach ($usersNEW as $userid) {
          $event = new stdClass;
          $event->name         = $_POST['title2'];
          $event->description  = $_POST['description2']['text'];
          $event->courseid     = 0;
          $event->userid       = $userid;
          $event->modulename   = 'supervisor';
          $event->instance     = 5;
          $event->eventtype    = 'meeting';
          $event->timestart    = $starttime;
          $event->visible      = 1;
          $event->timeduration = ((int)substr($_POST['duration2'], -5, 2)*60*60)+((int)substr($_POST['duration2'], -2, 2)*60);

          $eventid = calendar_event::create($event)->id;
          
          $newevent = new stdClass;
          $newevent->activity_id = $activityid;
          $newevent->event_id = $eventid;
          $newevent->location = $_POST['location2'];

          $DB->insert_record("supervisor_events", $newevent);
      }
          $editmeeting =  new stdClass;
          $editmeeting->id = $DB->get_record("supervisor_meetings", array('activity_id'=>$activityid))->id;
          $editmeeting->timestamp = time();
          $editmeeting->edit_by = $USER->id;

          $DB->update_record("supervisor_meetings", $editmeeting, $bulk=false);

          $activity = new stdClass;
          $activity->id = $activityid;
          $activity->updated = time();

          $DB->update_record("activities", $activity, $bulk=false);

          //Tells the user that meeting is created
          echo '<script type="text/javascript">
          alert("Meeting edited!");
             </script>';
          $_POST['editStatus'] = 'FALSE'; //This is to avoid resubmitting after going into af new page and then going back
      }
  }
}

//Created by another group. It was placed here by that same group.
function fetch_meetings_data($filter = array(), $group_id = '') 
{
  global $DB, $CFG, $USER;

  $where = "WHERE 1=1 ";
  $join = "";
  $group_or_course = false;
  $group_flag = "";
  if(count($filter) > 0) {
    foreach($filter AS $k=>$v) {
      switch($v['ident']) {
        case 'DATE': 
            if($v['type'] == '0') {
                $values = str_replace(' ', '', $v['args']);
                $values = explode(",", $values);
                $where .= "AND m.timestamp > '" . strtotime($values[0]) . "' AND m.timestamp < '" . strtotime($values[1]) . "' ";
            }
            elseif($v['type'] == '1') {
                $where .= "AND DATE(FROM_UNIXTIME(m.timestamp)) = '" . $v['args'] . "' ";
            }
            break;
          
        case 'TIMESTART':
            if($v['type'] == '0') {
                $values = str_replace(' ', '', $v['args']);
                $values = explode(",", $values);
                $where .= "AND e.timestart > '" . strtotime($values[0]) . "' AND m.timestart < '" . strtotime($values[1]) . "' ";
            }
            elseif($v['type'] == '1') {
                $where .= "AND DATE(FROM_UNIXTIME(e.timestart)) = '" . $v['args'] . "' ";
            }
            break;
          
        case 'TITLE': 
            if($v['type'] == '0') {
                $where .= "AND e.name LIKE '%" . $v['args'] . "%' ";
            }
            elseif($v['type'] == '1') {
                $where .= "AND e.name = '" . $v['args'] . "' ";
            }
            break;
          
        case 'COURSE_ID':
            if($v['type'] == '0') {
                $where .= "AND a.ref_type='1' AND a.ref_id = '" . $v['args'] . "'";
            }
            $group_or_course = true;
            break;
          
        case 'GROUP_ID':
            if($v['type'] == '0') {
              $where .= "AND a.ref_type='2' AND a.ref_id = '" . $v['args'] . "'";
            }
            $group_or_course = true;
            break;
      }
    }
  }
  
  if ($group_or_course) {
      $group_flag = "GROUP BY se.activity_id";
  }
  else {
      $group_flag = "AND e.userid='".$USER->id."'";
  }

  $d = array();

  $t = $DB->get_records_sql("SELECT m.id, m.timestamp, se.activity_id, se.event_id, e.timestart, e.timeduration, e.name, e.description, a.ref_id, a.ref_type FROM {supervisor_meetings} AS m JOIN {supervisor_events} AS se ON m.activity_id=se.activity_id JOIN {event} AS e ON se.event_id=e.id JOIN {activities} AS a ON a.id=m.activity_id $where AND e.timestart IS NOT NULL $group_flag ORDER BY e.timestart");
    
  if (is_array($t)) {
      foreach($t AS $k=>$v) {
          $te = $DB->get_records_sql("SELECT u.id, u.firstname, u.lastname, e.userid FROM {event} AS e JOIN {supervisor_events} AS se ON e.id=se.event_id JOIN {user} AS u ON u.id=e.userid WHERE se.activity_id='". $v->activity_id ."'");
                
          $users = array();
          if (is_array($te)) {
              foreach($te AS $uK => $uV) {
                $users[$uK] = array(
                  'firstname'   => $uV->firstname,
                  'lastname'    => $uV->lastname,
                );
              }
          }
          $status = ($v->timeduration/60) . ' ' . get_string('minutes_short', 'local_projectgroup');
          $day = date('j', $v->timestart);
          $month = date('n', $v->timestart);
          $year = date('Y', $v->timestart);
          if ($v->ref_id == $group_id && $v->ref_type == "2") {
              $link = "#activity:::".$v->activity_id;
          }
          else {
              $link = $CFG->wwwroot . '/calendar/view.php?view=day&cal_d='.$day.'&cal_m='.$month.'&cal_y='.$year.'#event_'.$v->id;
          }
          $d[$k] = array(
              'id'              => $v->id,
              'date'            => $v->timestart,
              'status'          => $status,
              'title'           => $v->name,
              'description'     => $v->description,
              'assigned_users'  => $users,
              'type_of'         => 'meeting',
              'color'           => '#FA468E',
              'link'            => $link,
          );
      }
    }
  return $d;
}

function secondsToTime($seconds)
{
  // extract hours
  $hours = floor($seconds / (60 * 60));

  // extract minutes
  $divisor_for_minutes = $seconds % (60 * 60);
  $minutes = floor($divisor_for_minutes / 60);

  // extract the remaining seconds
  $divisor_for_seconds = $divisor_for_minutes % 60;
  $seconds = ceil($divisor_for_seconds);

  // return the final array
  $obj = array(
      "h" => (int) $hours,
      "m" => (int) $minutes,
      "s" => (int) $seconds,
  );
  return $obj;
}

function cmp($a, $b)
{ 
  if($a->updated == $b->updated)
    return 0;
  return ($a->updated > $b->updated) ? 1 : -1;
}

function generateActivities($showFiles, $showMeetings, $showMessages, $displaynew, $filtercomments, $id, $showGroupNames = 0, $showTasks = 1)
{
  global $CFG;
  $rootstring = substr($CFG->dirroot, 8);
  
  //Saves a lot of work if nothing is to be displayed anyway.
  if($showFiles == 0 && $showMeetings == 0 && $showMessages == 0 && $showTasks == 0)
    return TRUE;

  global $DB;
  global $USER;
  $output = array();
  global $numberOfActivities;

  if(is_array($id))
  {
    if(($result = $DB->get_records_list("activities", "ref_id", $id)) == null)
      return FALSE;
  }
  else
  {
    if(($result = $DB->get_records("activities", array('ref_id'=>$id, 'ref_type'=>'2'))) == null)
      return FALSE;
  }

  if($filtercomments != 0)
  {
    usort($result,'cmp');
  }

  if($displaynew != 0)
  {
    $result = array_reverse($result);
  }

  foreach ($result as $key => $value) 
  {
    $comments = displayComments($value->id);

    if($showFiles != 0)
    {
      $upload = $DB->get_record("supervisor_uploads", array('activity_id'=>$value->id));
      if($upload != NULL)
      {
        $cell1 = new html_table_cell();
          $cell1->text = '
            <div style="float:right;"><strong style="position:relative;top:-10px;">Download: </strong><a href="' . $rootstring . '/blocks/upload/documents/'  . $upload->upload_path .   '"><img border="0" alt="download" src="../../blocks/groupwall/files/download.png" /></a></div>
            <strong>Uploaded by: </strong>'  . returnUsername($DB->get_record('user', array('id'=>$upload->create_by))) .     ' <strong>on: </strong>'  . userdate($upload->timestamp) .    '<br />
            <strong>Filename: </strong>' . $upload->upload_path . '<br /> <strong> Version </strong> ' . $upload->version . '<br/>
            ' . appendCommentField($value->id)
                          ;
        $row1 = new html_table_row();
          $row1->cells[] = $cell1;

        $session_table = new html_table();
        $session_table->head = array(returnTableHeader("File", $value->id, $USER->id, $upload->create_by, $showGroupNames));
        if($comments != null)
        {
          $session_table->data = array($row1, $comments);
        }
        else
        {
          $session_table->data = array($row1);
        }
        $session_table->width = "95%";
        $session_table->tablealign = "center";
        array_push($output, $session_table);

        $numberOfActivities++;;

      }
    }

    if($showMessages != 0)
    {
      $message = $DB->get_record("supervisor_messages", array('activity_id'=>$value->id));
      if($message != NULL)
      {
        $cell1 = new html_table_cell();
          $cell1->text = '
            <strong>Sender: </strong>'  . returnUsername($DB->get_record('user', array('id'=>$message->created_by))) . '<br />
            <strong>Time: </strong>'  . userdate($message->timestamp);
        $cell2 = new html_table_cell();
          $cell2->text = returnStringWithBrakers($message->text) . appendCommentField($value->id);
        $row1 = new html_table_row();
          $row1->cells[] = $cell1;
        $row2 = new html_table_row();
          $row2->cells = array($cell2);
        $session_table = new html_table();
        $session_table->head = array(returnTableHeader("Message", $value->id, $USER->id, $message->created_by, $showGroupNames));
          if($comments)
            $session_table->data = array($row1, $row2, $comments);
          else
            $session_table->data = array($row1, $row2);
        $session_table->width = "95%";
        $session_table->tablealign = "center";
        array_push($output, $session_table);

        $numberOfActivities++;
      }
    }

    if($showMeetings != 0)
    {
      $meeting = $DB->get_record("supervisor_meetings", array('activity_id'=>$value->id));
      $supEvent = $DB->get_records("supervisor_events", array('activity_id'=>$value->id));
      $participantsNames = array();
      $participantString = '';
      foreach ($supEvent as $valueEvent){
          $showEvent = $DB->get_record("event", array('id'=>$valueEvent->event_id));
          $participantsName = $DB->get_record("user", array('id'=>$showEvent->userid));
          $arrayPushVar = returnUsername($participantsName);
          array_push($participantsNames, $arrayPushVar);
      }
      $partCount = 0;
      foreach ($participantsNames as $partName) {
        $partCount++;
      }
      $endOfString = ', ';
      $forCount = 0;
      foreach ($participantsNames as $partName) {
        if ($partCount-1 == $forCount) { $endOfString = '.'; }
        $participantString .= $partName . $endOfString;
        $forCount++;
      }

      if($meeting != NULL)
      {
        $cell1 = new html_table_cell();
          $cell1->text = '
          <strong>Created by: </strong>'  . returnUsername($DB->get_record('user', array('id'=>$meeting->create_by))) . '<br />
          <strong>Created on: </strong>'  . userdate($meeting->timestamp) . '<br />
          <strong>Time: </strong>'  . date('Y-m-d H:i', $showEvent->timestart) . '<br />
          <strong>Duration: </strong>'  . secondsToTime($showEvent->timeduration)["h"] . ':' . secondsToTime($showEvent->timeduration)["m"] . '<br />
          <strong>Location: </strong>'  . end($supEvent)->location . '<br />
          <strong>Participants: </strong>'  . $participantString . '<br /><br />
          <strong>Notes: </strong><br />
          <div class="commentsdisplay">' . $showEvent->description . '</div>

                          ' . appendCommentField($value->id);
        $row1 = new html_table_row();
          $row1->cells[] = $cell1;

        $session_table = new html_table();
        $session_table->head = array(returnTableHeader(("Meeting - " . $showEvent->name), $value->id, $USER->id, $meeting->create_by, $showGroupNames));
        if($comments)
          $session_table->data = array($row1, $comments);
        else
          $session_table->data = array($row1);
        $session_table->width = "95%";
        $session_table->tablealign = "center";
        array_push($output, $session_table);

        $numberOfActivities++;
      }
    }

    if($showTasks != 0)
    {
      $task = $DB->get_record("tml_tasks", array('activity_id'=>$value->id));
      if($task != NULL)
      {
        $cell1 = new html_table_cell();
          $cell1->text .= '<strong>Sender: </strong>'  . returnUsername($DB->get_record('user', array('id'=>$task->created_by))) . '<br />';
          $cell1->text .= '<strong>Time: </strong>'  . userdate($task->timestamp) . '<br/><br/>';
          $cell1->text .= '<strong>Deadline: </strong>' . userdate($task->deadline) . '<br />';
          $cell1->text .= '<strong>Status: </strong>' . statusToString($task->status) . '<br />';
          $completed = "";
          if($task->completion_timestamp != null)
            $completed .= userdate($task->completion_timestamp);
          else
            $completed .= "Not completed yet";
          $cell1->text .= '<strong>Completed: </strong>' . $completed . '<br />';
          $assignedToString = "";
          if($assignedTo = $DB->get_records("tml_task_users", array('task_id'=>$task->id)))
            {
              foreach ($assignedTo as $key => $assignedToValue) 
              {
                $assignedToString .= returnUsername($assignedToValue->user_id) . ', ';
              }
              $assignedToString = substr($assignedToString, 0, -2);
              $assignedToString .= '.';
            }
          else
            $assignedToString = "No one";
          $cell1->text .= '<strong>Assigned to: </strong>' . $assignedToString;
          //$cell1->text .= '<strong></strong>' .
        $cell2 = new html_table_cell();
          $cell2->text = returnStringWithBrakers($task->description) . appendCommentField($value->id);
        $row1 = new html_table_row();
          $row1->cells[] = $cell1;
        $row2 = new html_table_row();
          $row2->cells = array($cell2);
        $session_table = new html_table();
        $session_table->head = array(returnTableHeader("Task - " . $task->title, $value->id, $USER->id, $task->created_by, $showGroupNames));
          if($comments)
            $session_table->data = array($row1, $row2, $comments);
          else
            $session_table->data = array($row1, $row2);
        $session_table->width = "95%";
        $session_table->tablealign = "center";
        array_push($output, $session_table);

        $numberOfActivities++;
      }
    }
  }
  return $output;
}

/**
* Used by the printActivities to determine the total number of pages shown.
* @param int How many entries is shown per page
* @return int number of pages on the Group-wall.
*/
function returnNumberOfPages($entriesPerPage, $nrOfActivities = 0)
{
  global $numberOfActivities;

  if($nrOfActivities != 0)
    $numberOfActivities = $nrOfActivities;

  return ceil($numberOfActivities / $entriesPerPage);
}

/**
* Prints a small number of the entries in a given array using the moodle html_writer or tables.
* @param int what page the user is currently viewing.
* @param array of moodle table objects.
* @param int optional entriesPerPage standard value is 10.
* @return string containing HTML-formatted by the moodle html writer.
*/
function printActivities($pagenumber, $output, $entriesPerPage = 10)
{
  global $numberOfActivities;
  $returnValue = "";

  $firstEntry = ($entriesPerPage * ($pagenumber - 1));
  $lastEntry = $firstEntry + $entriesPerPage;
  
  if($output != null)
  {
    foreach ($output as $key => $value) 
    {
      if($key >= $firstEntry && $key < $lastEntry)
      {
        $returnValue .= html_writer::table($value);
      }
    }
  }
  else
  {
    $cell1 = new html_table_cell();
      $cell1->text = 'There is nothing on the wall yet';
    $row1 = new html_table_row();
      $row1->cells[] = $cell1;

    $session_table = new html_table();
    $session_table->data = array($row1);
    $session_table->width = "95%";
    $session_table->tablealign = "center";
    $returnValue .= html_writer::table($session_table);
  }

  return $returnValue;
}

/**
* Returns the username of a user as a link to the user-profile using linkifyUsername.
* This function calls linkify with the correct parameter.
* @param Either user-id as an int or a moodle user-object.
* @return Username as a link.
*/
function returnUsername($user)
{
  global $CFG;
  global $DB;
  if (is_object($user)) 
    return linkifyUsername($user);
  else if(is_int($user))
  {
    return linkifyUsername($DB->get_record("user", array('id'=>$user)));
  }
  else
    return "Cannot find username";
}

/**
* Recieves a moodle user-object and returns a string containing a link to the user-profile.
* @param user Moodle user-object.
* @return HTML string showing the username and linkting to the corresponding profile page.
*/
function linkifyUsername($user)
{
  global $CFG;
  if (is_object($user)) 
    return('<a href="' . $CFG->wwwroot . '/user/profile.php?id=' . $user->id . '">' . $user->firstname . ' ' . $user->lastname . '</a>');
  else
    return "Cannot linkify username";
}

/**
* Generates the headline field for the html_writer used in generate activities.
* in order to append the correct buttons on the right hand side.
* @param headline string The text to be contained in the line.
* @param id int The activity_id of the post.
* @param userid int the id of the user who is logged in.
* @param ownerid int the id of the user who created the post.
* @param showGroupNames int Optional Value determining whether or not the name of the group containing the post should be printed. Standard is not (0).
* @return string the correct string to be inputted into a moodle table header property.
*/
function returnTableHeader($headline, $id, $userid, $ownerid, $showGroupNames = 0)
{
  global $CFG;
  global $DB;

  if($showGroupNames != 0)
  {
    $result = $DB->get_record("activities", array('id'=>$id));
    $headline .= '<br />Group: <a href=' . $CFG->wwwroot . '/local/projectgroup/index.php?id=' . $result->ref_id . '>' . get_projectgroup($result->ref_id)->shortname . '</a>';
  }

  $returnString = "";
  $returnString .= '
  <div style="float:right;">
    <form action="' . $_SERVER["REQUEST_URI"] . '" method="post">
    <input name="deleteId" id="deleteId" value="' . $id . '" type=hidden>
    <input type="image" width="20" src="../../blocks/groupwall/files/delete.png" alt="Delete" name="deleteId" id="deleteId" value="' . $id . '"/>
    </form>
  </div>';

  if(strstr($headline, "Meeting"))
  {
    $returnString .= '
    <div style="float:right;margin:-right:5px;">
      <form action="'  . $CFG->wwwroot . '/blocks/upload/edit_meeting.php" method="post">
      <input name="rememberPath" value="' . $_SERVER["REQUEST_URI"] . '" type=hidden>
      <input name="editId" id="editId" value="' . $id . '" type=hidden>
      <input type="image" width="20" src="../../blocks/groupwall/files/edit.png" alt="Edit" name="editId" id="editId" value="' . $id . '"/>
      </form>
    </div>';
  }

  $returnString .= '<div style="text-align:left;"><a name="activity:::' . $id . '" style="cursor:default;">' . $headline . '</a></div>';

  if($ownerid == $userid)
  {
    return $returnString;
  }
  else
  {
    return '<div style="text-align:left;">' . $headline . '</div>';
  }
}

/**
* Appends the delete button to comment if the user has permission to delete it.
* @param posterId int the id of the user who created the comment
* @param userId int the id of the user currently logged in
* @param commentActivityId int the activity_id of the comment in question.
*/
function displayCommentsDelete($posterId, $userId, $commentActivityId)
{
  global $DB;
  global $CFG;

  $result = $DB->get_records("supervisor_comments", array('belongs_to'=>$commentActivityId));

  //The commentfield should only be display of the comment does not have any comments belonging to it.
  if($result == null)
  {
    if($posterId == $userId)
    {
      return '
      <form action="' . $_SERVER["REQUEST_URI"] . '" method="post">
      <input type="image" width="15" src="' . $CFG->wwwroot . '/blocks/groupwall/files/delete.png" alt="Delete" name="deleteId" id="deleteId" value="' . $commentActivityId . '"/>
      </form>
              ';
    }
  }
}

/**
* Deletes an entry in the activities table and everywhere else it might exist
* @param int id of the 
* @return TRUE on success.
*/
function deleteEntry($id)
{
  global $DB;
  global $CFG;
  $output = array();

  if($DB->delete_records("activities", array('id'=>$id)))
  {
    //Deletes the files, if it exists
    if($fileEntry = $DB->get_record("supervisor_uploads", array('activity_id'=>$id)))
    {
      unlink($CFG->dirroot . "/blocks/upload/documents/" . $fileEntry->upload_path);
      $DB->delete_records("supervisor_uploads", array('activity_id'=>$id));
    }

    //Delete messages
    $DB->delete_records("supervisor_messages", array('activity_id'=>$id));
    
    //Calls this function recursively in order to delete all comments
    if($result = $DB->get_records("supervisor_comments", array('belongs_to'=>$id)))
    {
      foreach ($result as $key => $value) 
      {
        deleteEntry($value->activity_id);
      }
    }

    //Deletes the comments
    $DB->delete_records("supervisor_comments", array('activity_id'=>$id));

    //Deletes events in all the tables it's been stored.
    if($result = $DB->get_records("supervisor_events", array('activity_id'=>$id)))
    {
      foreach ($result as $key => $value) 
      {
        $DB->delete_records("event", array('id'=>$value->event_id));
      }
      $DB->delete_records("supervisor_events", array('activity_id'=>$id));
    }
    $DB->delete_records("supervisor_meetings", array('activity_id'=>$id));

    array_push($output, "Entry " . $id . " deleted");
    echo divWrapper($output, "accepted");
    return TRUE;
  }
  else
    return FALSE;
}

/**
* Returns a string with <br /> tags instead of ASCII newline characters.
* @param string The text to be evaluated
* @return string string with <br /> tags
*/
function returnStringWithBrakers($text)
{
  $stringWithBrakers = "";
  if($text != null)
  {
    while ($text != null)
    {
      if(ord($text) == 10)
      {
        $stringWithBrakers = $stringWithBrakers . "<br />";
      }
      elseif (ord($text) == 13) 
      {
        //Nothing to do here. ASCII 13 is unique to Windows and Unix(MAC OS). 10 is always used.
      }
      else
      {
        $stringWithBrakers = $stringWithBrakers . substr($text, 0, 1);
      }

      $text = substr($text, 1);
    }

    return $stringWithBrakers;
  }
  else
    return "";
}

/**
* Used to append the comment field to entries on the wall.
* @param int id of the post to append the field to
* @return string
*/
function appendCommentField($id)
{
  if(is_int((int)$id) && $id != 0)
    return '
        <div class="content-box">
          <div class="static">
            <div class="respond">
              <img alt="respond" border="0" width="20" src="../../blocks/groupwall/files/plus.png" />
            </div>
          </div>
          <div class="dynamic">
            <form action="' . $_SERVER["REQUEST_URI"] . '" method="post">
            <textarea type="text" name="comment" id="comment" cols="50" rows="5"></textarea>
            <input type="hidden" name="commentOnId" id="commentOnId" value="' . $id . '"/>
            <input type="submit" />
            </form>
          </div>
        </div>
            ';
  else
    return false;
}

/**
* displays all comments on a post recursively (also displays comments on the comment)
* @param int id of the post in question
* @return array consisting of formatted HTML or null on failture
*/
function displayComments($id)
{
  global $DB;
  global $USER;

  if($result = $DB->get_records("supervisor_comments", array('belongs_to'=>$id)));
  {
    $returnValue = array();
    $string = "";

    foreach ($result as $key => $value) 
    {
      $string .= '<div class="commentHeadline"><div style="float:right;position:relative;top:-2px;">' . displayCommentsDelete($value->create_by, $USER->id, $value->activity_id) . '</div>Posted by: <strong>' . returnUsername($DB->get_record('user', array('id'=>$value->create_by))) . ' </strong> on <strong>' . userdate($value->timestamp)  . '</strong></div>';
      $string .= '<div class="comment"><div class="marginToNextComment">';
      $string .= returnStringWithBrakers(returnStringWithoutHTMLtags($value->text));
      $string .= appendCommentField($value->activity_id);
      $string .= '</div>';
      $newComment = displayComments($value->activity_id);
      $string .= $newComment[0];
      $string .= "</div>";
    }

    array_push($returnValue, $string);

    if($returnValue[0] == null)
      return null;
    else
      return $returnValue;
  }

  return FALSE;
}

/**
* Wraps text in a HTML div-tag.
* @param array of lines to wrap
* @param type string "failure", "error", "accepted" or "success" determines the background color. Default if 0 and color is white.
* @return string
*/
function divWrapper($array, $type = 0)
{
  switch ($type) 
  {
    case "failure":
    case "error":
      $red    = 255;
      $green  = 50;
      $blue   = 50;
      break;
    case "accepted":
    case "success":
      $red    = 50;
      $green  = 255;
      $blue   = 50;
      break;
    default:
      $red    = 255;
      $green  = 255;
      $blue   = 255;
      break;
  }
  $returnvalue = "";
  $returnvalue .= '<div style="         
                            width:93%;
                            margin-bottom: 20px;
                            margin-left:auto;
                            margin-right:auto;
                            background-color:rgba(' . $red . ',' . $green . ',' . $blue . ',0.5);
                            padding:5px;
                            border-style:solid;
                            border-width:1px;
                            border-radius:1px;
                            ">';
  if(is_array($array))
  {
    foreach ($array as $value) 
    {
      $returnvalue = $returnvalue . $value;
    }
  }
  else if(is_string($array))
    $returnvalue = $returnvalue . $array;
  else
    $returnvalue = "Input to notification was not an array nor a string";

  $returnvalue .= '</div>';

  return $returnvalue;
}

/**
* Saves an inputted comment and echoes a confirmation on success.
* calls updateUpdatedPropertyOnAllParentEntries to update the updated property, thus making sure the content is displayed correctly on the wall.
* @param int id of the entry commented on
* @param string text contained in the comment
* @return FALSE/TRUE
*/
function saveComment($id, $text)
{
  global $DB;
  global $USER;
  $output = array();

  if($text == null)
  {
    $_SESSION['saveCommentResult'] = 1;
    return FALSE;
  }

  $fileinfo = new stdClass;
  $fileinfo->ref_id = 0;
  $fileinfo->ref_type = 2;
  $fileinfo->updated = time();

  $returnid = $DB->insert_record("activities", $fileinfo);

  $activityinfo = new stdClass;
  $activityinfo->activity_id = $returnid;
  $activityinfo->belongs_to = $id;
  $activityinfo->timestamp = time();
  $activityinfo->create_by = $USER->id;
  $activityinfo->text = $text;

  if($DB->insert_record("supervisor_comments", $activityinfo))
  {
    updateUpdatedPropertyOnAllParentEntries($activityinfo->belongs_to);
    $_SESSION['saveCommentResult'] =  2;
    return TRUE;
  }
  else
    return FALSE;
}

/**
* Recursively updates the updated property on itself and all parent entries
* @param int id on the post to be updated
* @return TRUE/FALSE
*/
function updateUpdatedPropertyOnAllParentEntries($id)
{
  global $DB;

  if($result = $DB->get_records("activities", array('id'=>$id)))
  {
    foreach ($result as $key => $value) 
    {
      /*The moodle documentation does not explain whether or not the update_record
      overwrites object properties that is not set. Because of this we load them all again 
      and updates the entry */
      $dataObject = new stdClass;
      $dataObject->id = $value->id;
      $dataObject->ref_id = $value->ref_id;
      $dataObject->ref_type = $value->ref_type;
      $dataObject->updated = time();
      
      $DB->update_record("activities", $dataObject);

      if($result = $DB->get_records("supervisor_comments", array('activity_id'=>$value->id)))
      {
        foreach ($result as $key => $value) 
        {
          //In order to put the entries on the top of the wall we only need to update one parent, thus the break;
          updateUpdatedPropertyOnAllParentEntries($value->belongs_to);
          break;
        }
      }
      else
        return True;

      break;
    }
  }
  else
    return FALSE;
}

/**
* Prints the confirmation messages set by the upload block and unsets the SESSION variables
*/
function printconfirmationmessages()
{
  if(isset($_SESSION['messageSaved']))
  {
    if($_SESSION['messageSaved'] === TRUE)
    {
      echo divWrapper(['New message saved'], "success");
    }
    else if ($_SESSION['messageSaved'] = "noMessage") 
    {
      echo divWrapper(['No message was inputted'], "error");
    }
    else
    {
      echo divWrapper(['An error occurred. The messages was not saved.'], "error");
    }
    unset($_SESSION['messageSaved']);
  }

  if(isset($_SESSION['saveCommentResult']))
  {
    if($_SESSION['saveCommentResult'] == 1)
      echo divWrapper(['No Message was inputted'], "error");
    if($_SESSION['saveCommentResult'] == 2)
      echo divWrapper(['New comment saved!'], "success");
    unset($_SESSION['saveCommentResult']);
  }

  if(isset($_SESSION['fileUploadResult']))
  {
    if($_SESSION['fileUploadResult'] == 1)
      echo divWrapper(['The uploaded file exceeds the upload_max_filesize directive in php.ini.'], "failure");

    if ($_SESSION['fileUploadResult'] == 2) 
      echo divWrapper(['The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'], "failure");

    if($_SESSION['fileUploadResult'] == 3)
      echo divWrapper(['The uploaded file was only partially uploaded. Try again.'], "failure");

    if($_SESSION['fileUploadResult'] == 4)
      echo divWrapper(['No file selected'], "failure");

    if($_SESSION['fileUploadResult'] == 6)
      echo divWrapper(['Temporary folder is missing. Contact system administrator.'], "failure");

    if($_SESSION['fileUploadResult'] == 7)
      echo divWrapper(['Failed to write file to disk. Contact system administrator.'], "failure");

    if($_SESSION['fileUploadResult'] == 8)
      echo divWrapper(['A PHP extension stopped the file upload.'], "failure");

    if($_SESSION['fileUploadResult'] == "unknown")
      echo divWrapper(['An unknown error occurred'], "failure");

    if($_SESSION['fileUploadResult'] == "noFolderCreated")
      echo divWrapper(['Folder does not exists and could not be created'], "failure");

    if($_SESSION['fileUploadResult'] == "dbError")
      echo divWrapper(['File was uploaded but not saved to database'], "failure");

    if($_SESSION['fileUploadResult'] == "success")
    {
      $output = array();
      array_push($output, "File was uploaded <br />");
      array_push($output, "Size: " . $_SESSION['fileUploadResultsize']);
      echo divWrapper($output, "success");
    }
    unset($_SESSION['fileUploadResult']);
  }
}

/**
* Generates a string based on ASCII characters, that does not contain any HTML start or end tags.
* @param string text
* @return string
*/
function returnStringWithoutHTMLtags($text)
{
  $stringWithoutTagSigns = "";
  if($text != null)
  {
    while ($text != null)
    {
      if(ord($text) == 60)
      {
        $stringWithoutTagSigns .= "&lt";
      }
      elseif (ord($text) == 62) 
      {
        $stringWithoutTagSigns .= "&gt";
      }
      else
      {
        $stringWithoutTagSigns .= substr($text, 0, 1);
      }

      $text = substr($text, 1);
    }

    return $stringWithoutTagSigns;
  }
  else
    return "";
}
