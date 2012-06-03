<?
function fetch_blackboard_data($filter=array()) {
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
						$where .= "AND b.timecreated > '" . strtotime($values[0]) . "' AND b.timecreated < '" . strtotime($values[1]) . "' ";
					}
					elseif($v['type'] == '1') {
						$where .= "AND DATE(FROM_UNIXTIME(b.timecreated)) = '" . $v['args'] . "' ";
					}
					break;

				case 'TITLE': 
					if($v['type'] == '0') {
						$where .= "AND b.title LIKE '%" . $v['args'] . "%' ";
					}
					elseif($v['type'] == '1') {
						$where .= "AND b.title = '" . $v['args'] . "' ";
					}
					break;

				/*case 'COURSE_ID':
					if($v['type'] == '0') {
						$join .= "JOIN {activities} AS a ON a.id=t.activity_id";
						$where .= "AND a.ref_type='1' AND a.ref_id = '" . $v['args'] . "'";
					}
					$group_or_course = true;
					break;*/
					
	            case 'GROUP_ID':
    				if($v['type'] == '0') {
    					$where .= "AND b.projectid='" . $v['args'] . "' ";
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
	    $join .= " JOIN {projectgroup_members} AS pm ON pm.projectgroup=b.projectid ";
	    $group_flag = " AND pm.user='".$USER->id."'";
	}

	$d = array();

	$t = $DB->get_records_sql("SELECT b.id, b.projectid, b.title, b.timecreated, b.timemodified FROM {block_blackboard} AS b $join $where $group_flag ORDER BY b.timecreated");

	if (is_array($t)) {
	    foreach($t AS $k=>$v) {
	        $users = array();
	        $group_users = get_members_of_group($v->projectid);
	    	$tu = $DB->get_records_list('user','id',$group_users);
	    	if (is_array($tu)) {
	    	    foreach($tu AS $uK => $uV) {
	    	    	$users[$uK] = array(
	    	    		'firstname'		=> $uV->firstname,
	    	    		'lastname'		=> $uV->lastname,
	    	    	);
	    	    }
	        }
	    	$d[$k] = array(
	    	    'id'                => $v->id,
	    		'date'			    => $v->timecreated,
	    		'status'			=> '',
	    		'title'				=> $v->title,
	    		'description'		=> '',
	    		'assigned_users'	=> $users,
	    		'type_of'           => 'blackboard',
	    		'color'             => '#77C7FC',
	    		'link'              => $CFG->wwwroot . '/blocks/blackboard/blackboard.php?id=' . $v->id,
	    	);
	    }
    }
	return $d;
}