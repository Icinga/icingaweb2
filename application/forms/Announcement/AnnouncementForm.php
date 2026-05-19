<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Announcement;

use DateTime;
use Icinga\Authentication\Auth;
use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;

/**
 * Create, update and delete announcements
 */
class AnnouncementForm extends RepositoryForm
{
    protected function fetchEntry()
    {
        $entry = parent::fetchEntry();
        if ($entry !== false) {
            if ($entry->start !== null) {
                $entry->start = (new DateTime())->setTimestamp($entry->start);
            }
            if ($entry->end !== null) {
                $entry->end = (new DateTime())->setTimestamp($entry->end);
            }
        }

        return $entry;
    }

    /**
     * {@inheritDoc}
     */
    protected function createInsertElements(array $formData)
    {
        $this->addElement(
            'text',
            'author',
            [
                'disabled'  => ! $this->getRequest()->isApiRequest(),
                'required'  => true,
                'value'     => Auth::getInstance()->getUser()->getUsername()
            ]
        );
        $this->addElement(
            'textarea',
            'message',
            [
                'description'   => $this->translate('The message to display to users'),
                'label'         => $this->translate('Message'),
                'required'      => true
            ]
        );
        $this->addElement(
            'dateTimePicker',
            'start',
            [
                'description'   => $this->translate('The time to display the announcement from'),
                'label'         => $this->translate('Start'),
                'placeholder'   => new DateTime('tomorrow'),
                'required'      => true
            ]
        );
        $this->addElement(
            'dateTimePicker',
            'end',
            [
                'description'   => $this->translate('The time to display the announcement until'),
                'label'         => $this->translate('End'),
                'placeholder'   => new DateTime('tomorrow +1day'),
                'required'      => true
            ]
        );

        $this->setTitle($this->translate('Create a new announcement'));
        $this->setSubmitLabel($this->translate('Create'));
    }
    /**
     * {@inheritDoc}
     */
    protected function createUpdateElements(array $formData)
    {
        $this->createInsertElements($formData);
        $this->setTitle(sprintf($this->translate('Edit announcement %s'), $this->getIdentifier()));
        $this->setSubmitLabel($this->translate('Save'));
    }

    /**
     * {@inheritDoc}
     */
    protected function createDeleteElements(array $formData)
    {
        $this->setTitle(sprintf($this->translate('Remove announcement %s?'), $this->getIdentifier()));
        $this->setSubmitLabel($this->translate('Confirm Removal'));
        $this->setAttrib('class', 'icinga-controls');
    }

    /**
     * {@inheritDoc}
     */
    protected function createFilter()
    {
        return Filter::where('id', $this->getIdentifier());
    }

    /**
     * {@inheritDoc}
     */
    protected function getInsertMessage($success)
    {
        return $success
            ? $this->translate('Announcement created')
            : $this->translate('Failed to create announcement');
    }

    /**
     * {@inheritDoc}
     */
    protected function getUpdateMessage($success)
    {
        return $success
            ? $this->translate('Announcement updated')
            : $this->translate('Failed to update announcement');
    }

    /**
     * {@inheritDoc}
     */
    protected function getDeleteMessage($success)
    {
        return $success
            ? $this->translate('Announcement removed')
            : $this->translate('Failed to remove announcement');
    }
}
