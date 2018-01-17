<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentCommandForm;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;

/**
 * Display detailed information about a comment
 */
class CommentController extends Controller
{
    /**
     * The fetched comment
     *
     * @var object
     */
    protected $comment;

    /**
     * Fetch the first comment with the given id and add tabs
     */
    public function init()
    {
        $commentId = $this->params->getRequired('comment_id');

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
        ))->where('comment_internal_id', $commentId);
        $this->applyRestriction('monitoring/filter/objects', $query);

        if (false === $this->comment = $query->fetchRow()) {
            $this->httpNotFound($this->translate('Comment not found'));
        }

        $this->getTabs()->add(
            'comment',
            array(
                'icon'  => 'comment-empty',
                'label' => $this->translate('Comment'),
                'title' => $this->translate('Display detailed information about a comment.'),
                'url'   =>'monitoring/comments/show'
            )
        )->activate('comment')->extend(new DashboardAction())->extend(new MenuAction());
    }

    /**
     * Display comment detail view
     */
    public function showAction()
    {
        $this->view->comment = $this->comment;

        if ($this->hasPermission('monitoring/command/comment/delete')) {
            $listUrl = Url::fromPath('monitoring/list/comments')->setQueryString('comment_type=(comment|ack)');
            $form = new DeleteCommentCommandForm();
            $form
                ->populate(array(
                    'comment_id'            => $this->comment->id,
                    'comment_is_service'    => isset($this->comment->service_description),
                    'comment_name'          => $this->comment->name,
                    'redirect'              => $listUrl
                ))
                ->handleRequest();
            $this->view->delCommentForm = $form;
        }
    }
}
