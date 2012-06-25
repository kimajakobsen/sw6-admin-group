<?php
require_once $CFG->dirroot.'/lib/pagelib.php';

/**
 * The project group page used to get and set the current project group 
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 * @package mymmoodle
 */
class projectgroup_page extends moodle_page {

    protected $_projectgroup = null;

    protected function magic_get_projectgroup() {
        global $SITE;
        if (is_null($this->_projectgroup)) {
            return $SITE;
        }
        return $this->_projectgroup;
    }


    /**
     *
     * @param $name string property name
     * @return mixed
     */
    public function __get($name) {
        $getmethod = 'magic_get_' . $name;
        if (method_exists($this, $getmethod)) {
            return $this->$getmethod();
        } else {
            throw new coding_exception('Unknown property ' . $name . ' of $PAGE.');
        }
    }


    public function set_projectgroup(stdClass $projectgroup) {
        global $COURSE, $PAGE;

        if (empty($projectgroup->id)) {
            throw new coding_exception('$projectgroup passed to projectgroup_page::set_projectgroup does not look like a proper projectgroup object.');
        }

        $this->_projectgroup = clone($projectgroup);

        if (!$this->_context) {
            $this->set_context(get_projectgroup_context_instance($this->_projectgroup->id));
        }
    }
}
