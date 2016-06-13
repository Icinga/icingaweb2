<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Eventdb\Controllers;

use Icinga\Module\Eventdb\EventdbController;
use Icinga\Util\StringHelper;
use Icinga\Web\Url;

class EventsController extends EventdbController
{
    public function indexAction()
    {
        $this->getTabs()->add('events', array(
            'title' => $this->translate('Events'),
            'url'   => Url::fromRequest()
        ))->activate('events');

        $staticQueryColumns = array(
            'id'
        );

        if (! $this->params->has('columns')) {
            $displayColumns = array(
                'ack',
                'type',
                'host_name',
                'priority',
                'message',
                'program',
                'facility',
                'created'
            );

            $additionalColumns = $this->Config()->get('backend', 'additional_columns');
            if ($additionalColumns !== null) {
                $displayColumns = array_merge($displayColumns, StringHelper::trimSplit($additionalColumns));
            }
        } else {
            $displayColumns = StringHelper::trimSplit($this->params->get('columns'));
        }

        $queryColumns = array_merge($staticQueryColumns, $displayColumns);

        $events = $this->getDb()
            ->select()
            ->from('event', $queryColumns);

        $this->setupPaginationControl($events);

        $this->setupFilterControl(
            $events,
            array(
                'host_name'     => $this->translate('Host'),
                'host_address'  => $this->translate('Host Address'),
                'type'          => $this->translate('Type'),
                'facility'      => $this->translate('Facility'),
                'priority'      => $this->translate('Priority'),
                'program'       => $this->translate('Program'),
                'message'       => $this->translate('Message'),
                'ack'           => $this->translate('Acknowledged'),
                'created'       => $this->translate('Created')
            ),
            array('host_name'),
            array('columns')
        );

        $this->setupLimitControl();

        $this->setupSortControl(
            array(
                'host_name'     => $this->translate('Host'),
                'host_address'  => $this->translate('Host Address'),
                'type'          => $this->translate('Type'),
                'facility'      => $this->translate('Facility'),
                'priority'      => $this->translate('Priority'),
                'program'       => $this->translate('Program'),
                'message'       => $this->translate('Message'),
                'ack'           => $this->translate('Acknowledged'),
                'created'       => $this->translate('Created')
            ),
            $events,
            array('created' => 'desc')
        );

        $this->view->displayColumns = $displayColumns;
        $this->view->events = $events;
    }

    public function detailsAction()
    {
        $this->getTabs()->add('events', array(
            'title' => $this->translate('Events'),
            'url'   => Url::fromRequest()
        ))->activate('events');
    }
}
