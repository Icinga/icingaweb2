<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Announcement;

use DateTime;
use Icinga\Application\Config;
use Icinga\Authentication\Auth;
use Icinga\Data\ConfigObject;
use Icinga\Forms\Announcement\AnnouncementForm;
use Icinga\Repository\RepositoryMode;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use Icinga\Web\Announcement\AnnouncementIniRepository;

class AnnouncementFormTest extends BaseTestCase
{
    /** @var string The user the tests are authenticated as */
    public const AUTHOR = 'icingaadmin';

    /** @var string The id of the stored announcement */
    public const ID = '6a79bd6b09595';

    /** @var string The message of the stored announcement */
    public const MESSAGE = 'Scheduled maintenance';

    /** @var string The stored announcement's start, in the format a browser submits */
    public const START = '2026-01-01T09:00:00';

    /** @var string The stored announcement's end, in the format a browser submits */
    public const END = '2026-01-02T09:00:00';

    public function setUp(): void
    {
        parent::setUp();

        Auth::getInstance()->setUser(new User(static::AUTHOR));
    }

    public function testInsertModeCreatesAnnouncement(): void
    {
        $config = $this->createConfig();
        $form = $this->createForm($config, RepositoryMode::Insert);
        $form->populate([
            'message' => static::MESSAGE,
            'start'   => static::START,
            'end'     => static::END,
        ]);
        $form->ensureAssembled();
        $form->exposeOnSuccess();

        $announcements = $config->toArray();
        $this->assertCount(1, $announcements);

        $announcement = $announcements[array_key_first($announcements)];
        $this->assertSame(static::MESSAGE, $announcement['message']);
        $this->assertSame((new DateTime(static::START))->getTimestamp(), $announcement['start']);
        $this->assertSame((new DateTime(static::END))->getTimestamp(), $announcement['end']);
    }

    public function testUpdateModeChangesTheStoredAnnouncement(): void
    {
        $config = $this->createConfig(seed: true);

        $newMessage = 'Maintenance postponed';
        $newStart = '2026-01-03T09:00:00';
        $newEnd = '2026-01-04T09:00:00';

        $form = $this->createForm($config, RepositoryMode::Update, static::ID);
        $form->populate([
            'message' => $newMessage,
            'start'   => $newStart,
            'end'     => $newEnd,
        ]);
        $form->ensureAssembled();
        $form->exposeOnSuccess();

        $announcement = $config->getSection(static::ID);

        $this->assertSame($newMessage, $announcement->message);
        $this->assertSame((new DateTime($newStart))->getTimestamp(), $announcement->start);
        $this->assertSame((new DateTime($newEnd))->getTimestamp(), $announcement->end);
    }

    public function testDeleteModeRemovesTheStoredAnnouncement(): void
    {
        $config = $this->createConfig(seed: true);

        $form = $this->createForm($config, RepositoryMode::Delete, static::ID);
        $form->ensureAssembled();

        $this->assertTrue($config->hasSection(static::ID));

        $form->exposeOnSuccess();

        $this->assertFalse($config->hasSection(static::ID));
    }

    public function testFetchEntryTurnsTheStoredTimestampsIntoDateTimes(): void
    {
        $entry = $this->createForm($this->createConfig(seed: true), RepositoryMode::Update, static::ID)
            ->exposeFetchEntry();

        $this->assertInstanceOf(DateTime::class, $entry->start);
        $this->assertInstanceOf(DateTime::class, $entry->end);
        $this->assertSame((new DateTime(static::START))->getTimestamp(), $entry->start->getTimestamp());
        $this->assertSame((new DateTime(static::END))->getTimestamp(), $entry->end->getTimestamp());
    }

    public function testFetchEntryKeepsUnsetTimestampsUnset(): void
    {
        $config = $this->createConfig(seed: true, overrides: ['start' => null, 'end' => null]);

        $entry = $this->createForm($config, RepositoryMode::Update, static::ID)->exposeFetchEntry();

        $this->assertNull($entry->start);
        $this->assertNull($entry->end);
    }

    public function testFetchEntryReturnsFalseIfTheAnnouncementDoesNotExist(): void
    {
        $form = $this->createForm($this->createConfig(seed: true), RepositoryMode::Update, '00000000000000');

        $this->assertFalse($form->exposeFetchEntry());
    }

