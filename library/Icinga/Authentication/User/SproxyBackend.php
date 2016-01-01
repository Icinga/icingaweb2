<?php
/* 2016 Zalora South East Asia Pte. Ltd | GPLv2+ */

namespace Icinga\Authentication\User;

use Icinga\Data\ConfigObject;
use Icinga\User;

/**
 * Login with Sproxy authentication mechanism:
 * https://github.com/zalora/sproxy
 */
class SproxyBackend extends ExternalBackend
{
    /**
     * {@inheritdoc}
     */
    public function authenticate(User $user, $password = null)
    {
        if (! empty($_SERVER['HTTP_FROM'])) {
            $email = $_SERVER['HTTP_FROM'];
            $user->setUsername($email);
            $user->setEmail($email);
            $user->setExternalUserInformation($email, 'HTTP_FROM');

            if (! empty($_SERVER['HTTP_X_GIVEN_NAME'])) {
              $user->setFirstname($_SERVER['HTTP_X_GIVEN_NAME']);
            }
            if (! empty($_SERVER['HTTP_X_GROUPS'])) {
              $user->setGroups(explode(',', $_SERVER['HTTP_X_GROUPS']));
            }
            if (! empty($_SERVER['HTTP_X_FAMILY_NAME'])) {
              $user->setLastname($_SERVER['HTTP_X_FAMILY_NAME']);
            }

            return true;
        }
        return false;
    }
}
