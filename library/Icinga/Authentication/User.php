<?php

/**
 * Icinga Authentication User class
 *
 * @package Icinga\Authentication
 */
namespace Icinga\Authentication;

/**
 * This class represents a user object
 *
 * TODO: Show some use cases
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Application
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class User extends Storable
{
    protected $defaultProps = array(
        'username'   => null,
        'password'   => null,
        'first_name' => null,
        'last_name'  => null,
        'email'      => null,
    );
    protected $permissions = array();
    protected $backend;
    protected $groups;
    protected $key = 'username';

    public function listGroups()
    {
        if ($this->groups === null) {
            $this->loadGroups();
        }
    }

    protected function loadGroups()
    {
        // Whatever
    }

    public function isMemberOf(Group $group)
    {
        
    }

    public function getPermissionList()
    {
        return $this->permissions;
    }

    public function hasPermission($uri, $permission)
    {

    }

    public function grantPermission($uri, $permission)
    {

    }

    public function revokePermission($uri, $permission)
    {

    }
}
