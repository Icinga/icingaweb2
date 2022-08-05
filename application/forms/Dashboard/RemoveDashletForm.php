<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Logger;
use Icinga\Web\Notification;
use ipl\Html\HtmlElement;

class RemoveDashletForm extends BaseDashboardForm
{
    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent() && $this->getPopulatedValue('btn_remove');
    }

    protected function assemble()
    {
        $this->addHtml(HtmlElement::create('h1', null, sprintf(
            t('Please confirm removal of Dashlet "%s"'),
            $this->requestUrl->getParam('dashlet')
        )));

        $submit = $this->registerSubmitButton(t('Remove Dashlet'));
        $submit->setName('btn_remove');

        $this->addHtml($submit);
    }

    protected function onSuccess()
    {
        $home = $this->dashboard->getActiveEntry();
        $pane = $home->getActiveEntry();

        $dashlet = $pane->getEntry($this->requestUrl->getParam('dashlet'));

        try {
            $pane->removeEntry($dashlet);

            $this->requestSucceeded = true;
        } catch (\Exception $err) {
            Logger::error(
                'Unable to remove Dashlet "%s". An unexpected error occurred: %s',
                $dashlet->getTitle(),
                $err
            );

            Notification::error(t('Failed to successfully remove the Dashlet. Please check the logs for details!'));

            return;
        }

        Notification::success(sprintf(t('Removed Dashlet "%s" successfully'), $dashlet->getTitle()));
    }
}
