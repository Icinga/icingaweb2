<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentsCommandForm;
use Icinga\Web\Url;
use Icinga\Data\Filter\Filter;

/**
 * Display detailed information about a comment
 */
class Monitoring_CommentsController extends Controller
{
    /**
     * The fetched comments
     *
     * @var array
     */
    protected $comments;

    /**
     * Fetch all comments matching the current filter and add tabs
     *
     * @throws Zend_Controller_Action_Exception
     */
    public function init()
    {
        $this->filter = Filter::fromQueryString(str_replace(
            'comment_id',
            'comment_internal_id',
            (string)$this->params
        ));
        $this->comments = $this->backend->select()->from('comment', array(
            'id'         => 'comment_internal_id',
            'objecttype' => 'comment_objecttype',
            'comment'    => 'comment_data',
            'author'     => 'comment_author_name',
            'timestamp'  => 'comment_timestamp',
            'type'       => 'comment_type',
            'persistent' => 'comment_is_persistent',
            'expiration' => 'comment_expiration',
            'host_name',
            'service_description',
            'host_display_name',
            'service_display_name'
        ))->addFilter($this->filter)->getQuery()->fetchAll();
        
        if (false === $this->comments) {
            throw new Zend_Controller_Action_Exception($this->translate('Comment not found'));
        }
         
        $this->getTabs()->add(
            'comments',
            array(
                'title' => $this->translate(
                    'Display detailed information about multiple comments.'
                ),
                'icon'  => 'comment',
                'label' => $this->translate('Comments') . sprintf(' (%d)', count($this->comments)),
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
            count($this->comments)
        ));
        $delCommentForm->setComments($this->comments)
            ->setRedirectUrl($listCommentsLink)
            ->handleRequest();
        $this->view->delCommentForm = $delCommentForm;
        $this->view->comments = $this->comments;
        $this->view->listAllLink = Url::fromPath('monitoring/list/comments')
                ->setQueryString($this->filter->toQueryString());
    }
}
