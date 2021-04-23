<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use DateInterval;
use DateTime;
use Icinga\Application\Config;
use Icinga\Module\Monitoring\Command\Object\AddCommentCommand;
use Icinga\Web\Notification;

/**
 * Form for adding host or service comments
 */
class AddCommentCommandForm extends ObjectsCommandForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->addDescription($this->translate('This command is used to add host or service comments.'));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural('Add comment', 'Add comments', count($this->objects));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElement(
            'textarea',
            'comment',
            array(
                'required'      => true,
                'label'         => $this->translate('Comment'),
                'description'   => $this->translate(
                    'If you work with other administrators, you may find it useful to share information about'
                    . ' the host or service that is having problems. Make sure you enter a brief description of'
                    . ' what you are doing.'
                ),
                'attribs'       => array('class' => 'autofocus')
            )
        );
        if (! $this->getBackend()->isIcinga2()) {
            $this->addElement(
                'checkbox',
                'persistent',
                array(
                    'label'         => $this->translate('Persistent'),
                    'value'         => (bool) Config::module('monitoring')->get('settings', 'comment_persistent', true),
                    'description'   => $this->translate(
                        'If you uncheck this option, the comment will automatically be deleted the next time Icinga is'
                        . ' restarted.'
                    )
                )
            );
        }

        if (version_compare($this->getBackend()->getProgramVersion(), '2.13.0', '>=')) {
            $config = Config::module('monitoring');
            $commentExpire = (bool) $config->get('settings', 'comment_expire', false);

            $this->addElement(
                'checkbox',
                'expire',
                [
                    'label'         => $this->translate('Use Expire Time'),
                    'value'         => $commentExpire,
                    'description'   => $this->translate('If the comment should expire, check this option.'),
                    'autosubmit'    => true
                ]
            );

            if (isset($formData['expire']) ? $formData['expire'] : $commentExpire) {
                $expireTime = new DateTime();
                $expireTime->add(new DateInterval($config->get('settings', 'comment_expire_time', 'PT1H')));

                $this->addElement(
                    'dateTimePicker',
                    'expire_time',
                    [
                        'label'         => $this->translate('Expire Time'),
                        'value'         => $expireTime,
                        'description'   => $this->translate(
                            'Enter the expire date and time for this comment here. Icinga will delete the'
                            . ' comment after this time expired.'
                        )
                    ]
                );

                $this->addDisplayGroup(
                    ['expire', 'expire_time'],
                    'expire-expire_time',
                    [
                        'decorators' => [
                            'FormElements',
                            ['HtmlTag', ['tag' => 'div']]
                        ]
                    ]
                );
            }
        }

        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $comment = new AddCommentCommand();
            $comment->setObject($object);
            $comment->setComment($this->getElement('comment')->getValue());
            $comment->setAuthor($this->request->getUser()->getUsername());
            if (($persistent = $this->getElement('persistent')) !== null) {
                $comment->setPersistent($persistent->isChecked());
            }

            $expire = $this->getElement('expire');

            if ($expire !== null && $expire->isChecked()) {
                $comment->setExpireTime($this->getElement('expire_time')->getValue()->getTimestamp());
            }

            $this->getTransport($this->request)->send($comment);
        }
        Notification::success($this->translatePlural(
            'Adding comment..',
            'Adding comments..',
            count($this->objects)
        ));
        return true;
    }
}
