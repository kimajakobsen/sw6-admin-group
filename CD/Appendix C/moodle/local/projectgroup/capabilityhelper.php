<?php
/**
 * Unit tests for Project group Library
 *
 * @author Rasmus Prentow et. al. 
 * @package mymmoodle
 */
class capability_helper
{
    public function has_capability($capability,$context)
    {
        return has_capability($capability,$context);
    }
    
    public function get_groups_of_user($user_id)
    {
        return get_groups_of_user($user_id);
    }
}