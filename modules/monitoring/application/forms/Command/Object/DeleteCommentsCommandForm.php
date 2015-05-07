<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteCommentCommand;
use \Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for deleting host or service comments
 */
class DeleteCommentsCommandForm extends CommandForm
{
    /**
     * The comments deleted on success
     *
     * @var array
     */
    protected $comments;

    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
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
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural('Remove', 'Remove All', count($this->downtimes));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        foreach ($this->comments as $comment) {
            $cmd = new DeleteCommentCommand();
            $cmd->setCommentId($comment->id)
                ->setIsService(isset($comment->service_description));
            $this->getTransport($this->request)->send($cmd);
        }
        $redirect = $this->getElement('redirect')->getValue();
        if (! empty($redirect)) {
            $this->setRedirectUrl($redirect);
        }
        Notification::success($this->translate('Deleting comment..'));
        return true;
    }

    /**
     * Set the comments to be deleted upon success
     *
     * @param array $comments
     *
     * @return this             fluent interface
     */
    public function setComments(array $comments)
    {
        $this->comments = $comments;
        return $this;
    }
}
