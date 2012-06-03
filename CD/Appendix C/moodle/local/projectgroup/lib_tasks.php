<?
function fetch_tasks_data($filter=array()) {
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
						$where .= "AND t.timestamp > '" . strtotime($values[0]) . "' AND t.timestamp < '" . strtotime($values[1]) . "' ";
					}
					elseif($v['type'] == '1') {
						$where .= "AND DATE(FROM_UNIXTIME(t.timestamp)) = '" . $v['args'] . "' ";
					}
					break;


				case 'DEADLINE':
					if($v['type'] == '0') {
					    $values = str_replace(' ', '', $v['args']);
					    $values = explode(",", $values);
						$where .= "AND t.deadline > '" . strtotime($values[0]) . "' AND t.deadline < '" . strtotime($values[1]) . "' ";
					}
					elseif($v['type'] == '1') {
						$where .= "AND DATE(FROM_UNIXTIME(t.deadline)) = '" . $v['args'] . "' ";
					}
					break;

				case 'COMPLETIONDATE': 
    				if($v['type'] == '0') {
    				    $values = str_replace(' ', '', $v['args']);
    				    $values = explode(",", $values);
    					$where .= "AND t.completion_timestamp > '" . strtotime($values[0]) . "' AND t.completion_timestamp < '" . strtotime($values[1]) . "' ";
    				}
    				elseif($v['type'] == '1') {
    					$where .= "AND DATE(FROM_UNIXTIME(t.completion_timestamp)) = '" . $v['args'] . "' ";
    				}
    				break;


				case 'TITLE': 
					if($v['type'] == '0') {
						$where .= "AND t.title LIKE '%" . $v['args'] . "%' ";
					}
					elseif($v['type'] == '1') {
						$where .= "AND t.title = '" . $v['args'] . "' ";
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
	    $group_flag = "";
	}
	else {
	    $join .= " JOIN {tml_task_users} AS u ON u.task_id=t.id ";
	    $group_flag = " AND u.user_id='".$USER->id."'";
	}

	$d = array();
    $t = $DB->get_records_sql(" SELECT t.id, t.deadline, t.status, t.title, t.description, t.type, ty.id as type_id, a.ref_id, a.ref_type, ty.name, ty.color 
                                FROM {tml_tasks} AS t JOIN {tml_task_types} AS ty ON t.type=ty.id 
                                JOIN {activities} AS a ON a.id=t.activity_id $join $where AND t.deadline IS NOT NULL $group_flag 
                                ORDER BY t.deadline");

	if (is_array($t)) {
	    foreach($t AS $k=>$v) {
	    	$tu = $DB->get_records_sql("SELECT u.id, u.firstname, u.lastname FROM {user} AS u JOIN {tml_task_users} AS tu ON tu.user_id=u.id WHERE tu.task_id='" . $k . "'");

	    	$users = array();
	    	if (is_array($tu)) {
	    	    foreach($tu AS $uK => $uV) {
	    	    	$users[$uK] = array(
	    	    		'firstname'		=> $uV->firstname,
	    	    		'lastname'		=> $uV->lastname,
	    	    	);
	    	    }
	        }
	        
	        if ($v->deadline < time() && $v->status != 4 && !empty($v->deadline)) {
	            $status = 5;
	        }
	        else {
	            $status = $v->status;
	        }
	        
	        if($status != 4){
                $thirdbutton = '<a title="' . get_string("complete_task", "block_tasks") . '" href="'. $CFG->wwwroot .'/blocks/tasks/task_list.php?complete=1&tid='.$v->id.'&redirect=1"><img src="'.$CFG->wwwroot .'/pix/i/tick_green_big.gif" class="iconsmall" alt="' . get_string("complete_task", "block_tasks") . '" /></a>';
            } else {
                $thirdbutton = '<a title="' . get_string("uncomplete_task", "block_tasks") . '" href="'. $CFG->wwwroot .'/blocks/tasks/task_list.php?uncomplete=1&tid='.$v->id.'&redirect=1"><img src="'. $CFG->wwwroot .'/blocks/tasks/minus_red.gif" class="iconsmall" alt="' . get_string("uncomplete_task", "block_tasks") . '" /></a>';
            }
	        
	    	$d[$k] = array(
	    	    'id'                => $v->id,
	    		'date'			    => $v->deadline,
	    		'status'			=> statusToString($status),
	    		'title'				=> $v->title,
	    		'description'		=> $v->description,
	    		'assigned_users'	=> $users,
	    		'type_of'           => 'task',
	    		'color'             => $v->color,
	    		'link'              => $CFG->wwwroot . '/blocks/tasks/edit_tasks.php?tid=' . $v->id,
	    		'unique'            => '<a title="' . get_string("task_delete_button", "block_tasks") . '" href="'. $CFG->wwwroot .'/blocks/tasks/task_list.php?delete=1&tid='.$v->id.'&redirect=1"><img src="'.$CFG->wwwroot .'/pix/t/delete.gif" class="iconsmall" alt="' . get_string("task_delete_button", "block_tasks") . '" /></a>' . $thirdbutton,
	    	);
	    }
    }
	return $d;
}

/**
 * Return a human readable string based on a predefined key (id).
 *
 * @param integer $id
 * @return string from the get_string() function
 */
function statusToString($id) {
	switch($id) {
		case 1: 
		    return get_string('status_notstarted', 'local_projectgroup');
		case 2: 
		    return get_string('status_inprogress', 'local_projectgroup');
		case 3: 
		    return get_string('status_onhold', 'local_projectgroup');
		case 4: 
		    return get_string('status_completed', 'local_projectgroup');
		case 5:
		    return get_string('status_overdue', 'local_projectgroup');
		default:
		    return get_string('status_invalid', 'local_projectgroup');
	}
}