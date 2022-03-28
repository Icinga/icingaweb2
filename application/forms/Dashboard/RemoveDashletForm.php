<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Notification;
use ipl\Html\HtmlElement;
use ipl\Web\Url;

class RemoveDashletForm extends BaseDashboardForm
{
    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent() && $this->getPopulatedValue('btn_remove');
    }

    protected function assemble()
    {
        $this->addHtml(HtmlElement::create('h1', null, sprintf(
            t('Please confirm removal of dashlet "%s"'),
            Url::fromRequest()->getParam('dashlet')
        )));

        $submit = $this->registerSubmitButton(t('Remove Dashlet'));
        $submit->setName('btn_remove');

        $this->addHtml($submit);
    }

    protected function onSuccess()
    {
        $requestUrl = Url::fromRequest();
        $home = $this->dashboard->getActiveHome();
        $pane = $home->getEntry($requestUrl->getParam('pane'));

        $dashlet = $requestUrl->getParam('dashlet');
        $pane->removeEntry($dashlet);

        Notification::success(sprintf(t('Removed dashlet "%s" successfully'), $dashlet));
    }
}
