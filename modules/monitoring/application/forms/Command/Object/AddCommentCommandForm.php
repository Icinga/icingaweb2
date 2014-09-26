<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use Icinga\Module\Monitoring\Command\Object\AddCommentCommand;
use Icinga\Web\Form\Element\Note;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for adding host or service comments
 */
class AddCommentCommandForm extends ObjectsCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return mtp(
            'monitoring', 'Add comment', 'Add comments', count($this->objects)
        );
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(array(
            new Note(
                'command-info',
                array(
                    'value' => mt(
                        'monitoring',
                        'This command is used to add host or service comments.'
                    )
                )
            ),
            array(
                'textarea',
                'comment',
                array(
                    'required'      => true,
                    'label'         => mt('monitoring', 'Comment'),
                    'description'   => mt(
                        'monitoring',
                        'If you work with other administrators, you may find it useful to share information about the'
                        . ' the host or service that is having problems. Make sure you enter a brief description of'
                        . ' what you are doing.'
                    )
                )
            ),
            array(
                'checkbox',
                'persistent',
                array(
                    'label'         => mt('monitoring', 'Persistent'),
                    'value'         => true,
                    'description'   => mt(
                        'monitoring',
                        'If you uncheck this option, the comment will automatically be deleted the next time Icinga is'
                        . ' restarted.'
                    )
                )
            )
        ));
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
            $comment = new AddCommentCommand();
            $comment->setObject($object);
            $comment->setComment($this->getElement('comment')->getValue());
            $comment->setAuthor($request->getUser()->getUsername());
            $comment->setPersistent($this->getElement('persistent')->isChecked());
            $this->getTransport($request)->send($comment);
        }
        Notification::success(mtp(
            'monitoring',
            'Adding comment..',
            'Adding comments..',
            count($this->objects)
        ));
        return true;
    }
}
