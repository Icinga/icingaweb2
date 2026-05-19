<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Announcement;

use Icinga\Data\Filter\Filter;
use Icinga\Web\Announcement\AnnouncementCookie;
use Icinga\Web\Announcement\AnnouncementIniRepository;
use Icinga\Web\Form;
use Icinga\Web\Url;

class AcknowledgeAnnouncementForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAction(Url::fromPath('announcements/acknowledge'));
        $this->setAttrib('class', 'acknowledge-announcement-control');
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
            [
                'class'         => 'link-button spinner',
                'decorators'    => [
                    'ViewHelper',
                    ['HtmlTag', ['tag' => 'div', 'class' => 'control-group form-controls']]
                ],
                'escape'        => false,
                'ignore'        => true,
                'label'         => $this->getView()->icon('cancel'),
                'title'         => $this->translate('Acknowledge this announcement'),
                'type'          => 'submit'
            ]
        );
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData = [])
    {
        $this->addElements(
            [
                [
                    'hidden',
                    'hash',
                    [
                        'required' => true,
                        'validators' => ['NotEmpty'],
                        'decorators' => ['ViewHelper']
                    ]
                ]
            ]
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        $cookie = new AnnouncementCookie();
        $repo = new AnnouncementIniRepository();
        $query = $repo->findActive();
        $filter = [];
        foreach ($cookie->getAcknowledged() as $hash) {
            $filter[] = Filter::expression('hash', '=', $hash);
        }
        $query->addFilter(Filter::matchAny($filter));
        $acknowledged = [];
        foreach ($query as $row) {
            $acknowledged[] = $row->hash;
        }
        $acknowledged[] = $this->getElement('hash')->getValue();
        $cookie->setAcknowledged($acknowledged);
        $this->getResponse()->setCookie($cookie);
        return true;
    }
}
