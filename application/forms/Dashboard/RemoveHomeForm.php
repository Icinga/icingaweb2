<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Logger;
use Icinga\Web\Notification;
use ipl\Html\HtmlElement;

class RemoveHomeForm extends BaseDashboardForm
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
            sprintf(t('Please confirm removal of Dashboard Home "%s"'), $this->dashboard->getActiveHome()->getTitle())
        ));

        $this->addHtml($this->registerSubmitButton('Remove Home')->setName('btn_remove'));
    }

    protected function onSuccess()
    {
        $home = $this->dashboard->getActiveHome();

        try {
            $this->dashboard->removeEntry($home);

            $this->requestSucceeded = true;

            Notification::success(sprintf(t('Removed Dashboard Home "%s" successfully'), $home->getTitle()));
        } catch (\Exception $err) {
            Logger::error(
                'Unable to remove Dashboard Home "%s". An unexpected error occurred: %s',
                $home->getTitle(),
                $err
            );

            Notification::error(
                t('Failed to successfully remove the Dashboard Home. Please check the logs for details!')
            );

            return;
        }
    }
}
