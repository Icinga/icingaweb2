<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Widget\ItemList;

use Generator;
use Icinga\Application\Hook\Common\DbMigrationStep;
use Icinga\Application\Hook\DbMigrationHook;
use Icinga\Application\MigrationManager;
use Icinga\Forms\MigrationForm;
use ipl\I18n\Translation;
use ipl\Web\Common\BaseItemList;
use ipl\Web\Widget\EmptyStateBar;

class MigrationList extends BaseItemList
{
    use Translation;

    protected $baseAttributes = ['class' => 'item-list'];

    /** @var Generator<DbMigrationHook> */
    protected $data;

    /** @var ?MigrationForm */
    protected $migrationForm;

    /** @var bool Whether to render minimal migration list items */
    protected $minimal = true;

    /**
     * Create a new migration list
     *
     * @param Generator<DbMigrationHook>|array<DbMigrationStep|DbMigrationHook> $data
     *
     * @param ?MigrationForm $form
     */
    public function __construct($data, MigrationForm $form = null)
    {
        parent::__construct($data);

        $this->migrationForm = $form;
    }

    /**
     * Set whether to render minimal migration list items
     *
     * @param bool $minimal
     *
     * @return $this
     */
    public function setMinimal(bool $minimal): self
    {
        $this->minimal = $minimal;

        return $this;
    }

    /**
     * Get whether to render minimal migration list items
     *
     * @return bool
     */
    public function isMinimal(): bool
    {
        return $this->minimal;
    }

    protected function getItemClass(): string
    {
        if ($this->isMinimal()) {
            return MigrationListItem::class;
        }

        return MigrationFileListItem::class;
    }

    protected function assemble(): void
    {
        $itemClass = $this->getItemClass();
        if (! $this->isMinimal()) {
            $this->getAttributes()->add('class', 'file-list');
        }

        /** @var DbMigrationHook $data */
        foreach ($this->data as $data) {
            /** @var MigrationFileListItem|MigrationListItem $item */
            $item = new $itemClass($data, $this);
            if ($item instanceof MigrationListItem && $this->migrationForm) {
                $migrateButton = $this->migrationForm->createElement(
                    'submit',
                    sprintf('migrate-%s', $data->getModuleName()),
                    [
                        'required' => false,
                        'label'    => $this->translate('Migrate'),
                        'title'    => sprintf(
                            $this->translatePlural(
                                'Migrate %d pending migration',
                                'Migrate all %d pending migrations',
                                $data->count()
                            ),
                            $data->count()
                        )
                    ]
                );

                $mm = MigrationManager::instance();
                if ($data->isModule() && $mm->hasMigrations(DbMigrationHook::DEFAULT_MODULE)) {
                    $migrateButton->getAttributes()
                        ->set('disabled', true)
                        ->set(
                            'title',
                            $this->translate(
                                'Please apply all the pending migrations of Icinga Web first or use the apply all'
                                . ' button instead.'
                            )
                        );
                }

                $this->migrationForm->registerElement($migrateButton);

                $item->setMigrateButton($migrateButton);
            }

            $this->addHtml($item);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyStateBar(t('No items found.')));
        }
    }
}
