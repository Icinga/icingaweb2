<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Hook\MigrationHook;
use Icinga\Application\MigrationManager;
use Icinga\Exception\MissingParameterException;
use Icinga\Forms\MigrationForm;
use Icinga\Web\Notification;
use Icinga\Web\Widget\ItemList\MigrationList;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatController;
use ipl\Web\Widget\ActionLink;

class MigrationsController extends CompatController
{
    public function indexAction(): void
    {
        $mm = MigrationManager::instance();

        $this->getTabs()->extend(new OutputFormat(['csv']));
        $this->addTitleTab($this->translate('Migrations'));

        $canApply = $this->hasPermission('application/migrations');
        if (! $canApply) {
            $this->addControl(
                new HtmlElement(
                    'div',
                    Attributes::create(['class' => 'migration-state-banner']),
                    new HtmlElement(
                        'span',
                        null,
                        Text::create(
                            $this->translate('You do not have the required permission to apply pending migrations.')
                        )
                    )
                )
            );
        }

        $migrateListForm = new MigrationForm();
        $migrateGlobalForm = new MigrationForm();
        $migrateGlobalForm->getAttributes()->set('name', sprintf('migrate-%s', MigrationHook::ALL_MIGRATIONS));

        if ($canApply && $mm->hasPendingMigrations()) {
            $migrateGlobalForm->addElement('submit', sprintf('migrate-%s', MigrationHook::ALL_MIGRATIONS), [
                'required' => true,
                'label'    => $this->translate('Migrate All'),
                'title'    => $this->translate('Migrate all pending migrations')
            ]);

            $this->controls->getAttributes()->add('class', 'default-layout');
            $this->handleMigrateRequest($migrateGlobalForm);

            $this->addControl($migrateGlobalForm);
        }

        $this->handleFormatRequest($mm->toArray());

        $frameworkList = new MigrationList($mm->yieldMigrations(), $migrateListForm);
        $frameworkListControl = new HtmlElement('div', Attributes::create(['class' => 'migration-list-control']));
        $frameworkListControl->addHtml(new HtmlElement('h2', null, Text::create($this->translate('System'))));
        $frameworkListControl->addHtml($frameworkList);

        $moduleList = new MigrationList($mm->yieldMigrations(true), $migrateListForm);
        $moduleListControl = new HtmlElement('div', Attributes::create(['class' => 'migration-list-control']));
        $moduleListControl->addHtml(new HtmlElement('h2', null, Text::create($this->translate('Modules'))));
        $moduleListControl->addHtml($moduleList);

        $migrateListForm->addHtml($frameworkListControl, $moduleListControl);
        if ($canApply && $mm->hasPendingMigrations()) {
            $frameworkList->ensureAssembled();
            $moduleList->ensureAssembled();

            $this->handleMigrateRequest($migrateListForm);
        }

        $migrations = new HtmlElement('div', Attributes::create(['class' => 'migrations']));
        $migrations->addHtml($migrateListForm);

        $this->addContent($migrations);
    }

    public function hintAction(): void
    {
        // The forwarded request doesn't modify the original server query string, but adds the migration param to the
        // request param instead. So, there is no way to access the migration param other than via the request instance.
        /** @var ?string $module */
        $module = $this->getRequest()->getParam(MigrationHook::MIGRATION_PARAM);
        if ($module === null) {
            throw new MissingParameterException(t('Required parameter \'%s\' missing'), MigrationHook::MIGRATION_PARAM);
        }

        $mm = MigrationManager::instance();
        if (! $mm->hasMigrations($module)) {
            $this->httpNotFound(sprintf('There are no pending migrations matching the given name: %s', $module));
        }

        $migration = $mm->getMigration($module);
        $this->addTitleTab($this->translate('Error'));
        $this->addContent(
            new HtmlElement(
                'div',
                Attributes::create(['class' => 'pending-migrations-hint']),
                new HtmlElement('h2', null, Text::create($this->translate('Error!'))),
                new HtmlElement(
                    'p',
                    null,
                    Text::create(sprintf($this->translate('%s has pending migrations.'), $migration->getName()))
                ),
                new HtmlElement('p', null, Text::create($this->translate('Please apply the migrations first.'))),
                new ActionLink($this->translate('View pending Migrations'), 'migrations')
            )
        );
    }

