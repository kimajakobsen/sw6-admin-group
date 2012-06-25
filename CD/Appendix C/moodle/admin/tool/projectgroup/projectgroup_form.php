<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));

class projectgroup_form extends moodleform {
    protected $projectgroup;
    

    function definition() {
        global $USER, $CFG, $DB, $SESSION;

        $mform    = $this->_form;
        $acount =& $this->_customdata['acount'];
        $scount =& $this->_customdata['scount'];
        $ausers =& $this->_customdata['ausers'];
        $susers =& $this->_customdata['susers'];
        $total  =& $this->_customdata['total'];
        $fields  =& $this->_customdata['fields'];
//--------------------------------------------------------------------------------

        $fields      = $this->_customdata['fields'];
        

        $mform->addElement('header', 'newfilter', 'Search filters'); //get_string('newfilter','filters')

        foreach($fields as $ft) {
            $ft->setupForm($mform);
        }

        
       
        // Add button
        $mform->addElement('submit', 'addfilter', get_string('addfilter','filters'));

        // Don't use last advanced state
        $mform->setShowAdvanced(false);
       
        if (!empty($SESSION->projectgroup_filtering)) {
            // add controls for each active filter in the active filters group
            $mform->addElement('header', 'actfilterhdr', get_string('actfilterhdr','filters'));

            foreach ($SESSION->projectgroup_filtering as $fname=>$datas) {
                if (!array_key_exists($fname, $fields)) {
                    continue; // filter not used
                }
                $field = $fields[$fname];
                foreach($datas as $i=>$data) {
                    $description = $field->get_label($data);
                    $mform->addElement('checkbox', 'filter['.$fname.']['.$i.']', null, $description);
                }
            }

          

            $objs = array();
            $objs[] = &$mform->createElement('submit', 'removeselectedfilters', get_string('removeselected','filters'));
            $objs[] = &$mform->createElement('submit', 'removeallfilters', get_string('removeall','filters'));
            $mform->addElement('group', 'actfiltergrp', '', $objs, ' ', false);
        }
    
//--------------------------------------------------------------------------------
        if(!empty($projectgroup->id)){
            $mform->addElement('hidden', 'id', null);
            $mform->setType('id', PARAM_INT);
        }

/// finally set the current form data
//--------------------------------------------------------------------------------
        //$this->set_data($projectgroup);
        
    }


/// perform some extra moodle validation
    function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        /*
        if ($foundcourses = $DB->get_records('course', array('shortname'=>$data['shortname']))) {
            if (!empty($data['id'])) {
                unset($foundcourses[$data['id']]);
            }
            if (!empty($foundcourses)) {
                foreach ($foundcourses as $foundcourse) {
                    $foundcoursenames[] = $foundcourse->fullname;
                }
                $foundcoursenamestring = implode(',', $foundcoursenames);
                $errors['shortname']= get_string('shortnametaken', '', $foundcoursenamestring);
            }
        }
        
        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));
*/
        return $errors;
    }
}

