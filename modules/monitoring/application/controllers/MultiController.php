<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Controller;
use Icinga\Web\Widget\Chart\InlinePie;
use Icinga\Module\Monitoring\Form\Command\MultiCommandFlagForm;
use Icinga\Web\Widget;
use Icinga\Data\Filter\Filter;

/**
 * Displays aggregations collections of multiple objects.
 */
class Monitoring_MultiController extends Controller
{
    public function hostAction()
    {
        $errors  = array();
        $query = $this->backend->select()->from(
            'hostStatus',
            array(
                'host_name',
                'host_in_downtime',
                'host_passive_checks_enabled',
                'host_obsessing',
                'host_state',
                'host_notifications_enabled',
                'host_event_handler_enabled',
                'host_flap_detection_enabled',
                'host_active_checks_enabled',
                // columns intended for filter-request
                'host_problem',
                'host_handled'
            )
        )->getQuery();
        $this->applyQueryFilter($query);
        $hosts = $query->fetchAll();

        $comments = $this->backend->select()->from('comment', array(
            'comment_internal_id',
            'comment_host',
        ));
        $this->applyQueryFilter($comments);
        $uniqueComments = array_keys($this->getUniqueValues($comments->getQuery()->fetchAll(), 'comment_internal_id'));

        // Populate view
        $this->view->objects = $this->view->hosts = $hosts;
        $this->view->problems = $this->getProblems($hosts);
        $this->view->comments = $uniqueComments;
        $this->view->hostnames = $this->getProperties($hosts, 'host_name');
        $this->view->downtimes = $this->getDowntimes($hosts);
        $this->view->errors = $errors;
        $this->view->states = $this->countStates($hosts, 'host', 'host_name');
        $this->view->pie = $this->createPie(
            $this->view->states,
            $this->view->getHelper('MonitoringState')->getHostStateColors(),
            mt('monitoring', 'Host State')
        );

        // Handle configuration changes
        $this->handleConfigurationForm(array(
            'host_passive_checks_enabled' => $this->translate('Passive Checks'),
            'host_active_checks_enabled'  => $this->translate('Active Checks'),
            'host_notifications_enabled'  => $this->translate('Notifications'),
            'host_event_handler_enabled'  => $this->translate('Event Handler'),
            'host_flap_detection_enabled' => $this->translate('Flap Detection'),
            'host_obsessing'              => $this->translate('Obsessing')
        ));
    }

    public function serviceAction()
    {
        $errors = array();
        $query = $this->backend->select()->from('serviceStatus', array(
            'host_name',
            'host_state',
            'service_description',
            'service_handled',
            'service_state',
            'service_in_downtime',
            'service_passive_checks_enabled',
            'service_notifications_enabled',
            'service_event_handler_enabled',
            'service_flap_detection_enabled',
            'service_active_checks_enabled',
            'service_obsessing',
             // also accept all filter-requests from ListView
            'service_problem',
            'service_severity',
            'service_last_check',
            'service_state_type',
            'host_severity',
            'host_address',
            'host_last_check'
        ));

        $this->applyQueryFilter($query);
        $services = $query->getQuery()->fetchAll();

        $comments = $this->backend->select()->from('comment', array(
            'comment_internal_id',
            'comment_host',
            'comment_service'
        ));
        $this->applyQueryFilter($comments);
        $uniqueComments = array_keys($this->getUniqueValues($comments->getQuery()->fetchAll(), 'comment_internal_id'));

        // populate the view
        $this->view->objects        = $this->view->services = $services;
        $this->view->problems       = $this->getProblems($services);
        $this->view->comments       = $uniqueComments;
        $this->view->hostnames      = $this->getProperties($services, 'host_name');
        $this->view->servicenames   = $this->getProperties($services, 'service_description');
        $this->view->downtimes      = $this->getDowntimes($services);
        $this->view->service_states = $this->countStates($services, 'service');
        $this->view->host_states    = $this->countStates($services, 'host', 'host_name');
        $this->view->service_pie    = $this->createPie(
            $this->view->service_states,
            $this->view->getHelper('MonitoringState')->getServiceStateColors(),
            mt('monitoring', 'Service State')
        );
        $this->view->host_pie       = $this->createPie(
            $this->view->host_states,
            $this->view->getHelper('MonitoringState')->getHostStateColors(),
            mt('monitoring', 'Host State')
        );
        $this->view->errors         = $errors;

        $this->handleConfigurationForm(array(
            'service_passive_checks_enabled' => $this->translate('Passive Checks'),
            'service_active_checks_enabled'  => $this->translate('Active Checks'),
            'service_notifications_enabled'  => $this->translate('Notifications'),
            'service_event_handler_enabled'  => $this->translate('Event Handler'),
            'service_flap_detection_enabled' => $this->translate('Flap Detection'),
            'service_obsessing'              => $this->translate('Obsessing'),
        ));
    }

