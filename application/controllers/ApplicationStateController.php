<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Icinga;
use Icinga\Web\Announcement\AnnouncementCookie;
use Icinga\Web\Announcement\AnnouncementIniRepository;
use Icinga\Web\Controller;
use Icinga\Web\Session;

/**
 * @TODO(el): https://dev.icinga.com/issues/10646
 */
class ApplicationStateController extends Controller
{
    protected $requiresAuthentication = false;

    public function indexAction()
    {
        $this->_helper->layout()->disableLayout();

        if ($this->Auth()->isAuthenticated()) {
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
            $announcementCookie = new AnnouncementCookie();
            $announcementRepo = new AnnouncementIniRepository();
            if ($announcementCookie->getEtag() !== $announcementRepo->getEtag()) {
                $announcementCookie
                    ->setEtag($announcementRepo->getEtag())
                    ->setNextActive($announcementRepo->findNextActive());
                $this->getResponse()->setCookie($announcementCookie);
                $this->getResponse()->setHeader('X-Icinga-Announcements', 'refresh', true);
            } else {
                $nextActive = $announcementCookie->getNextActive();
                if ($nextActive && $nextActive <= $now) {
                    $announcementCookie->setNextActive($announcementRepo->findNextActive());
                    $this->getResponse()->setCookie($announcementCookie);
                    $this->getResponse()->setHeader('X-Icinga-Announcements', 'refresh', true);
                }
            }
        }

        $this->getResponse()->setHeader('X-Icinga-Container', 'ignore', true);
    }
}
