<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Data\Filter\Filter;
use Icinga\Forms\Announcement\AcknowledgeAnnouncementForm;
use Icinga\Web\Announcement\AnnouncementCookie;
use Icinga\Web\Announcement\AnnouncementIniRepository;
use Icinga\Web\Helper\Markdown;

/**
 * Render announcements
 */
class Announcements extends AbstractWidget
{
    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $repo = new AnnouncementIniRepository();
        $etag = $repo->getEtag();
        $cookie = new AnnouncementCookie();
        if ($cookie->getEtag() !== $etag) {
            $cookie->setEtag($etag);
            $cookie->setNextActive($repo->findNextActive());
            Icinga::app()->getResponse()->setCookie($cookie);
        }
        $acked = array();
        foreach ($cookie->getAcknowledged() as $hash) {
            $acked[] = Filter::expression('hash', '!=', $hash);
        }
        $acked = Filter::matchAll($acked);
        $announcements = $repo->findActive();
        $announcements->applyFilter($acked);
        if ($announcements->hasResult()) {
            $html = '<ul role="alert">';
            foreach ($announcements as $announcement) {
                $ackForm = new AcknowledgeAnnouncementForm();
                $ackForm->populate(array('hash' => $announcement->hash));
                $html .= '<li><div class="message">'
                    . Markdown::text($announcement->message)
                    . '</div>'
                    . $ackForm
                    . '</li>';
            }
            $html .= '</ul>';
            return $html;
        }
        // Force container update on XHR
        return '<div class="hide-announcement"></div>';
    }
}
