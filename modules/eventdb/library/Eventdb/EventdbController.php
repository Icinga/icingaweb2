<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Eventdb;

use Icinga\Web\Controller;

class EventdbController extends Controller
{
    /**
     * @return  Eventdb
     */
    protected function getDb()
    {
        return Eventdb::fromConfig();
    }

    protected function handleFormatRequest($query)
    {
        switch ($this->params->get('format')) {
            case 'sql':

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
            echo json_encode($query->fetchAll());
            exit;
        }
        if ($this->_getParam('format') === 'csv'
            || $this->_request->getHeader('Accept') === 'text/csv') {
            Csv::fromQuery($query)->dump();
            exit;
        }
    }
}