    protected function applyQueryFilter($query)
    {
        $params = clone $this->params;
        $modifyFilter = $params->shift('modifyFilter');

        $filter = Filter::fromQueryString((string) $params);
        if ($modifyFilter) {
            $this->view->filterWidget = Widget::create('filterEditor', array(
                'filter' => $filter,
                'query'  => $query
            ));
        }
        $this->view->filter = $filter;
        $query->applyFilter($filter);
        return $query;
    }

    /**
     * Create an array with all unique values as keys.
     *
     * @param array $values     The array containing the objects
     * @param       $key        The key to access
     *
     * @return array
     */
    private function getUniqueValues($values, $key)
    {
        $unique = array();
        foreach ($values as $value) {
            if (is_array($value)) {
                $unique[$value[$key]] = $value[$key];
            } else {
                $unique[$value->$key] = $value->$key;
            }
        }
        return $unique;
    }

    /**
     * Get the numbers of problems of the given objects
     *
     * @param $objects  The objects containing the problems
     *
     * @return int  The problem count
     */
    private function getProblems($objects)
    {
        $problems = 0;
        foreach ($objects as $object) {
            if (property_exists($object, 'host_unhandled_service_count')) {
                $problems += $object->host_unhandled_service_count;
            } else if (
                property_exists($object, 'service_handled') &&
                !$object->service_handled &&
                $object->service_state > 0
            ) {
                $problems++;
            }
        }
        return $problems;
    }

    private function countStates($objects, $type = 'host', $unique = null)
    {
        $known  = array();
        if ($type === 'host') {
            $states = array_fill_keys($this->view->getHelper('MonitoringState')->getHostStateNames(), 0);
        } else {
            $states = array_fill_keys($this->view->getHelper('MonitoringState')->getServiceStateNames(), 0);
        }
        foreach ($objects as $object) {
            if (isset($unique)) {
                if (array_key_exists($object->$unique, $known)) {
                    continue;
                }
                $known[$object->$unique] = true;
            }
            $states[$this->view->monitoringState($object, $type)]++;
        }
        return $states;
    }

    private function createPie($states, $colors, $title)
    {
        $chart = new InlinePie(array_values($states), $title, $colors);
        $chart->setLabel(array_keys($states))->setHeight(100)->setWidth(100);
        $chart->setTitle($title);
        return $chart;
    }


    private function getComments($objects)
    {
        $unique = array();
        foreach ($objects as $object) {
            $unique = array_merge($unique, $this->getUniqueValues($object->comments, 'comment_internal_id'));
        }
        return array_keys($unique);
    }

    private function getProperties($objects, $property)
    {
        $objectnames = array();
        foreach ($objects as $object) {
            $objectnames[] = $object->$property;
        }
        return $objectnames;
    }

    private function getDowntimes($objects)
    {
        $downtimes = array();
        foreach ($objects as $object)
        {
            if (
                (property_exists($object, 'host_in_downtime') && $object->host_in_downtime) ||
                (property_exists($object, 'service_in_downtime') && $object->service_in_downtime)
            ) {
                $downtimes[] = true;
            }
        }
        return $downtimes;
    }


    /**
     * Handle the form to edit configuration flags.
     *
     * @param $flags array  The used flags.
     */
    private function handleConfigurationForm(array $flags)
    {
        $this->view->form = $form = new MultiCommandFlagForm($flags);
        $this->view->formElements = $form->buildCheckboxes();
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            // TODO: Handle commands
            $changed = $form->getChangedValues();
        }
        if ($this->_request->isPost() === false) {
            $this->view->form->initFromItems($this->view->objects);
        }
    }
}
