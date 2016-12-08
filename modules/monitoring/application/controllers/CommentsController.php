<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentsCommandForm;
use Icinga\Web\Url;

/**
 * Display detailed information about comments
 */
class CommentsController extends Controller
{
    /**
     * The comments view
     *
     * @var \Icinga\Module\Monitoring\DataView\Comment
     */
    protected $comments;

    /**
     * Filter from request
     *
     * @var Filter
     */
    protected $filter;

    /**
     * Fetch all comments matching the current filter and add tabs
     */
    public function init()
    {
        $this->filter = Filter::fromQueryString(str_replace(
            'comment_id',
            'comment_internal_id',
            (string) $this->params
        ));
        $query = $this->backend->select()->from('comment', array(
            'id'         => 'comment_internal_id',
            'objecttype' => 'object_type',
            'comment'    => 'comment_data',
            'author'     => 'comment_author_name',
            'timestamp'  => 'comment_timestamp',
            'type'       => 'comment_type',
            'persistent' => 'comment_is_persistent',
            'expiration' => 'comment_expiration',
            'name'       => 'comment_name',
            'host_name',
            'service_description',
            'host_display_name',
            'service_display_name'
        ))->addFilter($this->filter);
        $this->applyRestriction('monitoring/filter/objects', $query);

        $this->comments = $query;

        $this->getTabs()->add(
            'comments',
            array(
                'icon'  => 'comment-empty',
                'label' => $this->translate('Comments') . sprintf(' (%d)', $query->count()),
                'title' => $this->translate(
                    'Display detailed information about multiple comments.'
                ),
                'url'   =>'monitoring/comments/show'
            )
        )->activate('comments');
    }

    /**
     * Display the detail view for a comment list
     */
    public function showAction()
    {
        $this->view->comments = $this->comments;
        $this->view->listAllLink = Url::fromPath('monitoring/list/comments')
            ->setQueryString($this->filter->toQueryString());
        $this->view->removeAllLink = Url::fromPath('monitoring/comments/delete-all')
            ->setParams($this->params);
    }

    /**
     * Display the form for removing a comment list
     */
    public function deleteAllAction()
    {
        $this->assertPermission('monitoring/command/comment/delete');

        $listCommentsLink = Url::fromPath('monitoring/list/comments')
            ->setQueryString('comment_type=(comment|ack)');
        $delCommentForm = new DeleteCommentsCommandForm();
        $delCommentForm->setTitle($this->view->translate('Remove all Comments'));
        $delCommentForm->addDescription(sprintf(
            $this->translate('Confirm removal of %d comments.'),
            $this->comments->count()
        ));
        $delCommentForm->setComments($this->comments->fetchAll())
            ->setRedirectUrl($listCommentsLink)
            ->handleRequest();
        $this->view->delCommentForm = $delCommentForm;
        $this->view->comments = $this->comments;
        $this->view->listAllLink = Url::fromPath('monitoring/list/comments')
            ->setQueryString($this->filter->toQueryString());
    }
}
