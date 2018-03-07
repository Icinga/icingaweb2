<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteCommentCommand;
use Icinga\Web\Notification;

/**
 * Form for deleting host or service comments
 */
class DeleteCommentCommandForm extends ObjectsCommandForm
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }

    /**
     * {@inheritdoc}
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'button',
            'btn_submit',
            array(
                'class'         => 'link-button spinner',
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                ),
                'escape'        => false,
                'ignore'        => true,
                'label'         => $this->getView()->icon('cancel'),
                'title'         => $this->translate('Delete this comment'),
                'type'          => 'submit'
            )
        );
        return $this;
    }

    /**
     * {@inheritdoc}
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
                    'comment_instance_name',
                    array(
                        'decorators'    => array('ViewHelper')
                    )
                ),
                array(
                    'hidden',
                    'comment_name',
                    array(
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
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        $cmd = new DeleteCommentCommand();
        $cmd
            ->setCommentId($this->getElement('comment_id')->getValue())
            ->setCommentName($this->getElement('comment_name')->getValue());

        if (($obj = $this->getObject()) !== null) {
            $cmd->setObject($obj);
        } elseif (($instance = $this->getElement('comment_instance_name')->getValue()) !== null) {
            $cmd->setInstance($instance);
        }

        if ($obj === null) {
            $cmd->setIsService($this->getElement('comment_is_service')->getValue());
        }

        $this->getTransport($this->request)->send($cmd);
        $redirect = $this->getElement('redirect')->getValue();
        if (! empty($redirect)) {
            $this->setRedirectUrl($redirect);
        }
        Notification::success($this->translate('Deleting comment..'));
        return true;
    }

    public function setInstance($instance)
    {
        if (($el = $this->getElement('comment_instance_name')) !== null) {
            $el->setValue($instance);
        }
        return parent::setInstance($instance);
    }

    public function populate(array $defaults)
    {
        $k = 'comment_instance_name';
        if (! array_key_exists($k, $defaults)) {
            $defaults[$k] = $this->getInstance();
        }
        return parent::populate($defaults);
    }
}