    public function testInsertModeDefaults(): void
    {
        $config = $this->createConfig();
        $form = $this->createForm($config, RepositoryMode::Insert);

        $form->populate(['message' => static::MESSAGE]);
        $form->ensureAssembled();

        $this->assertSame(static::AUTHOR, $form->getElement('author')->getValue());
        $this->assertEquals(new DateTime('tomorrow'), $form->getElement('start')->getValue());
        $this->assertEquals(new DateTime('tomorrow +1day'), $form->getElement('end')->getValue());

        $form->exposeOnSuccess();

        $announcements = $config->toArray();
        $announcements = $announcements[array_key_first($announcements)];

        $this->assertSame(static::AUTHOR, $announcements['author']);
        $this->assertSame((new DateTime('tomorrow'))->getTimestamp(), $announcements['start']);
        $this->assertSame((new DateTime('tomorrow +1day'))->getTimestamp(), $announcements['end']);
    }

    public function testUpdateModePreFillsTheFormWithTheStoredAnnouncement(): void
    {
        $form = $this->createForm($this->createConfig(seed: true), RepositoryMode::Update, static::ID);
        $form->exposeOnUpdateRequest();
        $form->ensureAssembled();

        $this->assertSame(static::MESSAGE, $form->getElement('message')->getValue());
        $this->assertSame(
            (new DateTime(static::START))->getTimestamp(),
            $form->getElement('start')->getValue()->getTimestamp()
        );
        $this->assertSame(
            (new DateTime(static::END))->getTimestamp(),
            $form->getElement('end')->getValue()->getTimestamp()
        );
    }

    public function testUpdateModeReassignsTheAuthorToTheEditingUser(): void
    {
        $originalAuthor = 'someone_else';
        $config = $this->createConfig(seed: true, overrides: ['author' => $originalAuthor]);

        $form = $this->createForm($config, RepositoryMode::Update, static::ID);
        $form->populate(['message' => static::MESSAGE]);
        $form->ensureAssembled();

        $this->assertSame($originalAuthor, $config->getSection(static::ID)->author);

        $form->exposeOnSuccess();

        $this->assertSame(static::AUTHOR, $config->getSection(static::ID)->author);
    }

    public function testTheAuthorIsOnlyEditableViaTheApi(): void
    {
        $form = $this->createForm($this->createConfig(), RepositoryMode::Insert);
        $form->ensureAssembled();

        $this->assertTrue($form->getElement('author')->getAttributes()->get('disabled')->getValue());

        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        try {
            $apiForm = $this->createForm($this->createConfig(), RepositoryMode::Insert);
            $apiForm->ensureAssembled();

            $this->assertFalse($apiForm->getElement('author')->getAttributes()->get('disabled')->getValue());
        } finally {
            unset($_SERVER['HTTP_ACCEPT']);
        }
    }

    /**
     * Create an announcement form backed by this test's announcements
     *
     * @param Config $config
     * @param RepositoryMode $mode
     * @param ?string $identifier
     *
     * @return AnnouncementForm
     */
    private function createForm(Config $config, RepositoryMode $mode, ?string $identifier = null): AnnouncementForm
    {
        $repository = new AnnouncementIniRepository($config);

        $form = new class ($repository, $mode, $identifier) extends AnnouncementForm {
            public function exposeFetchEntry(): object|false
            {
                return $this->fetchEntry();
            }

            public function exposeOnSuccess(): void
            {
                $this->onSuccess();
            }

            public function exposeOnUpdateRequest(): void
            {
                $this->onUpdateRequest();
            }
        };

        $form->disableCsrfCounterMeasure();

        return $form;
    }

    /**
     * Create an in-memory substitute for the announcements.ini
     *
     * Writing back is a no-op, so that the repository's statements can be asserted
     * on the config itself instead of on a file.
     *
     * @param bool $seed Whether to store a single announcement
     * @param array<string, mixed> $overrides Values to store instead of the defaults.
     *   Has no effect unless $seed is true.
     *
     * @return Config
     */
    private function createConfig(bool $seed = false, array $overrides = []): Config
    {
        $config = new class (new ConfigObject()) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
        };

        $config->getConfigObject()->setKeyColumn('id');

        if ($seed) {
            $config->setSection(static::ID, array_merge([
                'author'  => static::AUTHOR,
                'message' => static::MESSAGE,
                'start'   => (new DateTime(static::START))->getTimestamp(),
                'end'     => (new DateTime(static::END))->getTimestamp(),
            ], $overrides));
        }

        return $config;
    }
}
