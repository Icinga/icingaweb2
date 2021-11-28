<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga;

use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Sql\Select;

class DBUser extends User
{
    /**
     * Database users unique identifier
     *
     * @var int
     */
    protected $identifier;

    /**
     * A flag whether this user has a write access to a shared dashboard
     *
     * @var bool
     */
    protected $hasWriteAccess = false;

    /**
     * An indicator whether this user has deleted a shared dashboard that he has created personally
     *
     * @var bool
     */
    protected $removed = false;

    /**
     * Set this user's DB identifier
     *
     * @param int $identifier
     *
     * @return $this
     */
    public function setIdentifier(int $identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get this user's unique DB identifier
     *
     * @return int
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set this user's write access flag
     *
     * @param bool $writeAccess
     *
     * @return $this
     */
    public function setWriteAccess(bool $writeAccess)
    {
        $this->hasWriteAccess = $writeAccess;

        return $this;
    }

    /**
     * Get whether this user has a write access permission
     *
     * @return bool
     */
    public function hasWriteAccess(Pane $pane = null)
    {
        if (! $pane) {
            return $this->hasWriteAccess;
        }

        $query = DashboardHome::getConn()->select((new Select())
            ->columns('write_access')
            ->from($pane->getTableMembership())
            ->where([
                'dashboard_id = ?'  => $pane->getPaneId(),
                'user_id = ?'       => $this->getIdentifier()
            ]))->fetch();

        return $query && $query->write_access === 'y';
    }

    /**
     * Set an indicator whether this user has deleted a shared
     * dashboard that he has created personally
     *
     * @param bool $removed
     *
     * @return $this
     */
    public function setRemoved(bool $removed)
    {
        $this->removed = $removed;

        return $this;
    }

    /**
     * Get whether this user has deleted a shared dashboard
     * that he has created personally
     *
     * @return bool
     */
    public function isRemoved()
    {
        return $this->removed;
    }

    /**
     * Extract a normal User to DB user
     *
     * @param User $user
     *
     * @return $this
     */
    public function extractFrom(User $user)
    {
        $refUser = new \ReflectionClass(get_class($user));
        foreach ($refUser->getProperties() as $property) {
            $property->setAccessible(true);
            $this->{$property->getName()} = $property->getValue($user);
            $property->setAccessible(false);
        }

        return $this;
    }

    /**
     * Get all assigned roles to this user
     *
     * @return Authentication\Role[]
     */
    public function getAssignedRoles()
    {
        $assignedRoles = [];
        foreach ($this->getRoles() as $role) {
            if (! in_array($role->getName(), $this->getAdditional('assigned_roles'), true)) {
                continue;
            }

            $assignedRoles[$role->getName()] = $role;
        }

        return $assignedRoles;
    }

    /**
     * Get whether the given role is part of the assigned user roles
     *
     * @param string $roleName
     *
     * @return bool
     */
    public function hasRole($roleName)
    {
        return array_key_exists($roleName, $this->getAssignedRoles());
    }

    /**
     * Get simple string representation of this class, i.e just get the username
     *
     * Useful for comparing using built-in functions only
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername();
    }
}
