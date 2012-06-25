<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));

/**
 * Edit form for project groups.
 * Inspired by other form (course)
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
class projectgroup_edit_form extends moodleform {
    protected $projectgroup;
    
    /**
     * 
     */
    function definition() {
        global $USER, $CFG, $DB, $SESSION;

        $mform    = $this->_form;
        $acount =& $this->_customdata['acount'];
        $scount =& $this->_customdata['scount'];
        $ausers =& $this->_customdata['ausers'];
        $susers =& $this->_customdata['susers'];
        $total  =& $this->_customdata['total'];
        $advisors   =& $this->_customdata['advisors'];
        $fields  =& $this->_customdata['fields'];
        $projectgroup        = $this->_customdata['projectgroup']; // this contains the data of this form

//--------------------------------------------------------------------------------
        $mform->addElement('header','general', get_string('general', 'form'));

    

       

        $mform->addElement('text','fullname', get_string('fullname', 'tool_projectgroup'),'maxlength="254" size="50"');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);
      

        $mform->addElement('text', 'shortname', get_string('shortname', 'tool_projectgroup'), 'maxlength="30" size="20"');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);
        

        $fields      = $this->_customdata['fields'];
        

        $mform->addElement('header', 'newfilter', get_string('newfilter','filters'));

        foreach($fields as $ft) {
            $ft->setupForm($mform);
        }

        
       
        // Add button
        $mform->addElement('submit', 'addfilter', get_string('addfilter','filters'),array('onclick'=>'skipClientValidation = true; return true;'));

        // Don't use last advanced state
        $mform->setShowAdvanced(false);
        
       
        if (!empty($SESSION->user_filtering)) {
            // add controls for each active filter in the active filters group
            $mform->addElement('header', 'actfilterhdr', get_string('actfilterhdr','filters'));

            foreach ($SESSION->user_filtering as $fname=>$datas) {
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
        $mform->addElement('header', 'users', get_string('usersinlist', 'bulkusers'));
        $achoices = array();
        $schoices = array();

        if (is_array($ausers)) {
            if ($total == $acount) {
                $achoices[0] = get_string('allusers', 'bulkusers', $total);
            } else {
                $a = new stdClass();
                $a->total  = $total;
                $a->count = $acount;
                $achoices[0] = get_string('allfilteredusers', 'bulkusers', $a);
            }
            $achoices = $achoices + $ausers;

            if ($acount > MAX_BULK_USERS) {
                $achoices[-1] = '...';
            }

        } else {
            $achoices[-1] = get_string('nofilteredusers', 'bulkusers', $total);
        }

        if (is_array($susers)) {
            $a = new stdClass();
            $a->total  = $total;
            $a->count = $scount;
            $schoices[0] = get_string('allselectedusers', 'bulkusers', $a);
            $schoices = $schoices + $susers;

            if ($scount > MAX_BULK_USERS) {
                $schoices[-1] = '...';
            }

        } else {
            $schoices[-1] = get_string('noselectedusers', 'bulkusers');
        }
        
        $objs = array();
        $objs[0] =& $mform->createElement('select', 'ausers', get_string('available', 'bulkusers'), $achoices, 'size="15"');
        $objs[0]->setMultiple(true);
        $objs[1] =& $mform->createElement('select', 'susers', get_string('selected', 'bulkusers'), $schoices, 'size="15"');
        $objs[1]->setMultiple(true);
        
      
        $grp =& $mform->addElement('group', 'usersgrp', get_string('users', 'bulkusers'), $objs, ' ', false);
        $mform->addHelpButton('usersgrp', 'users', 'bulkusers');
        
        
        
        $objs = array();
        $objs[] =& $mform->createElement('submit', 'addsel', get_string('addsel', 'bulkusers'));
        $objs[] =& $mform->createElement('submit', 'removesel', get_string('removesel', 'bulkusers'));
        $objs[] =& $mform->createElement('submit', 'addall', get_string('addall', 'bulkusers'));
        $objs[] =& $mform->createElement('submit', 'removeall', get_string('removeall', 'bulkusers'));
        $grp =& $mform->addElement('group', 'buttonsgrp', get_string('selectedlist', 'bulkusers'), $objs, array(' ', '<br />'), false);
        $mform->addHelpButton('buttonsgrp', 'selectedlist', 'bulkusers');
        
        $roleArray = $schoices;
        $roleArray[0] = "None";
        unset($roleArray[-1]);
        $mform->addElement('header','roleheader', get_string('advisors', 'tool_projectgroup'));
        $advisors =& $mform->AddElement('select', 'advisors', get_string('selectrole', 'tool_projectgroup'), $roleArray, 'size="15"');
        $advisors->setMultiple(TRUE);
        if(!function_exists('callbackToProjectGroupMapper'))
        {
            function callbackToProjectGroupMapper($elem) {
                if(isset($elem->role) && isset($elem->user)) {
                    if($elem->role == 1) {
                        return $elem->user;
                    }
                }
                return null;
            }
        }
        if(is_array($projectgroup->members))
        {
            $advisorsId = array_map('callbackToProjectGroupMapper', $projectgroup->members);
            $advisors->setSelected(array_unique($advisorsId));
        }
        $mform->setType('advisors', PARAM_INT);
        
       $renderer =& $mform->defaultRenderer();
        $template = '<label class="qflabel" style="vertical-align:top">{label}</label> {element}';
        $renderer->setGroupElementTemplate($template, 'usersgrp');

//--------------------------------------------------------------------------------
        $this->add_action_buttons();
//--------------------------------------------------------------------------------
        if(!empty($projectgroup->id)){
            $mform->addElement('hidden', 'id', null);
            $mform->setType('id', PARAM_INT);
        }

/// finally set the current form data
//--------------------------------------------------------------------------------
        $this->set_data($projectgroup);
        
    }
}

