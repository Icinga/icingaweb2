<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Eventdb\Controllers;

use Icinga\Module\Eventdb\EventdbController;
use Icinga\Web\Url;

class EventController extends EventdbController
{
    public function indexAction()
    {
        $event = $this->params->getRequired('id');

        $this->getTabs()->add('event', array(
            'title' => $this->translate('Event'),
            'url'   => Url::fromRequest()
        ))->activate('event');

        $comments = $this->getDb()
            ->select()
            ->from('comment', array(
                'id',
                'type',
                'message',
                'created',
                'modified',
                'user'
            ))
            ->where('event_id', $event);

        $this->setupPaginationControl($comments);

        $this->setupFilterControl(
            $comments,
            array(
                'type'      => $this->translate('Type'),
                'message'   => $this->translate('Comment'),
                'created'   => $this->translate('Created'),
                'user'      => $this->translate('Author')
            ),
            array('message'),
            array('id')
        );

        $this->setupLimitControl();

        $this->setupSortControl(
            array(
                'type'      => $this->translate('Type'),
                'message'   => $this->translate('Comment'),
                'created'   => $this->translate('Created'),
                'user'      => $this->translate('Author')
            ),
            $comments,
            array('created' => 'desc')
        );

        $this->view->comments = $comments;
    }
}
