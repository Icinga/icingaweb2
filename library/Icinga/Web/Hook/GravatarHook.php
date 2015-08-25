<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Hook;

/**
 * Icinga Web Gravatar Hook base class
 *
 * Extend this class if you want to integrate your avatar solution nicely into
 * Icinga Web.
 */
abstract class GravatarHook extends WebBaseHook
{
    /**
     * Get avatar img source
     *
     * @param $email string
     *
     * @return string
     */
    abstract public function getAvatar($email);
}
