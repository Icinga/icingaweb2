<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Application\Config;
use Icinga\Application\Hook\ApplicationStateHook;
use Icinga\Authentication\Auth;
use Icinga\Forms\AcknowledgeApplicationStateMessageForm;
use Icinga\Web\ApplicationStateCookie;
use Icinga\Web\Helper\Markdown;

/**
 * Render application state messages
 */
class ApplicationStateMessages extends AbstractWidget
{
    protected function getMessages()
    {
        $cookie = new ApplicationStateCookie();

        $acked = array_flip($cookie->getAcknowledgedMessages());
        $messages = ApplicationStateHook::getAllMessages();

        $active = array_diff_key($messages, $acked);

        return $active;
    }

    public function render()
    {
        $enabled = Auth::getInstance()
            ->getUser()
            ->getPreferences()
            ->getValue('icingaweb', 'show_application_state_messages', 'system');

        if ($enabled === 'system') {
            $enabled = Config::app()->get('global', 'show_application_state_messages', true);
        }

        if (! (bool) $enabled) {
            return '<div class="hide-announcement"></div>';
        }

        $active = $this->getMessages();

        if (empty($active)) {
            // Force container update on XHR
            return '<div class="hide-announcement"></div>';
        }

        $html = '<div>';

        reset($active);

        $id = key($active);
        $spec = current($active);
        $message = array_pop($spec); // We don't use state and timestamp here


        $ackForm = new AcknowledgeApplicationStateMessageForm();
        $ackForm->populate(['id' => $id]);

        $html .= '<section class="markdown">';
        $html .= Markdown::text($message);
        $html .= '</section>';

        $html .= $ackForm;

        $html .= '</div>';

        return $html;
    }
}
