<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config\User;

use Icinga\Authentication\Auth;
use Icinga\Authentication\PasswordPolicyHelper;
use Icinga\Data\Filter\Filter;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryMode;
use Icinga\User;
use Icinga\Web\Form\RepositoryForm;

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
     * Create and add elements to this form to insert or update a user
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
        PasswordPolicyHelper::apply($this, new User($this->getValue('user_name', '')), 'password', adminFacing: true);

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

        $user = new User($this->getIdentifier());
        $user->setAdditional('backend_name', $this->repository->getName());
        Auth::getInstance()->setupUser($user);
        PasswordPolicyHelper::apply($this, $user, 'password', adminFacing: true);

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
     */
    protected function createFilter(): Filter
    {
        return Filter::where('user_name', $this->getIdentifier());
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
     * Strips off the password if null or an empty string.
     *
     * @return array
     */
    public function getValues(): array
    {
        $values = parent::getValues();
        if (array_key_exists('password', $values) && ($values['password'] === '' || $values['password'] === null)) {
            unset($values['password']);
        }

        return $values;
    }
}
