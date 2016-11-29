<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Announcement;

use Icinga\Web\Announcement\AnnouncementCookie;
use Icinga\Web\Form;

class AcknowledgeAnnouncementForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAction('announcements/acknowledge');
        $this->setAttrib('class', 'form-inline acknowledge-announcement-control');
        $this->setRedirectUrl('layout/announcements');
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
                'title'         => $this->translate('Acknowledge this announcement'),
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
                    'hash',
                    array(
                        'required' => true,
                        'validators' => array('NotEmpty'),
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
        $cookie = new AnnouncementCookie();
        $acknowledged = $cookie->getAcknowledged();
        $acknowledged[] = $this->getElement('hash')->getValue();
        $cookie->setAcknowledged($acknowledged);
        $this->getResponse()->setCookie($cookie);
        return true;
    }
}
