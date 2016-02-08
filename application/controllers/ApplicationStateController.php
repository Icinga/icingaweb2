<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Icinga;
use Icinga\Web\Controller;
use Icinga\Web\Session;

/**
 * @TODO(el): https://dev.icinga.org/issues/10646
 */
class ApplicationStateController extends Controller
{
    public function indexAction()
    {
        if (isset($_COOKIE['icingaweb2-session'])) {
            $last = (int) $_COOKIE['icingaweb2-session'];
        } else {
            $last = 0;
        }
        $now = time();
        if ($last + 600 < $now) {
            Session::getSession()->write();
            $params = session_get_cookie_params();
            setcookie(
                'icingaweb2-session',
                $now,
                null,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
            $_COOKIE['icingaweb2-session'] = $now;
        }
        Icinga::app()->getResponse()->setHeader('X-Icinga-Container', 'ignore', true);
    }
}
