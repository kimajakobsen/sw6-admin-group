<?php

require_once($CFG->dirroot.'/user/filters/text.php');
require_once($CFG->dirroot.'/user/filters/date.php');
require_once($CFG->dirroot.'/user/filters/select.php');
require_once($CFG->dirroot.'/user/filters/simpleselect.php');
require_once($CFG->dirroot.'/user/filters/courserole.php');
require_once($CFG->dirroot.'/user/filters/globalrole.php');
require_once($CFG->dirroot.'/user/filters/profilefield.php');
require_once($CFG->dirroot.'/user/filters/yesno.php');
require_once($CFG->dirroot.'/user/filters/cohort.php');
require_once($CFG->dirroot.'/user/filters/user_filter_forms.php');
require_once($CFG->dirroot.'/user/filters/checkbox.php');
require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));

/**
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 * 
 */
class projectgroup_filtering {
    var $_fields;
    var $_addform;
    var $_activeform;
    
    /**
     * Contructor
     * @param array array of visible user fields
     * @param string base url used for submission/return, null if the same of current page
     * @param array extra page parameters
     */
    function projectgroup_filtering($fieldnames=null, $baseurl=null, $extraparams=null) {
        global $SESSION;

        if (!isset($SESSION->projectgroup_filtering)) {
            $SESSION->projectgroup_filtering = array();
        }

        if (empty($fieldnames)) {
            $fieldnames = array('shortname'=>0,'longname'=>1);
        }

        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }
        

        // fist the new filter form
        $this->_addform = new user_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        if ($adddata = $this->_addform->get_data()) {
            foreach($this->_fields as $fname=>$field) {
                $data = $field->check_data($adddata);
                if ($data === false) {
                    continue; // nothing new
                }
                if (!array_key_exists($fname, $SESSION->projectgroup_filtering)) {
                    $SESSION->projectgroup_filtering[$fname] = array();
                }
                $SESSION->projectgroup_filtering[$fname][] = $data;
            }
            // clear the form
            $_POST = array();
            $this->_addform = new user_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }

        // now the active filters
        $this->_activeform = new user_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        if ($adddata = $this->_activeform->get_data()) {
            if (!empty($adddata->removeall)) {
                $SESSION->projectgroup_filtering = array();

            } else if (!empty($adddata->removeselected) and !empty($adddata->filter)) {
                foreach($adddata->filter as $fname=>$instances) {
                    foreach ($instances as $i=>$val) {
                        if (empty($val)) {
                            continue;
                        }
                        unset($SESSION->projectgroup_filtering[$fname][$i]);
                    }
                    if (empty($SESSION->projectgroup_filtering[$fname])) {
                        unset($SESSION->projectgroup_filtering[$fname]);
                    }
                }
            }
            // clear+reload the form
            $_POST = array();
            $this->_activeform = new user_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }
        // now the active filters
    }

    /**
     * Creates known user filter if present
     * @param string $fieldname
     * @param boolean $advanced
     * @return object filter
     */
    function get_field($fieldname, $advanced) {
        global $USER, $CFG, $DB, $SITE;

        switch ($fieldname) {
            case 'shortname':   return new user_filter_text('shortname', get_string('shortname'), $advanced, 'shortname');
            case 'longname':    return new user_filter_text('fullname', get_string('fullname'), $advanced, 'fullname');
            
            default:            return null;
        }
    }
    
    /** 
     * Retrives the fields to be used elsewhere
     * @return array of fields
     * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
     * @package mymmoodle
     * 
     */
    function get_fields(){
        return $this->_fields;
    }

    /**
     * Returns sql where statement based on active user filters
     * @param string $extra sql
     * @param array named params (recommended prefix ex)
     * @return array sql string and $params
     * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
     * @package mymmoodle
     * 
     */
    function get_sql_filter($extra='', array $params=null) {
        global $SESSION;

        $sqls = array();
        if ($extra != '') {
            $sqls[] = $extra;
        }
        $params = (array)$params;

        if (!empty($SESSION->projectgroup_filtering)) {
            foreach ($SESSION->projectgroup_filtering as $fname=>$datas) {
                if (!array_key_exists($fname, $this->_fields)) {
                    continue; // filter not used
                }
                $field = $this->_fields[$fname];
                foreach($datas as $i=>$data) {
                    list($s, $p) = $field->get_sql_filter($data);
                    $sqls[] = $s;
                    $params = $params + $p;
                }
            }
        }

        if (empty($sqls)) {
            return array('', array());
        } else {
            $sqls = implode(' AND ', $sqls);
            return array($sqls, $params);
        }
    }
    
    /**
     * Print the add filter form.
     */
    function display_add() {
        $this->_addform->display();
    }

    /**
     * Print the active filter form.
     */
    function display_active() {
        $this->_activeform->display();
    }

    /**
     * 
     */
    function filter_on() {
        return !empty($SESSION->projectgroup_filtering);
    }
}
