<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
        $acked = [];
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
                $ackForm->populate(['hash' => $announcement->hash]);
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
        return '<div hidden></div>';
    }
}
