<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Monitoring\Form\Command;

use \Icinga\Web\Form\Element\DateTimePicker;
use \Icinga\Protocol\Commandpipe\Acknowledgement;
use \Icinga\Protocol\Commandpipe\Comment;
use \Icinga\Util\DateTimeFactory;

/**
 * Form for problem acknowledgements
 */
class AcknowledgeForm extends CommandForm
{
    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(
            t(
                'This command is used to acknowledge host or service problems. When a problem is '
                . 'acknowledged, future notifications about problems are temporarily disabled until the '
                . 'host/service changes from its current state.'
            )
        );

        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'     => t('Comment'),
                'rows'      => 4,
                'required'  => true,
                'helptext'  => t(
                    ' If you work with other administrators you may find it useful to share information '
                    . 'about a host or service that is having problems if more than one of you may be working on '
                    . 'it. Make sure you enter a brief description of what you are doing.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'persistent',
            array(
                'label'    => t('Persistent Comment'),
                'value'    => false,
                'helptext' => t(
                    'If you would like the comment to remain even when the acknowledgement is removed, '
                    . 'check this option.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'expire',
            array(
                'label'    => t('Use Expire Time'),
                'helptext' => t('If the acknowledgement should expire, check this option.')
            )
        );
        $this->enableAutoSubmit(array('expire'));

        if ($this->getRequest()->getPost('expire', '0') === '1') {
            $now = DateTimeFactory::create();
            $this->addElement(
                new DateTimePicker(
                    array(
                        'name'      => 'expiretime',
                        'label'     => t('Expire Time'),
                        'value'     => $now->getTimestamp() + 3600,
                        'patterns'  => $this->getValidDateTimeFormats(),
                        'helptext'  => t(
                            'Enter the expire date/time for this acknowledgement here. Icinga will '
                            . ' delete the acknowledgement after this date expired.'
                        )
                    )
                )
            );
        }

        $this->addElement(
            'checkbox',
            'sticky',
            array(
                'label'    => t('Sticky Acknowledgement'),
                'value'    => true,
                'helptext' => t(
                    'If you want the acknowledgement to disable notifications until the host/service '
                    . 'recovers, check this option.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'notify',
            array(
                'label'    => t('Send Notification'),
                'value'    => true,
                'helptext' => t(
                    'If you do not want an acknowledgement notification to be sent out to the appropriate '
                    . 'contacts, uncheck this option.'
                )
            )
        );

        $this->setSubmitLabel(t('Acknowledge Problem'));

        parent::create();
    }

    /**
     * Add validator for dependent fields
     *
     * @param   array $data
     *
     * @see     \Icinga\Web\Form::preValidation()
     */
    protected function preValidation(array $data)
    {
        if (isset($data['expire']) && intval($data['expire']) === 1) {
            $expireTime = $this->getElement('expiretime');
            $expireTime->setRequired(true);
        }
    }

    /**
     * Create acknowledgement from request data
     *
     * @return \Icinga\Protocol\Commandpipe\Acknowledgement
     */
    public function getAcknowledgement()
    {
        $expireTime = -1;
        if ($this->getValue('expire')) {
            $expireTime = $this->getValue('expiretime');
        }
        return new Acknowledgement(
            new Comment(
                $this->getAuthorName(),
                $this->getValue('comment'),
                $this->getValue('persistent')
            ),
            $this->getValue('notify'),
            $expireTime,
            $this->getValue('sticky')
        );
    }
}
