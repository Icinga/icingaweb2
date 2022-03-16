<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Hook\HealthHook;
use Icinga\Web\View\AppHealth;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Compat\CompatController;

class HealthController extends CompatController
{
    public function indexAction()
    {
        $query = HealthHook::collectHealthData()
            ->select();

        $this->setupSortControl(
            [
                'module'    => $this->translate('Module'),
                'name'      => $this->translate('Name'),
                'state'     => $this->translate('State')
            ],
            $query,
            ['state' => 'desc']
        );
        $this->setupLimitControl();
        $this->setupPaginationControl($query);
        $this->setupFilterControl($query, [
            'module'    => $this->translate('Module'),
            'name'      => $this->translate('Name'),
            'state'     => $this->translate('State'),
            'message'   => $this->translate('Message')
        ], ['name'], ['format']);

        $this->getTabs()->extend(new OutputFormat(['csv']));
        $this->handleFormatRequest($query);

        $this->addControl(HtmlString::create((string) $this->view->paginator));
        $this->addControl(Html::tag('div', ['class' => 'sort-controls-container'], [
            HtmlString::create((string) $this->view->limiter),
            HtmlString::create((string) $this->view->sortBox)
        ]));
        $this->addControl(HtmlString::create((string) $this->view->filterEditor));

        $this->addTitleTab(t('Health'));
        $this->setAutorefreshInterval(10);
        $this->addContent(new AppHealth($query));
    }

    protected function handleFormatRequest($query)
    {
        $formatJson = $this->params->get('format') === 'json';
        if (! $formatJson && ! $this->getRequest()->isApiRequest()) {
            return;
        }

        $this->getResponse()
            ->json()
            ->setSuccessData($query->fetchAll())
            ->sendResponse();
    }
}
