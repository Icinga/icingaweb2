<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Clicommands;

use Icinga\Module\Translation\Cli\TranslationCommand;

/**
 * Translation compiler
 *
 * This command will compile gettext catalogs of modules.
 *
 * Once a catalog is compiled its content is used by Icinga Web 2 to display
 * messages in the configured language.
 */
class CompileCommand extends TranslationCommand
{
    /**
     * Compile a module gettext catalog
     *
     * This will compile the catalog of the given module and locale.
     *
     * USAGE:
     *
     *   icingacli translation compile <module> <locale>
     *
     * EXAMPLES:
     *
     *   icingacli translation compile demo de_DE
     *   icingacli translation compile demo fr_FR
     */
    public function moduleAction()
    {
        $module = $this->validateModuleName($this->params->shift());
        $locale = $this->validateLocaleCode($this->params->shift());

        $helper = $this->getTranslationHelper($locale);
        $helper->compileModuleTranslation($module);
    }
}
