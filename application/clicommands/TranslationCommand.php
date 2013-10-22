<?php

namespace Icinga\Clicommands;

use Icinga\Cli\Command;
use Icinga\Application\TranslationHelper;

/**
 * Translation command
 *
 * This command provides different utilities useful for translators. It
 * allows to add new languages and also to refresh existing translations. All
 * functionality is available for core components and for modules.
 *
 * This is another parapragh.
 */
class TranslationCommand extends Command
{
    protected $translator;

    public function init()
    {
        $this->translator = new TranslationHelper(
            $this->application,
            $this->params->get('locale', 'C'),
            $this->params->get('module', 'monitoring') // bullshit. NULL?
        );
    }

    /**
     * Refresh translation catalogs
     *
     * Extracts all translatable strings for a given module (or core) from the
     * Icingaweb source code, adds those to the existing catalog for the given
     * locale and marks obsolete translations.
     *
     * Usage: icingaweb translation refresh --module <modulename> --locale <lc_LC>
     */
    public function refreshAction()
    {
        $this->translator->createTemporaryFileList()->extractTexts();
    }
}
