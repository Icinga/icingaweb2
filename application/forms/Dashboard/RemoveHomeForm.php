<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

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
            sprintf(t('Please confirm removal of dashboard home "%s"'), $this->dashboard->getActiveHome()->getTitle())
        ));

        $this->addHtml($this->registerSubmitButton('Remove Home')->setName('btn_remove'));
    }

    protected function onSuccess()
    {
        $home = $this->dashboard->getActiveHome();
        $this->dashboard->removeEntry($home);

        Notification::success(sprintf(t('Removed dashboard home "%s" successfully'), $home->getTitle()));
    }
}
