<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashboard;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class RemoveDashletForm extends CompatForm
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
        $this->addHtml(HtmlElement::create('h1', null, sprintf(
            t('Please confirm removal of dashlet "%s"'),
            Url::fromRequest()->getParam('dashlet')
        )));

        $this->addElement('submit', 'remove_dashlet', ['label' => t('Remove Dashlet')]);
    }

    protected function onSuccess()
    {
        $requestUrl = Url::fromRequest();
        $home = $this->dashboard->getActiveHome();
        $pane = $home->getPane($requestUrl->getParam('pane'));

        $dashlet = $requestUrl->getParam('dashlet');
        $pane->removeDashlet($dashlet);

        Notification::success(sprintf(t('Removed dashlet "%s" successfully'), $dashlet));
    }
}
