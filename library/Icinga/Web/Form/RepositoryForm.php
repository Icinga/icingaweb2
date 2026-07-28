<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form;

use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Reducible;
use Icinga\Data\Updatable;
use Icinga\Exception\NotFoundError;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryMode;
use InvalidArgumentException;
use ipl\Html\Contract\Form;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use Psr\Http\Message\ServerRequestInterface;
use Stringable;

/**
 * Form base-class providing standard functionality for extensible, updatable and
 * reducible repositories
 *
 * CSRF protection is enabled by default. Call {@see setCsrfCounterMeasureId()}
 * to set the CSRF form element id or {@see disableCsrfCounterMeasure()}
 * to opt out. The form UID element requires a form name. Set one via the
 * `name` attribute before assembling.
 */
abstract class RepositoryForm extends CompatForm
{
    use CsrfCounterMeasure;
    use FormUid;

    /** @var ?array<string, mixed> The data of the entry to pre-populate the form with */
    protected ?array $data = null;

    /**
     * Create a new repository form
     *
     * Applies the default element decorators and calls the mode-appropriate
     * {@see onInsertRequest()}, {@see onUpdateRequest()}, or {@see onDeleteRequest()}
     * when the form receives a request. $repository must implement the correct
     * interface for the respective $mode:
     *
     * - {@see Extensible} for {@see RepositoryMode::Insert}
     * - {@see Updatable} for {@see RepositoryMode::Update}
     * - {@see Reducible} for {@see RepositoryMode::Delete}
     *
     * @param Repository $repository The repository to work with
     * @param RepositoryMode $mode How to interact with the repository
     * @param string|Stringable|int|null $identifier The identifier of the entry to handle.
     *   Required for {@see RepositoryMode::Update} and {@see RepositoryMode::Delete}.
     *
     * @throws InvalidArgumentException If $repository does not implement the interface
     *   required by $mode, or if $identifier does not match $mode's requirements
     */
    public function __construct(
        protected Repository $repository,
        protected RepositoryMode $mode,
        protected string|Stringable|int|null $identifier = null,
    ) {
        $requiredInterface = $this->mode->getRequiredInterface();

        if (! $this->repository instanceof $requiredInterface) {
            throw new InvalidArgumentException(sprintf(
                'Repository "%s" does not implement %s, which is required for %s mode',
                $this->repository::class,
                $requiredInterface,
                $this->mode->name
            ));
        }

        if ($this->mode->requiresIdentifier() && $this->identifier === null) {
            throw new InvalidArgumentException(sprintf('An identifier is required for %s mode', $this->mode->name));
        }

        $this->applyDefaultElementDecorators();

        $this->on(Form::ON_REQUEST, function (ServerRequestInterface $_, RepositoryForm $form): void {
            match ($this->mode) {
                RepositoryMode::Insert => $form->onInsertRequest(),
                RepositoryMode::Update => $form->onUpdateRequest(),
                RepositoryMode::Delete => $form->onDeleteRequest(),
            };
        });
    }

    /**
     * Create and add elements to this form
     *
     * @return void
     */
    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure();
        $this->addElement($this->createUidElement());