    public function migrationAction(): void
    {
        /** @var string $name */
        $name = $this->params->getRequired(MigrationHook::MIGRATION_PARAM);

        $this->addTitleTab($this->translate('Migration'));
        $this->getTabs()->disableLegacyExtensions();
        $this->controls->getAttributes()->add('class', 'default-layout');

        $mm = MigrationManager::instance();
        if (! $mm->hasMigrations($name)) {
            $migrations = [];
        } else {
            $hook = $mm->getMigration($name);
            $migrations = array_reverse($hook->getMigrations());
            if (! $this->hasPermission('application/migrations')) {
                $this->addControl(
                    new HtmlElement(
                        'div',
                        Attributes::create(['class' => 'migration-state-banner']),
                        new HtmlElement(
                            'span',
                            null,
                            Text::create(
                                $this->translate('You do not have the required permission to apply pending migrations.')
                            )
                        )
                    )
                );
            } else {
                $migrateForm = (new MigrationForm())
                    ->addElement(
                        'submit',
                        sprintf('migrate-%s', $hook->getModuleName()),
                        [
                            'required' => true,
                            'label'    => $this->translate('Migrate'),
                            'title'    => sprintf(
                                $this->translatePlural(
                                    'Migrate %d pending migration',
                                    'Migrate all %d pending migrations',
                                    $hook->count()
                                ),
                                $hook->count()
                            )
                        ]
                    );

                $migrateForm->getAttributes()->add('class', 'inline');
                $this->handleMigrateRequest($migrateForm);

                $this->addControl(
                    new HtmlElement(
                        'div',
                        Attributes::create(['class' => 'migration-controls']),
                        new HtmlElement('span', null, Text::create($hook->getName())),
                        $migrateForm
                    )
                );
            }
        }

        $migrationWidget = new HtmlElement('div', Attributes::create(['class' => 'migrations']));
        $migrationWidget->addHtml((new MigrationList($migrations))->setMinimal(false));
        $this->addContent($migrationWidget);
    }

    public function handleMigrateRequest(MigrationForm $form): void
    {
        $this->assertPermission('application/migrations');

        $form->on(MigrationForm::ON_SUCCESS, function (MigrationForm $form) {
            $mm = MigrationManager::instance();

            $pressedButton = $form->getPressedSubmitElement();
            if ($pressedButton) {
                $name = substr($pressedButton->getName(), 8);
                switch ($name) {
                    case MigrationHook::ALL_MIGRATIONS:
                        if ($mm->applyAll()) {
                            Notification::success($this->translate('Applied all migrations successfully'));
                        } else {
                            Notification::error(
                                $this->translate(
                                    'Applied migrations successfully. Though, one or more migration hooks'
                                    . ' failed to run. See logs for details'
                                )
                            );
                        }
                        break;
                    default:
                        $migration = $mm->getMigration($name);
                        if ($mm->apply($migration)) {
                            Notification::success($this->translate('Applied pending migrations successfully'));
                        } else {
                            Notification::error(
                                $this->translate('Failed to apply pending migration(s). See logs for details')
                            );
                        }
                }
            }

            $this->redirectNow('migrations');
        })->handleRequest($this->getServerRequest());
    }

    /**
     * Handle exports
     *
     * @param array<string, mixed> $data
     */
    protected function handleFormatRequest(array $data): void
    {
        $formatJson = $this->params->get('format') === 'json';
        if (! $formatJson && ! $this->getRequest()->isApiRequest()) {
            return;
        }

        $this->getResponse()
            ->json()
            ->setSuccessData($data)
            ->sendResponse();
    }
}
