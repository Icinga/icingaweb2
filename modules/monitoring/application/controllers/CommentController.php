<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentCommandForm;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Display detailed information about a comment
 */
class Monitoring_CommentController extends Controller
{
    protected $comment;

    /**
     * Add tabs
     */
    public function init()
    {
        $commentId = $this->params->get('comment_id');
         
        $this->comment = $this->backend->select()->from('comment', array(
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
        ))->where('comment_internal_id', $commentId)->getQuery()->fetchRow();
        
        if (false === $this->comment) {
            throw new Zend_Controller_Action_Exception($this->translate('Comment not found'));
        }
         
        $this->getTabs()
            ->add(
                'comment',
                array(
                    'title' => $this->translate(
                        'Display detailed information about a comment.'
                    ),
                    'icon' => 'comment',
                    'label' => $this->translate('Comment'),
                    'url'   =>'monitoring/comments/show'
                )
        )->activate('comment')->extend(new DashboardAction());
    }
    
    public function showAction()
    {
        $this->view->comment = $this->comment;
        if ($this->hasPermission('monitoring/command/comment/delete')) {
            $this->view->delCommentForm = $this->createDelCommentForm();
        }
    }
    
    private function createDelCommentForm()
    {
        $this->assertPermission('monitoring/command/comment/delete');
        
        $delCommentForm = new DeleteCommentCommandForm();
        $delCommentForm->setAction(
            Url::fromPath('monitoring/comment/show')
                ->setParam('comment_id', $this->comment->id)
        );
        $delCommentForm->populate(
            array(
                'redirect' => Url::fromPath('monitoring/list/comments'),
                'comment_id' => $this->comment->id
            )
        );
        $delCommentForm->handleRequest();
        return $delCommentForm;
    }
}
