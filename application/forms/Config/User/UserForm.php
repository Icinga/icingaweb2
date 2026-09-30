<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config\User;

use Icinga\Authentication\PasswordPolicyHelper;
use Icinga\Data\Filter\Filter;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryMode;
use Icinga\Web\Form\RepositoryForm;
use LogicException;

/**
 * Form for creating, updating, and removing database users
 *
 * @extends RepositoryForm<string>
 */
class UserForm extends RepositoryForm
{
    /**
     * Create a new UserForm
     *
     * @param Repository $repository The repository to work with
     * @param RepositoryMode $mode How to interact with the repository
     * @param ?string $identifier The name of the user to handle
     */
    public function __construct(Repository $repository, RepositoryMode $mode, ?string $identifier = null)
    {
        parent::__construct($repository, $mode, $identifier);
        $this->setAttribute('name', 'repo_form_user');
    }

    /**
     * Get the name of the user to handle
     *
     * @return ?string Narrower than the inherited contract, as this form
     *   accepts string identifiers only. Null only in
     *   {@see RepositoryMode::Insert} mode, where none is required.
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Retrieve all form element values
     *
     * Omits passwords considered empty by form validation, including whitespace-only
     * strings, so skipped validation cannot result in a password change.
     *
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        $values = parent::getValues();
        if (array_key_exists('password', $values) && static::isEmptyValue($values['password'])) {
            unset($values['password']);
        }

        return $values;
    }

    /**
     * Create and add common elements to this form
     *
     * @return void
     */
    protected function assembleCommonElements(): void
    {
        $this->addElement('checkbox', 'is_active', [
            'checkedValue'   => '1',
            'uncheckedValue' => '0',
            'value'          => '1',
            'label'          => $this->translate('Active'),
            'description'    => $this->translate('Prevents the user from logging in if unchecked'),
        ]);

        $this->addElement('text', 'user_name', [
            'required' => true,
            'label'    => $this->translate('Username')
        ]);
    }

    /**
     * Create and add elements to this form to insert a user
     *
     * @return void
     */
    protected function assembleInsertElements(): void
    {
        $this->assembleCommonElements();

        $this->addElement('password', 'password', [
            'required' => true,
            'label'    => $this->translate('Password'),
        ]);
        PasswordPolicyHelper::apply($this, 'password', adminFacing: true);

        $this->addElement('submit', 'submit_add', ['label' => $this->translate('Add')]);
    }

    /**
     * Create and add elements to this form to update a user
     *
     * @return void
     */
    protected function assembleUpdateElements(): void
    {
        $this->assembleCommonElements();

        $this->addElement('password', 'password', [
            'description' => $this->translate('Leave empty for not updating the user\'s password'),
            'label'       => $this->translate('Password'),
        ]);

        PasswordPolicyHelper::apply($this, 'password', adminFacing: true);

        $this->addElement('submit', 'submit_update', ['label' => $this->translate('Save')]);
    }

    /**
     * Create and add elements to this form to delete a user
     *
     * @return void
     */
    protected function assembleDeleteElements(): void
    {
        $this->addElement('submit', 'submit_remove', ['label' => $this->translate('Confirm Removal')]);
    }

    /**
     * Create and return a filter to use when updating or deleting a user
     *
     * @return Filter
     *
     * @throws LogicException If no user name is set, which is only the case in {@see RepositoryMode::Insert} mode
     */
    protected function createFilter(): Filter
    {
        return Filter::where(
            'user_name',
            $this->getIdentifier() ?? throw new LogicException('Cannot filter for a user without an identifier'),
        );
    }

    /**
     * Fetch the user to pre-populate the form with
     *
     * The active flag is returned as a string, regardless of the database's own type for it.
     *
     * @return object|false False if the user does not exist
     */
    protected function fetchEntry(): object|false
    {
        $row = parent::fetchEntry();
        if ($row !== false && isset($row->is_active) && is_int($row->is_active)) {
            // pdo_pgsql returns the smallint column as int, while the checkbox only
            // checks itself for its exact checked value '1'.
            $row->is_active = (string) $row->is_active;
        }

        return $row;
    }
}
