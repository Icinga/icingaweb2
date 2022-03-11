<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashboard;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class RemoveHomePaneForm extends CompatForm
{
    /** @var Dashboard */
    protected $dashboard;

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;

        $this->setAction((string) Url::fromRequest());
    }

    protected function assemble()
    {
        $this->addElement('submit', 'btn_remove', ['label' => t('Remove Home')]);
    }

    protected function onSuccess()
    {
        $requestUrl = Url::fromRequest();
        $home = $this->dashboard->getActiveHome();

        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/remove-home') {
            $this->dashboard->removeHome($home);

            Notification::success(sprintf(t('Removed dashboard home "%s" successfully'), $home->getLabel()));
        } else {
            $pane = $home->getPane($requestUrl->getParam('pane'));
            $home->removePane($pane);

            Notification::success(sprintf(t('Removed dashboard pane "%s" successfully'), $pane->getTitle()));
        }
    }
}
