<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Gravatar;

use Icinga\Web\Hook\GravatarHook;

/**
 * Class Gravatar
 */
class Gravatar extends GravatarHook
{
    /**
     * {@inheritdoc}
     */
    public function getAvatar($email)
    {
        $baseUrl = $this->getView()->baseUrl();

        return $baseUrl . '/gravatar/?email=' . $email;
    }
}
