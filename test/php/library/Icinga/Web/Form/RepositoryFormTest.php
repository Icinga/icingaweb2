<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web\Form;

use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Reducible;
use Icinga\Data\Updatable;
use Icinga\Exception\NotFoundError;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryMode;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Form\RepositoryForm;
use InvalidArgumentException;

/**
 * Tests for {@see RepositoryForm}
 */
class RepositoryFormTest extends BaseTestCase
{
    private function makeExtensibleRepository(): Repository&Extensible
    {
        /** @var Repository&Extensible $repository */
        $repository = new class () extends Repository implements Extensible {
            /** @var list<array{target: string, data: array<string, mixed>}> */
            public array $insertCalls = [];

            public function __construct()
            {
                // Bypass the parent constructor: unit tests need no datasource.
            }

            public function getBaseTable(): string
            {
                return 'test_table';
            }

            public function insert($target, array $data): void
            {
                $this->insertCalls[] = ['target' => $target, 'data' => $data];
            }
        };

        return $repository;
    }

    private function makeUpdatableRepository(): Repository&Updatable
    {
        /** @var Repository&Updatable $repository */
        $repository = new class () extends Repository implements Updatable {
            /** @var list<array{target: string, data: array<string, mixed>, filter: ?Filter}> */
            public array $updateCalls = [];

            public function __construct()
            {
                // Bypass the parent constructor: unit tests need no datasource.
            }

            public function getBaseTable(): string
            {
                return 'test_table';
            }

            public function update($target, array $data, ?Filter $filter = null): void
            {
                $this->updateCalls[] = ['target' => $target, 'data' => $data, 'filter' => $filter];
            }
        };

        return $repository;
    }

    private function makeReducibleRepository(): Repository&Reducible
    {
        /** @var Repository&Reducible $repository */
        $repository = new class () extends Repository implements Reducible {
            /** @var list<array{target: string, filter: ?Filter}> */
            public array $deleteCalls = [];

            public function __construct()
            {
                // Bypass the parent constructor: unit tests need no datasource.
            }

            public function getBaseTable(): string
            {
                return 'test_table';
            }

            public function delete($target, ?Filter $filter = null): void
            {
                $this->deleteCalls[] = ['target' => $target, 'filter' => $filter];
            }
        };

        return $repository;
    }

    public function testInsertModeRequiresAnExtensibleRepository(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repo = $this->makeUpdatableRepository(); // Updatable, not Extensible
        new class ($repo, RepositoryMode::Insert) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
    }

    public function testUpdateModeRequiresAnUpdatableRepository(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repo = $this->makeExtensibleRepository(); // Extensible, not Updatable
        new class ($repo, RepositoryMode::Update, '1') extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
    }

    public function testDeleteModeRequiresAReducibleRepository(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repo = $this->makeExtensibleRepository(); // Extensible, not Reducible
        new class ($repo, RepositoryMode::Delete, '1') extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
    }

    public function testUpdateModeRequiresAnIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new class ($this->makeUpdatableRepository(), RepositoryMode::Update) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
    }

    public function testDeleteModeRequiresAnIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new class ($this->makeReducibleRepository(), RepositoryMode::Delete) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
    }

    public function testInsertModeCallsAssembleInsertElements(): void
    {
        $form = new class ($this->makeExtensibleRepository(), RepositoryMode::Insert) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
                $this->addElement('text', 'insert_marker');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        $this->assertTrue($form->hasElement('insert_marker'));
    }

    public function testUpdateModeCallsAssembleUpdateElements(): void
    {
        $form = new class ($this->makeUpdatableRepository(), RepositoryMode::Update, 'id') extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleUpdateElements(): void
            {
                $this->addElement('text', 'update_marker');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        $this->assertTrue($form->hasElement('update_marker'));
    }

    public function testDeleteModeCallsAssembleDeleteElements(): void
    {
        $form = new class ($this->makeReducibleRepository(), RepositoryMode::Delete, 'id') extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
                $this->addElement('text', 'delete_marker');
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        $this->assertTrue($form->hasElement('delete_marker'));
    }

    public function testAssembleUpdateElementsFallsBackToAssembleInsertElements(): void
    {
        $form = new class ($this->makeUpdatableRepository(), RepositoryMode::Update, 'id') extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
                $this->addElement('text', 'insert_marker');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        $this->assertTrue($form->hasElement('insert_marker'));
    }

    public function testCsrfElementIsAddedOnAssembly(): void
    {
        $form = new class ($this->makeExtensibleRepository(), RepositoryMode::Insert) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
        $form->setCsrfCounterMeasureId('unique-test-id');
        $form->ensureAssembled();

        $this->assertTrue($form->hasElement('CSRFToken'));
    }

    public function testCsrfElementCanBeDisabled(): void
    {
        $form = new class ($this->makeExtensibleRepository(), RepositoryMode::Insert) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        $this->assertFalse($form->hasElement('CSRFToken'));
    }

    public function testUidElementIsAddedOnAssembly(): void
    {
        $form = new class ($this->makeExtensibleRepository(), RepositoryMode::Insert) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        $this->assertTrue($form->hasElement('uid'));
    }

    public function testOnInsertRequestPopulatesFormWithSetData(): void
    {
        $form = new class ($this->makeExtensibleRepository(), RepositoryMode::Insert) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
                $this->addElement('text', 'username');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnInsertRequest(): void
            {
                $this->onInsertRequest();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->setData(['username' => 'alice']);
        $form->exposeOnInsertRequest();

        $this->assertSame('alice', $form->getElement('username')->getValue());
    }

    public function testOnInsertRequestSkipsPopulationWhenDataIsEmpty(): void
    {
        $form = new class ($this->makeExtensibleRepository(), RepositoryMode::Insert) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
                $this->addElement('text', 'username');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnInsertRequest(): void
            {
                $this->onInsertRequest();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        // No setData call
        $form->exposeOnInsertRequest();

        $this->assertNull($form->getElement('username')->getValue());
    }

    public function testOnUpdateRequestPopulatesFormWithSetData(): void
    {
        $form = new class ($this->makeUpdatableRepository(), RepositoryMode::Update, 'alice') extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
                $this->addElement('text', 'username');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnUpdateRequest(): void
            {
                $this->onUpdateRequest();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->setData(['username' => 'alice-updated']);
        $form->exposeOnUpdateRequest();

        $this->assertSame('alice-updated', $form->getElement('username')->getValue());
    }

    public function testOnUpdateRequestFetchesEntryFromRepositoryWhenDataIsNotSet(): void
    {
        $form = new class ($this->makeUpdatableRepository(), RepositoryMode::Update, 'alice') extends RepositoryForm {
            protected function fetchEntry(): object|false
            {
                return (object) ['username' => 'fetched-alice'];
            }

            protected function assembleInsertElements(): void
            {
                $this->addElement('text', 'username');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnUpdateRequest(): void
            {
                $this->onUpdateRequest();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        // No setData call
        $form->exposeOnUpdateRequest();

        $this->assertSame('fetched-alice', $form->getElement('username')->getValue());
    }

    public function testOnUpdateRequestDoesNotFetchEntryFromRepositoryWhenSetDataIsEmpty(): void
    {
        $form = new class ($this->makeUpdatableRepository(), RepositoryMode::Update, 'alice') extends RepositoryForm {
            protected function fetchEntry(): object|false
            {
                return (object) ['username' => 'fetched-alice'];
            }

            protected function assembleInsertElements(): void
            {
                $this->addElement('text', 'username');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnUpdateRequest(): void
            {
                $this->onUpdateRequest();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->setData([]);
        $form->exposeOnUpdateRequest();

        $this->assertNull($form->getElement('username')->getValue());
    }

    public function testOnUpdateRequestThrowsNotFoundErrorWhenEntryIsMissing(): void
    {
        $this->expectException(NotFoundError::class);

        $form = new class ($this->makeUpdatableRepository(), RepositoryMode::Update, 'missing') extends RepositoryForm {
            protected function fetchEntry(): object|false
            {
                return false;
            }

            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnUpdateRequest(): void
            {
                $this->onUpdateRequest();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->exposeOnUpdateRequest();
    }

    public function testOnDeleteRequestThrowsNotFoundErrorWhenEntryIsMissing(): void
    {
        $this->expectException(NotFoundError::class);

        $form = new class ($this->makeReducibleRepository(), RepositoryMode::Delete, 'missing') extends RepositoryForm {
            protected function entryExists(): bool
            {
                return false;
            }

            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnDeleteRequest(): void
            {
                $this->onDeleteRequest();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->exposeOnDeleteRequest();
    }

    public function testOnDeleteRequestSucceedsWhenEntryExists(): void
    {
        $form = new class ($this->makeReducibleRepository(), RepositoryMode::Delete, 'alice') extends RepositoryForm {
            protected function entryExists(): bool
            {
                return true;
            }

            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnDeleteRequest(): void
            {
                $this->onDeleteRequest();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->exposeOnDeleteRequest();

        $this->addToAssertionCount(1); // Reached without NotFoundError
    }

    public function testInsertEntryPassesValuesAndTableToRepository(): void
    {
        $repo = $this->makeExtensibleRepository();
        $form = new class ($repo, RepositoryMode::Insert) extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
                $this->addElement('text', 'username');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnSuccess(): void
            {
                $this->onSuccess();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->populate(['username' => 'new-entry']);
        $form->exposeOnSuccess();

        $this->assertCount(1, $repo->insertCalls);
        $this->assertSame('test_table', $repo->insertCalls[0]['target']);
        $this->assertSame('new-entry', $repo->insertCalls[0]['data']['username']);
    }

    public function testUpdateEntryPassesValuesAndFilterToRepository(): void
    {
        $repo = $this->makeUpdatableRepository();
        $form = new class ($repo, RepositoryMode::Update, 'alice') extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
                $this->addElement('text', 'username');
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnSuccess(): void
            {
                $this->onSuccess();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->populate(['username' => 'alice-updated']);
        $form->exposeOnSuccess();

        $this->assertCount(1, $repo->updateCalls);
        $this->assertEquals(
            [
                'target' => 'test_table',
                'data'   => ['username' => 'alice-updated'],
                'filter' => Filter::where('username', 'alice')
            ],
            $repo->updateCalls[0]
        );
    }

    public function testDeleteEntryPassesTableAndFilterToRepository(): void
    {
        $repo = $this->makeReducibleRepository();
        $form = new class ($repo, RepositoryMode::Delete, 'alice') extends RepositoryForm {
            protected function assembleInsertElements(): void
            {
            }

            protected function assembleDeleteElements(): void
            {
            }

            protected function createFilter(): Filter
            {
                return Filter::where('username', $this->getIdentifier());
            }

            public function exposeOnSuccess(): void
            {
                $this->onSuccess();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->exposeOnSuccess();

        $this->assertCount(1, $repo->deleteCalls);
        $this->assertEquals(
            [
                'target' => 'test_table',
                'filter' => Filter::where('username', 'alice')
            ],
            $repo->deleteCalls[0]
        );
    }
}
