<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Exception;
use Icinga\Application\Logger;
use Icinga\Web\Notification;
use ipl\Html\HtmlElement;

class RemovePaneForm extends BaseDashboardForm
{
    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent() && $this->getPopulatedValue('btn_remove');
    }

    protected function assemble()
    {
        $this->addHtml(HtmlElement::create(
            'h2',
            null,
            sprintf(t('Please confirm removal of pane "%s"'), $this->requestUrl->getParam('pane'))
        ));

        $this->addHtml($this->registerSubmitButton(t('Remove Pane'))->setName('btn_remove'));
    }

    protected function onSuccess()
    {
        $home = $this->dashboard->getActiveHome();
        $pane = $home->getActivePane();

        try {
            $home->removeEntry($pane);

            $this->requestSucceeded = true;

            Notification::success(sprintf(t('Removed pane "%s" successfully'), $pane->getTitle()));
        } catch (Exception $err) {
            Logger::error(
                'Unable to remove pane "%s". An unexpected error occurred: %s',
                $pane->getTitle(),
                $err
            );

            Notification::error(t('Failed to successfully remove the pane. Please check the logs for details!'));
        }
    }
}
