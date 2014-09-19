<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteCommentCommand;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for deleting host or service comments
 */
class DeleteCommentCommandForm extends ObjectsCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setAttrib('class', 'inline link-like');
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
                'comment_id',
                array(
                    'required' => true
                )
            ),
            array(
                'hidden',
                'redirect'
            )
        ));
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::addSubmitButton() For the method documentation.
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            array(
                'ignore'        => true,
                'label'         => 'X',
                'title'         => mt('monitoring', 'Delete comment'),
                'decorators'    => array('ViewHelper')
            )
        );
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess(Request $request)
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $delComment = new DeleteCommentCommand();
            $delComment
                ->setObject($object)
                ->setCommentId($this->getElement('comment_id')->getValue());
            $this->getTransport($request)->send($delComment);
        }
        $redirect = $this->getElement('redirect')->getValue();
        if (! empty($redirect)) {
            $this->setRedirectUrl($redirect);
        }
        Notification::success(mt('monitoring', 'Deleting comment..'));
        return true;
    }
}
