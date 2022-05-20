<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashboard;
use ipl\Html\HtmlElement;
use ipl\Web\Url;

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
            sprintf(t('Please confirm removal of dashboard pane "%s"'), $this->requestUrl->getParam('pane'))
        ));

        $this->addHtml($this->registerSubmitButton(t('Remove Pane'))->setName('btn_remove'));
    }

    protected function onSuccess()
    {
        $home = $this->dashboard->getActiveHome();

        $pane = $home->getEntry($this->requestUrl->getParam('pane'));
        $home->removeEntry($pane);

        Notification::success(sprintf(t('Removed dashboard pane "%s" successfully'), $pane->getTitle()));
    }
}
