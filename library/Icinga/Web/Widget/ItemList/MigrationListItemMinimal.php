<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Widget\ItemList;

use Icinga\Application\Hook\Common\DbMigration;
use Icinga\Application\Hook\MigrationHook;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Contract\FormElement;
use ipl\Html\FormattedString;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Common\BaseListItem;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use LogicException;

class MigrationListItemMinimal extends BaseListItem
{
    use Translation;

    /** @var ?FormElement */
    protected $migrateButton;

    /** @var MigrationHook Just for type hint */
    protected $item;

    /**
     * Set a migration form of this list item
     *
     * @param FormElement $migrateButton
     *
     * @return $this
     */
    public function setMigrateButton(FormElement $migrateButton): self
    {
        $this->migrateButton = $migrateButton;

        return $this;
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(
            FormattedString::create(
                t('%s ', '<name>'),
                HtmlElement::create('span', ['class' => 'subject'], $this->item->getName())
            )
        );
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        if ($this->migrateButton === null) {
            throw new LogicException('Please set the migrate submit button beforehand');
        }

        $header->addHtml($this->createTitle());
        $header->addHtml($this->migrateButton);
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $migrations = $this->item->getMigrations();
        /** @var DbMigration $migration */
        $migration = array_shift($migrations);
        if ($migration->getLastState()) {
            if ($migration->getDescription()) {
                $caption->addHtml(Text::create($migration->getDescription()));
            } else {
                $caption->getAttributes()->add('class', 'empty-state');
                $caption->addHtml(Text::create($this->translate('No description provided.')));
            }

            $scriptPath = $migration->getScriptPath();
            /** @var string $parentDirs */
            $parentDirs = substr($scriptPath, (int) strpos($scriptPath, 'schema'));
            $parentDirs = substr($parentDirs, 0, strrpos($parentDirs, '/') + 1);

            $title = new HtmlElement('div', Attributes::create(['class' => 'title']));
            $title->addHtml(
                new HtmlElement('span', null, Text::create($parentDirs)),
                new HtmlElement(
                    'span',
                    Attributes::create(['class' => 'version']),
                    Text::create($migration->getVersion() . '.sql')
                ),
                new HtmlElement(
                    'span',
                    Attributes::create(['class' => 'upgrade-failed']),
                    Text::create($this->translate('Upgrade failed'))
                )
            );

            $error = new HtmlElement('div', Attributes::create([
                'class'               => 'collapsible',
                'data-visible-height' => '58',
            ]));
            $error->addHtml(new HtmlElement('pre', null, new HtmlString(Html::escape($migration->getLastState()))));

            $errorSection = new HtmlElement('div', Attributes::create(['class' => 'errors-section',]));
            $errorSection->addHtml(
                new HtmlElement('header', null, new Icon('circle-xmark', ['class' => 'status-icon']), $title),
                $caption,
                $error
            );

            $caption->prependWrapper($errorSection);
        }
    }

    protected function assembleFooter(BaseHtmlElement $footer): void
    {
        $footer->addHtml((new MigrationList($this->item->getLatestMigrations(3)))->setMinimal(false));
        if ($this->item->count() > 3) {
            $footer->addHtml(
                new Link(
                    sprintf($this->translate('Show all %d migrations'), $this->item->count()),
                    Url::fromPath(
                        'migrations/migration',
                        [MigrationHook::MIGRATION_PARAM => $this->item->getModuleName()]
                    ),
                    [
                        'data-base-target' => '_next',
                        'class' => 'show-more'
                    ]
                )
            );
        }
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());
        $caption = $this->createCaption();
        if (! $caption->isEmpty()) {
            $main->addHtml($caption);
        }

        $footer = $this->createFooter();
        if ($footer) {
            $main->addHtml($footer);
        }
    }
}
