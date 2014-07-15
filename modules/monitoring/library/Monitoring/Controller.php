<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring;

use Icinga\Web\Controller\ModuleActionController;
use Icinga\Web\Url;
use Icinga\File\Csv;

/**
 * Base class for all monitoring action controller
 */
class Controller extends ModuleActionController
{
    /**
     * The backend used for this controller
     *
     * @var Backend
     */
    protected $backend;

    /**
     * Compact layout name
     *
     * Set to a string containing the compact layout name to use when
     * 'compact' is set as the layout parameter, otherwise null
     *
     * @var string
     */
    protected $compactView;

    protected function moduleInit()
    {
        $this->backend = Backend::createBackend($this->_getParam('backend'));
        $this->view->url = Url::fromRequest();
    }

    protected function handleFormatRequest($query)
    {
        if ($this->compactView !== null && ($this->_getParam('view', false) === 'compact')) {
            $this->_helper->viewRenderer($this->compactView);
        }

        if ($this->_getParam('format') === 'sql') {
            echo '<pre>'
                . htmlspecialchars(wordwrap($query->dump()))
                . '</pre>';
            exit;
        }
        if ($this->_getParam('format') === 'json'
            || $this->_request->getHeader('Accept') === 'application/json') {
            header('Content-type: application/json');
            echo json_encode($query->getQuery()->fetchAll());
            exit;
        }
        if ($this->_getParam('format') === 'csv'
            || $this->_request->getHeader('Accept') === 'text/csv') {
            Csv::fromQuery($query)->dump();
            exit;
        }
    }
}
// @codingStandardsIgnoreEnd
