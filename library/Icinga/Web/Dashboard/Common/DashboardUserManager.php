<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use Icinga\Authentication\Auth;
use Icinga\Model\DashboardOwner;
use Icinga\User;
use Icinga\Util\DBUtils;
use ipl\Stdlib\Filter;

// TODO: Remove this completely as soon as we have introduced a daemon in Icinga Web 2.
trait DashboardUserManager
{
    /** @var User */
    private static $user;

    /**
     * Init the current user
     *
     * Just ensures that the current user is stored in the DB
     *
     * @return void
     */
    protected static function initUser()
    {
        if (! self::userExist()) {
            $conn = DBUtils::getConn();
            $conn->insert('icingaweb_dashboard_owner', ['username' => self::getUser()->getUsername()]);

            self::getUser()->setAdditional('id', $conn->lastInsertId());
        }
    }

    /**
     * Set this dashboard's user
     *
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user): self
    {
        self::$user = $user;
        self::initUser();

        return $this;
    }

    /**
     * Get this dashboard's user
     *
     * @return User
     */
    public static function getUser(): User
    {
        if (self::$user === null) {
            self::$user = Auth::getInstance()->getUser();
            self::initUser();
        }

        return self::$user;
    }

    /**
     * Get whether the current user is already in the database
     *
     * @return bool
     */
    public static function userExist(): bool
    {
        $query = DashboardOwner::on(DBUtils::getConn())
            ->setColumns(['id'])
            ->filter(Filter::equal('username', self::getUser()->getUsername()));

        $found = false;
        $result = $query->execute();
        if ($result->hasResult()) {
            $found = true;
            self::getUser()->setAdditional('id', $result->current()->id);
        }

        return $found;
    }
}
