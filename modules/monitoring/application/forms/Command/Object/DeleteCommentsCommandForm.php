<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteCommentCommand;
use Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for deleting host or service comments
 */
class DeleteCommentsCommandForm extends CommandForm
{
    /**
     * The comments to delete
     *
     * @var array
     */
    protected $comments;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }

    /**
     * Set the comments to delete
     *
     * @param   array $comments
     *
     * @return  $this
     */
    public function setComments(array $comments)
    {
        $this->comments = $comments;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(array(
            array(
                'hidden',
                'redirect',
                array('decorators' => array('ViewHelper'))
            )
        ));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural('Remove', 'Remove All', count($this->comments));
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        foreach ($this->comments as $comment) {
            $cmd = new DeleteCommentCommand();
            $cmd
                ->setCommentId($comment->id)
                ->setCommentName($comment->name)
                ->setIsService(isset($comment->service_description));
            $this->getTransport($this->request)->send($cmd);
        }
        $redirect = $this->getElement('redirect')->getValue();
        if (! empty($redirect)) {
            $this->setRedirectUrl($redirect);
        }
        Notification::success(
            $this->translatePlural('Deleting comment..', 'Deleting comments..', count($this->comments))
        );
        return true;
    }
}
