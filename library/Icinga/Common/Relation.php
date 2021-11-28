<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Common;

use Icinga\DBUser;
use Icinga\Exception\ProgrammingError;

trait Relation
{
    /**
     * All dashboard users member of this widget
     *
     * @var DBUser[]
     */
    protected $members = [];

    protected $memberRoles = [];

    protected $memberGroups = [];

    /**
     * Caches more information about this widget's relation
     *
     * @var array
     */
    protected $additionalInfo = [];

    /**
     * Load users from DB that are already member of this widget
     *
     * @return void
     */
    abstract public function loadMembers();

    /**
     * Get database table name of this membership
     *
     * @return string
     */
    public function getTableMembership()
    {
        return $this->tableMembership;
    }

    /**
     * Add a user member of this widget
     *
     * @param DBUser $member
     *
     * @return $this
     */
    public function addMember(DBUser $member)
    {
        if (! $this->hasMember($member->getUsername())) {
            $this->members[$member->getUsername()] = $member;
        }

        return $this;
    }

    /**
     * Set the users member of this home, pane or dashlet
     *
     * @param DBUser[] $members
     *
     * @return $this
     */
    public function setMembers(array $members)
    {
        $this->members = $members;

        return $this;
    }

    /**
     * Get user members of this home, pane or dashlet
     *
     * @return DBUser[]
     */
    public function getMembers()
    {
        return array_filter($this->members, function ($DBUser) {
            return ! $DBUser->isRemoved();
        });
    }

    /**
     * Get a member by the given name
     *
     * @param string $name
     *
     * @return DBUser
     */
    public function getMember($name)
    {
        if (! $this->hasMember($name)) {
            throw new ProgrammingError('Trying to retrieve invalid member');
        }

        return $this->members[$name];
    }

    /**
     * Get whether this home, pane or dashlet has the given member
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasMember($name)
    {
        return array_key_exists($name, $this->members);
    }

    /**
     * Whether this widget has any user members
     *
     * @return bool
     */
    public function hasMembers()
    {
        return ! empty($this->getMembers());
    }

    /**
     * Whether the given group is already assigned to this widget
     *
     * @return bool
     */
    public function hasMemberGroup($group)
    {
        return array_key_exists($group, $this->memberGroups);
    }

    /**
     * Whether the given role is already assigned to this widget
     *
     * @return bool
     */
    public function hasMemberRole($role)
    {
        return array_key_exists($role, $this->memberRoles);
    }

    /**
     * Set additional info about this widget's relation
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setAdditional($key, $value)
    {
        $this->additionalInfo[$key] = $value;

        return $this;
    }

    /**
     * Get additional info by the given key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getAdditional($key)
    {
        if (isset($this->additionalInfo[$key])) {
            return $this->additionalInfo[$key];
        }

        return [];
    }
}
