<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Hook\DbMigrationHook;
use Icinga\Application\Icinga;
use Icinga\Application\MigrationManager;
use Icinga\Common\Database;
use Icinga\Exception\MissingParameterException;
use Icinga\Forms\MigrationForm;
use Icinga\Web\Notification;
use Icinga\Web\Widget\ItemList\MigrationList;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use ipl\Html\Attributes;
use ipl\Html\FormElement\SubmitButtonElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatController;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use Throwable;

class MigrationsController extends CompatController
{
    use Database;

    public function init()
    {
        Icinga::app()->getModuleManager()->loadModule('setup');
    }

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
        $migrateListForm->setAttribute('id', $this->getRequest()->protectId('migration-form'));
        try {
            $migrateListForm->setRenderDatabaseUserChange(! $mm->validateDatabasePrivileges());

            if ($canApply && $mm->hasPendingMigrations()) {
                $migrateAllButton = new SubmitButtonElement(sprintf('migrate-%s', DbMigrationHook::ALL_MIGRATIONS), [
                'form'  => $migrateListForm->getAttribute('id')->getValue(),
                'label' => $this->translate('Migrate All'),
                'title' => $this->translate('Migrate all pending migrations')
                ]);

                // Is the first button, so will be cloned and that the visible
                // button is outside the form doesn't matter for Web's JS
                $migrateListForm->registerElement($migrateAllButton);

                // Make sure it looks familiar, even if not inside a form
                $migrateAllButton->setWrapper(
                    new HtmlElement('div', Attributes::create(['class' => 'icinga-controls']))
                );

                $this->controls->getAttributes()->add('class', 'default-layout');
                $this->addControl($migrateAllButton);
            }
        } catch (Throwable $e) {
            $this->addContent(
                new HtmlElement(
                    'div',
                    new Attributes(['class' => 'db-connection-warning']),
                    new Icon('warning'),
                    new HtmlElement('ul', null),
                    new HtmlElement(
                        'p',
                        null,
                        new Text($this->translate(
                            'No Configuration Database selected. '
                            . 'To establish a valid database connection set the Configuration Database field.'
                        )),
                        new HtmlElement('ul', null),
                        new Link($this->translate('Configuration Database'), 'config/general')
                    )
                )
            );
            return;
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
        $module = $this->getRequest()->getParam(DbMigrationHook::MIGRATION_PARAM);
        if ($module === null) {
            throw new MissingParameterException(
                $this->translate('Required parameter \'%s\' missing'),
                DbMigrationHook::MIGRATION_PARAM
            );
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
        $name = $this->params->getRequired(DbMigrationHook::MIGRATION_PARAM);

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
                $this->addControl(
                    new HtmlElement(
                        'div',
                        Attributes::create(['class' => 'migration-controls']),
                        new HtmlElement('span', null, Text::create($hook->getName()))
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

            /** @var array<string, string> $elevatedPrivileges */
            $elevatedPrivileges = $form->getValue('database_setup');
            if ($elevatedPrivileges !== null && $elevatedPrivileges['grant_privileges'] === 'y') {
                $mm->fixIcingaWebMysqlGrants($this->getDb(), $elevatedPrivileges);
            }

            $pressedButton = $form->getPressedSubmitElement();
            if ($pressedButton) {
                $name = substr($pressedButton->getName(), 8);
                switch ($name) {
                    case DbMigrationHook::ALL_MIGRATIONS:
                        if ($mm->applyAll($elevatedPrivileges)) {
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
                        if ($mm->apply($migration, $elevatedPrivileges)) {
                            Notification::success($this->translate('Applied pending migrations successfully'));
                        } else {
                            Notification::error(
                                $this->translate('Failed to apply pending migration(s). See logs for details')
                            );
                        }
                }
            }

            $this->sendExtraUpdates(['#col2' => '__CLOSE__']);

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
