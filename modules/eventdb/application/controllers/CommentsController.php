<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Eventdb\Controllers;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Eventdb\EventdbController;
use Icinga\Module\Eventdb\Forms\Event\EventCommentForm;
use Icinga\Web\Url;

class CommentsController extends EventdbController
{
    public function newAction()
    {
        $this->getTabs()->add('new-comment', array(
            'title' => $this->translate('New Comment'),
            'url'   => Url::fromRequest()
        ))->activate('new-comment');

        $commentForm = new EventCommentForm();
        $commentForm
            ->setDb($this->getDb())
            ->setFilter(Filter::fromQueryString((string) $this->params))
            ->setRedirectUrl('eventdb/events')
            ->handleRequest();
        $this->view->form = $commentForm;
    }
}
