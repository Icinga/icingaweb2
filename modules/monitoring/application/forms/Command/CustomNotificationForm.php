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

namespace Icinga\Module\Monitoring\Form\Command;

use \Icinga\Protocol\Commandpipe\CustomNotification;

/**
 * For for command CustomNotification
 */
class CustomNotificationForm extends CommandForm
{
    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(
            t(
                'This command is used to send a custom notification about hosts or services. Useful in '
                . 'emergencies when you need to notify admins of an issue regarding a monitored system or '
                . 'service.'
            )
        );

        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'    => t('Comment'),
                'rows'     => 4,
                'required' => true,
                'helptext' => t(
                    'If you work with other administrators, you may find it useful to share information '
                    . 'about a host or service that is having problems if more than one of you may be working on '
                    . 'it. Make sure you enter a brief description of what you are doing.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'forced',
            array(
                'label'    => t('Forced'),
                'helptext' => t(
                    'Custom notifications normally follow the regular notification logic in Icinga. Selecting this '
                    . 'option will force the notification to be sent out, regardless of time restrictions, '
                    . 'whether or not notifications are enabled, etc.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'broadcast',
            array(
                'label'    => t('Broadcast'),
                'helptext' => t(
                    'Selecting this option causes the notification to be sent out to all normal (non-escalated) '
                    . ' and escalated contacts. These options allow you to override the normal notification logic '
                    . 'if you need to get an important message out.'
                )
            )
        );

        $this->setSubmitLabel(t('Send Custom Notification'));

        parent::create();
    }

    /**
     * Create Custom Notification from request data
     *
     * @return CustomNotification
     */
    public function getCustomNotification()
    {
        return new CustomNotification(
            $this->getAuthorName(),
            $this->getValue('comment'),
            $this->getValue('forced'),
            $this->getValue('broadcast')
        );
    }
}
