<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Announcement;

use DateTime;
use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Data\Filter\Filter;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryMode;
use Icinga\Web\Form\RepositoryForm;

/**
 * Create, update and delete announcements
 */
class AnnouncementForm extends RepositoryForm
{
    /**
     * Create a new AnnouncementForm
     *
     * @param Repository $repository The repository to work with
     * @param RepositoryMode $mode How to interact with the repository
     * @param ?string $identifier The id of the announcement to handle
     */
    public function __construct(Repository $repository, RepositoryMode $mode, ?string $identifier = null)
    {
        parent::__construct($repository, $mode, $identifier);
        $this->setAttribute('name', 'repo_form_announcement');
    }

    protected function fetchEntry(): object|false
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

    protected function assembleCommonElements(): void
    {
        $this->addElement('text', 'author', [
            'disabled' => ! Icinga::app()->getRequest()->isApiRequest(),
            'required' => true,
            'value'    => Auth::getInstance()->getUser()->getUsername(),
        ]);

        $this->addElement('textarea', 'message', [
            'description' => $this->translate('The message to display to users'),
            'label'       => $this->translate('Message'),
            'required'    => true,
        ]);

        $this->addElement('localDateTime', 'start', [
            'description' => $this->translate('The time to display the announcement from'),
            'label'       => $this->translate('Start'),
            'value'       => (new DateTime('tomorrow')),
            'required'    => true,
        ]);

        $this->addElement('localDateTime', 'end', [
            'description' => $this->translate('The time to display the announcement until'),
            'label'       => $this->translate('End'),
            'value'       => (new DateTime('tomorrow +1day')),
            'required'    => true,
        ]);
    }

    protected function assembleInsertElements(): void
    {
        $this->assembleCommonElements();
        $this->addElement('submit', 'submit_add', ['label' => $this->translate('Create')]);
    }

    protected function assembleUpdateElements(): void
    {
        $this->assembleCommonElements();
        $this->addElement('submit', 'submit_update', ['label' => $this->translate('Save')]);
    }

    protected function assembleDeleteElements(): void
    {
        $this->addElement('submit', 'submit_remove', ['label' => $this->translate('Confirm Removal')]);
    }

    protected function createFilter(): Filter
    {
        return Filter::where('id', $this->getIdentifier());
    }

    /**
     * Get the id of the announcement to handle
     *
     * @return ?string Narrower than the inherited contract, as this form
     *   accepts string identifiers only. Null only in
     *   {@see RepositoryMode::Insert} mode, where none is required.
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }
}
