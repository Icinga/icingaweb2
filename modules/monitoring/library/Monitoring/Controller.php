<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Module\Monitoring\DataView\HostAndServiceStatus as HostAndServiceStatusView;
use Icinga\Web\Controller\ActionController;

/**
 * Base class for all monitoring action controller
 */
class Controller extends ActionController
{
    /**
     * Retrieve services from either given parameters or request
     *
     * @param   array $params
     *
     * @return  \Zend_Paginator
     */
    protected function fetchServices(array $params = null)
    {
        $columns = array(
            'host_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state',
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_output',
            'service_last_state_change',
            'service_icon_image',
            'service_long_output',
            'service_is_flapping',
            'service_state_type',
            'service_handled',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_action_url',
            'service_notes_url',
            'service_last_comment',
            'service_active_checks_enabled',
            'service_passive_checks_enabled',
            'current_check_attempt' => 'service_current_check_attempt',
            'max_check_attempts'    => 'service_max_check_attempts'
        );
        if ($params === null) {
            $query = HostAndServiceStatusView::fromRequest(
                $this->_request,
                $columns
            )->getQuery();
        } else {
            $params['backend'] = $this->_request->getParam('backend');
            $query = HostAndServiceStatusView::fromParams(
                $params,
                $columns
            )->getQuery();
        }
        $this->handleFormatRequest($query);
        return $query->paginate();
    }

    private function handleFormatRequest($query)
    {
        if ($this->_getParam('format') === 'sql'
            && IcingaConfig::app()->global->get('environment', 'production') === 'development') {
            echo '<pre>'
                . htmlspecialchars(wordwrap($query->dump()))
                . '</pre>';
            exit;
        }
        if ($this->_getParam('format') === 'json'
            || $this->_request->getHeader('Accept') === 'application/json')
        {
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
// @codingStandardsIgnoreEnd
