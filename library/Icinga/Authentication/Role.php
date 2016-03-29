<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

class Role
{
    /**
     * Name of the role
     *
     * @var string
     */
    protected $name;

    /**
     * Permissions of the role
     *
     * @var string[]
     */
    protected $permissions = array();

    /**
     * Restrictions of the role
     *
     * @var string[]
     */
    protected $restrictions = array();

    /**
     * Get the name of the role
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the role
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the permissions of the role
     *
     * @return  string[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Set the permissions of the role
     *
     * @param   string[]    $permissions
     *
     * @return  $this
     */
    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * Get the restrictions of the role
     *
     * @param   string  $name   Optional name of the restriction
     *
     * @return  string[]|null
     */
    public function getRestrictions($name = null)
    {
        $restrictions = $this->restrictions;

        if ($name === null) {
            return $restrictions;
        }

        if (isset($restrictions[$name])) {
            return $restrictions[$name];
        }

        return null;
    }

    /**
     * Set the restrictions of the role
     *
     * @param   string[]    $restrictions
     *
     * @return  $this
     */
    public function setRestrictions(array $restrictions)
    {
        $this->restrictions = $restrictions;
        return $this;
    }
}
