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

use \Icinga\Protocol\Commandpipe\Comment;

/**
 * Form for adding comment commands
 */
class CommentForm extends CommandForm
{
    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(t('This command is used to add a comment to hosts or services.'));

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
            'persistent',
            array(
                'label'    => t('Persistent'),
                'value'    => true,
                'helptext' => t(
                    'If you uncheck this option, the comment will automatically be deleted the next time '
                    . 'Icinga is restarted.'
                )
            )
        );

        $this->setSubmitLabel(t('Post Comment'));

        parent::create();
    }

    /**
     * Create comment from request data
     *
     * @return \Icinga\Protocol\Commandpipe\Comment
     */
    public function getComment()
    {
        return new Comment($this->getAuthorName(), $this->getValue('comment'), $this->getValue('persistent'));
    }
}
