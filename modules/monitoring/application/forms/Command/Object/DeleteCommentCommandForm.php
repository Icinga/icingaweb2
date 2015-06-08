<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteCommentCommand;
use Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for deleting host or service comments
 */
class DeleteCommentCommandForm extends CommandForm
{
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
         $this->addElements(
            array(
                array(
                    'hidden',
                    'comment_id',
                    array(
                        'required' => true,
                        'validators' => array('NotEmpty'),
                        'decorators' => array('ViewHelper')
                    )
                ),
                array(
                    'hidden',
                    'comment_is_service',
                    array(
                        'filters' => array('Boolean'),
                        'decorators' => array('ViewHelper')
                    )
                ),
                array(
                    'hidden',
                    'redirect',
                    array(
                        'decorators' => array('ViewHelper')
                    )
                )
            )
        );
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::addSubmitButton() For the method documentation.
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'button',
            'btn_submit',
            array(
                'ignore'        => true,
                'escape'        => false,
                'type'          => 'submit',
                'class'         => 'link-like',
                'label'         => $this->getView()->icon('trash'),
                'title'         => $this->translate('Delete this comment'),
                'decorators'    => array('ViewHelper')
            )
        );
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        $cmd = new DeleteCommentCommand();
        $cmd->setIsService($this->getElement('comment_is_service')->getValue())
             ->setCommentId($this->getElement('comment_id')->getValue());
        $this->getTransport($this->request)->send($cmd);
        $redirect = $this->getElement('redirect')->getValue();
        if (! empty($redirect)) {
            $this->setRedirectUrl($redirect);
        }
        Notification::success($this->translate('Deleting comment..'));
        return true;
    }
}
