<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config\UserGroup;

use Icinga\Data\Filter\Filter;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryMode;
use Icinga\Web\Form\RepositoryForm;
use ipl\Web\Common\CalloutType;
use ipl\Web\Widget\Callout;

class UserGroupForm extends RepositoryForm
{
    /**
     * Create a new UserGroupForm
     *
     * @param Repository $repository The repository to work with
     * @param RepositoryMode $mode How to interact with the repository
     * @param ?string $identifier The name of the user group to handle
     */
    public function __construct(Repository $repository, RepositoryMode $mode, ?string $identifier = null)
    {
        parent::__construct($repository, $mode, $identifier);
        $this->setAttribute('name', 'repo_form_user_group');
    }

    protected function assembleCommonElements(): void
    {
        $this->addElement('text', 'group_name', [
            'required' => true,
            'label'    => $this->translate('Group Name'),
        ]);
    }

    /**
     * Create and add elements to this form to insert or update a group
     *
     * @return void
     */
    protected function assembleInsertElements(): void
    {
        $this->assembleCommonElements();

        $this->addElement('submit', 'submit_add', ['label' => $this->translate('Add')]);
    }

    protected function assembleUpdateElements(): void
    {
        $this->assembleCommonElements();

        $this->addElement('submit', 'submit_update', ['label' => $this->translate('Save')]);
    }

    /**
     * Create and add elements to this form to delete a group
     *
     * @return void
     */
    protected function assembleDeleteElements(): void
    {
        $this->addHtml(new Callout(CalloutType::Info, $this->translate(
            'Note that all users that are currently a member of this group will'
            . ' have their membership cleared automatically.'
        )));

        $this->addElement('submit', 'submit_remove', ['label' => $this->translate('Confirm Removal')]);
    }

    /**
     * Create and return a filter to use when updating or deleting a group
     *
     * @return  Filter
     */
    protected function createFilter(): Filter
    {
        return Filter::where('group_name', $this->getIdentifier());
    }

    /**
     * Get the name of the user group to handle
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