        match ($this->mode) {
            RepositoryMode::Insert => $this->assembleInsertElements(),
            RepositoryMode::Update => $this->assembleUpdateElements(),
            RepositoryMode::Delete => $this->assembleDeleteElements(),
        };
    }

    /**
     * Apply the requested mode on the repository
     *
     * @return void
     */
    protected function onSuccess(): void
    {
        match ($this->mode) {
            RepositoryMode::Insert => $this->insertEntry(),
            RepositoryMode::Update => $this->updateEntry(),
            RepositoryMode::Delete => $this->deleteEntry(),
        };
    }

    /**
     * Get the name of the entry to handle
     *
     * @return string|Stringable|int|null
     */
    public function getIdentifier(): string|Stringable|int|null
    {
        return $this->identifier;
    }

    /**
     * Set the current data of the entry being handled
     *
     * In case of {@see RepositoryMode::Insert} the data will be used as default values.
     * In case of {@see RepositoryMode::Update} the data is the current entry's values.
     *
     * @param array<string, mixed> $data Entry data or default values
     *
     * @return $this
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the extensible repository
     *
     * @return Repository&Extensible
     */
    protected function getExtensibleRepository(): Repository&Extensible
    {
        /** @var Repository&Extensible $repo */
        $repo = $this->repository;

        return $repo;
    }

    /**
     * Get the updatable repository
     *
     * @return Repository&Updatable
     */
    protected function getUpdatableRepository(): Repository&Updatable
    {
        /** @var Repository&Updatable $repo */
        $repo = $this->repository;

        return $repo;
    }

    /**
     * Get the reducible repository
     *
     * @return Repository&Reducible
     */
    protected function getReducibleRepository(): Repository&Reducible
    {
        /** @var Repository&Reducible $repo */
        $repo = $this->repository;

        return $repo;
    }

    /**
     * Fetch and return the entry to pre-populate the form with when in mode update
     *
     * @return object|false False in case of no result
     */
    protected function fetchEntry(): object|false
    {
        return $this->repository
            ->select()
            ->addFilter($this->createFilter())
            ->fetchRow();
    }

    /**
     * Whether the entry supposed to be removed exists
     *
     * @return bool
     */
    protected function entryExists(): bool
    {
        $count = $this->repository
            ->select()
            ->addFilter($this->createFilter())
            ->count();

        return $count > 0;
    }

    /**
     * Insert the new entry
     *
     * @return void
     */
    protected function insertEntry(): void
    {
        $repo = $this->getExtensibleRepository();
        $repo->insert($repo->getBaseTable(), $this->getValues());
    }

    /**
     * Update the entry
     *
     * @return void
     */
    protected function updateEntry(): void
    {
        $repo = $this->getUpdatableRepository();
        $repo->update($repo->getBaseTable(), $this->getValues(), $this->createFilter());
    }

    /**
     * Delete the entry
     *
     * @return void
     */
    protected function deleteEntry(): void
    {
        $repo = $this->getReducibleRepository();
        $repo->delete($repo->getBaseTable(), $this->createFilter());
    }

    /**
     * Prepare the form for mode insert
     *
     * Populates the form with the data passed to setData().
     *
     * @return void
     */
    protected function onInsertRequest(): void
    {
        if (! empty($this->data)) {
            $this->populate($this->data);
        }
    }

    /**
     * Prepare the form for mode update
     *
     * Populates the form with either the data passed to setData() or tries to fetch it
     * from the repository.
     *
     * @return void
     *
     * @throws NotFoundError In case the entry to update cannot be found
     */
    protected function onUpdateRequest(): void
    {
        $data = $this->data;
        if ($data === null) {
            $row = $this->fetchEntry();
            if ($row === false) {
                throw new NotFoundError('Entry "%s" not found', $this->getIdentifier());
            }

            $data = get_object_vars($row);
        }

        $this->populate($data);
    }

    /**
     * Prepare the form for mode delete
     *
     * Verifies that the repository contains the entry to delete.
     *
     * @return void
     *
     * @throws NotFoundError In case the entry to delete cannot be found
     */
    protected function onDeleteRequest(): void
    {
        if (! $this->entryExists()) {
            throw new NotFoundError('Entry "%s" not found', $this->getIdentifier());
        }
    }

    /**
     * Add elements to this form to insert an entry
     *
     * @return void
     */
    abstract protected function assembleInsertElements(): void;

    /**
     * Add elements to this form to update an entry
     *
     * Calls {@see assembleInsertElements()} by default. Overwrite this to add different
     * elements when in mode update.
     *
     * @return void
     */
    protected function assembleUpdateElements(): void
    {
        $this->assembleInsertElements();
    }

    /**
     * Add elements to this form to delete an entry
     *
     * @return void
     */
    abstract protected function assembleDeleteElements(): void;

    /**
     * Create a filter to use when selecting, updating or deleting an entry
     *
     * @return Filter
     */
    abstract protected function createFilter(): Filter;
}
