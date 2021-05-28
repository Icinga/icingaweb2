<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Clicommands;

use Icinga\Module\Translation\Cli\TranslationCommand;

/**
 * Translation updater
 *
 * This command will create a new or update any existing gettext catalog of a module.
 *
 * Once a catalog has been created/updated one can open it with a editor for
 * PO-files and start with the actual translation.
 */
class RefreshCommand extends TranslationCommand
{
    /**
     * Generate or update a module gettext catalog
     *
     * This will create/update the PO-file of the given module and locale.
     *
     * USAGE:
     *
     *   icingacli translation refresh module <module> <locale>
     *
     * EXAMPLES:
     *
     *   icingacli translation refresh module demo de_DE
     *   icingacli translation refresh module demo fr_FR
     */
    public function moduleAction()
    {
        $module = $this->validateModuleName($this->params->shift());
        $locale = $this->validateLocaleCode($this->params->shift());

        $helper = $this->getTranslationHelper($locale);
        $helper->updateModuleTranslations($module);
    }
}
