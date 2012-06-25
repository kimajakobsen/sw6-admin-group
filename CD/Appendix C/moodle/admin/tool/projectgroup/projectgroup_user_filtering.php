<?php 

defined('MOODLE_INTERNAL') || die();

require_capability('local/projectgroup:edit', get_context_instance(CONTEXT_SYSTEM));

require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');
/**
 * This class is an extendsion of the user_filtering class. 
 * The class does the same but the form logic has been removed and should be placed elsewhere. 
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
 * @package mymmoodle
 */
class projectgroup_user_filtering extends user_filtering 
{
     
      /**
     * Contructor
     * @param array array of visible user fields
     * @param string base url used for submission/return, null if the same of current page
     * @param array extra page parameters
     * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen. 
     * @package mymmoodle
     */
    function projectgroup_user_filtering($fieldnames=null, $baseurl=null, $extraparams=null) {
        global $SESSION;

        if (!isset($SESSION->user_filtering)) {
            $SESSION->user_filtering = array();
        }

        if (empty($fieldnames)) {
            $fieldnames = array('realname'=>0, 'lastname'=>1, 'firstname'=>1, 'email'=>1, 'city'=>1, 'country'=>1,
                                'confirmed'=>1, 'suspended'=>1, 'profile'=>1, 'courserole'=>1, 'systemrole'=>1, 'cohort'=>1,
                                'firstaccess'=>1, 'lastaccess'=>1, 'neveraccessed'=>1, 'timemodified'=>1,
                                'nevermodified'=>1, 'username'=>1, 'auth'=>1, 'mnethostid'=>1);
        }

        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }

        $this->_extraparams = $extraparams;
        

    }
    
    /** 
     * Retrives the fields to be used elsewhere
     * @return array of fields
     */
    function get_fields(){
        return $this->_fields;
    }
    
     /** 
     * Retrives the extraparams 
     * @return array of extraparams
     */
    function get_extraparams(){
        return $this->_extraparams;
    }
}
