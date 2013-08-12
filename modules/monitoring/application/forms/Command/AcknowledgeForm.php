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

use Icinga\Web\Form\Element\DateTimePicker;
use Icinga\Web\Form\Element\Note;
use Icinga\Protocol\Commandpipe\Acknowledgement;
use Icinga\Protocol\Commandpipe\Comment;
use Icinga\Util\DateTimeFactory;

/**
 * Form for problem acknowledgements
 */
class AcknowledgeForm extends CommandForm
{
    /**
     * Initialize form
     */
    public function init()
    {
        $this->setName('AcknowledgeForm');
    }

    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'     => t('Comment'),
                'rows'      => 4,
                'required'  => true
            )
        );

        $this->addElement(
            'checkbox',
            'persistent',
            array(
                'label' => t('Persistent comment'),
                'value' => false
            )
        );

        $expireNote = new Note(
            array(
                'name'  => 'expirenote',
                'value' => t('If the acknowledgement should expire, check the box and enter an expiration timestamp.')
            )
        );

        $expireCheck = $this->createElement(
            'checkbox',
            'expire',
            array(
                'label' => t('Use expire time')
            )
        );

        if ($this->getRequest()->getPost('expire', '0') === '1') {
            $now = DateTimeFactory::create();
            $expireTime = new DateTimePicker(
                array(
                    'name'  => 'expiretime',
                    'label' => t('Expire time'),
                    'value' => $now->getTimestamp() + 3600
                )
            );

            $this->addElements(array($expireNote, $expireCheck, $expireTime));
        } else {
            $this->addElements(array($expireNote, $expireCheck));
        }

        $this->enableAutoSubmit(array('expire'));

        $this->addDisplayGroup(
            array(
                'expirenote',
                'expire',
                'expiretime'
            ),
            'expire_group',
            array(
                'legend' => t('Expire acknowledgement')
            )
        );

        $this->addElement(
            'checkbox',
            'sticky',
            array(
                'label' => t('Sticky acknowledgement'),
                'value' => false
            )
        );

        $this->addElement(
            'checkbox',
            'notify',
            array(
                'label' => t('Send notification'),
                'value' => false
            )
        );

        $this->setSubmitLabel(t('Acknowledge problem'));

        parent::create();
    }

    /**
     * Add validator for dependent fields
     *
     * @param   array   $data
     * @see     \Icinga\Web\Form::preValidation()
     */
    protected function preValidation(array $data)
    {
        if (isset($data['expire']) && intval($data['expire']) === 1) {
            $expireTime = $this->getElement('expiretime');
            $expireTime->setRequired(true);
        }
    }

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
