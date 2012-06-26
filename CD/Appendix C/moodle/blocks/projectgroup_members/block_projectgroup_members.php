<?php

require_once $CFG->dirroot.'/local/projectgroup/lib.php';

/**
 * Form for editing HTML block instances.
 *
 * @package   block_html
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_projectgroup_members extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_projectgroup_members');
    }

    function applicable_formats() {
        return array('local-projectgroup' => true);
    }

    function instance_allow_multiple() {
        return true;
    }
    
    function get_content() {
        if($this->content != null)
        {
            return $this->content;
        }
        global $CFG;
        global $DB;
        global $OUTPUT;
        $this->content = new stdClass();
        
        $groupId = $this->get_projectgroup_id();
        $memberIds = get_members_of_group($groupId);
        
        $memberObjects = array();
        
        $count = 0;
        foreach ($memberIds as $id) {
            $memberObjects[$count] = $DB->get_record('user', array('id'=>$id));
            $count++;
        }
        $size = 60;
        $this->content->text ='<style>
            ul#grp-mem {
                margin:0 auto;
                padding:0;
                list-style:none;
                width:80%;
            }
            ul#grp-mem li {
                width:30%;
                height:100px;
                padding:10px;
                display:inline-block;
            }
            ul#grp-mem img {
                height:'.$size.'px;
                width:'.$size.'px;
            }
            </style><ul id="grp-mem">';
        foreach($memberObjects as $member){
            $this->content->text.= '<li>' . $OUTPUT->user_picture($member, array('size' => $size)). '<div>' .$member->firstname . ' ' . $member->lastname . '</div></li>';
        }
        $this->content->text.='</ul>';
        return $this->content;
    }

    /**
     * The block should only be dockable when the title of the block is not empty
     * and when parent allows docking.
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return false;
    }
    
    private function get_projectgroup_id() 
    {
        if($this->page->context->contextlevel == "55") {
            return $this->page->context->getProjectGroupId();
        }
        
        throw new coding_exception('Trying to get projectgroup id when not in projectgroup context');
    }
}
