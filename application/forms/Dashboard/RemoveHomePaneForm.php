<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashboard;
use ipl\Html\HtmlElement;
use ipl\Web\Url;

class RemoveHomePaneForm extends BaseDashboardForm
{
    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent() && $this->getPopulatedValue('btn_remove');
    }

    protected function assemble()
    {
        $requestRoute = Url::fromRequest();
        $label = t('Remove Home');
        $message = sprintf(t('Please confirm removal of dashboard home "%s"'), $requestRoute->getParam('home'));
        if ($requestRoute->getPath() === Dashboard::BASE_ROUTE . '/remove-pane') {
            $label = t('Remove Pane');
            $message = sprintf(t('Please confirm removal of dashboard pane "%s"'), $requestRoute->getParam('pane'));
        }

        $this->addHtml(HtmlElement::create('h1', null, $message));

        $submit = $this->registerSubmitButton($label);
        $submit->setName('btn_remove');

        $this->addHtml($submit);
    }

    protected function onSuccess()
    {
        $requestUrl = Url::fromRequest();
        $home = $this->dashboard->getActiveHome();

        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/remove-home') {
            $this->dashboard->removeEntry($home);

            Notification::success(sprintf(t('Removed dashboard home "%s" successfully'), $home->getTitle()));
        } else {
            $pane = $home->getEntry($requestUrl->getParam('pane'));
            $home->removeEntry($pane);

            Notification::success(sprintf(t('Removed dashboard pane "%s" successfully'), $pane->getTitle()));
        }
    }
}
