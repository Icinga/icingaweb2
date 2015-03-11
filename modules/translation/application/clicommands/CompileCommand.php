<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Clicommands;

use Icinga\Module\Translation\Cli\TranslationCommand;
use Icinga\Module\Translation\Util\GettextTranslationHelper;

/**
 * Translation compiler
 *
 * This command will compile the PO-file of a domain. The actions below allow
 * you to select a particular domain for which the PO-file should be compiled.
 *
 * Domains are the global one 'icinga' and all available and enabled modules
 * identified by their name.
 *
 * Once a PO-file is compiled its content is used by Icinga Web 2 to display
 * messages in the configured language.
 */
class CompileCommand extends TranslationCommand
{
    /**
     * Compile the global domain
     *
     * This will compile the PO-file of the global 'icinga' domain.
     *
     * USAGE:
     *
     *   icingacli translation compile icinga <locale>
     *
     * EXAMPLES:
     *
     *   icingacli translation compile icinga de_DE
     *   icingacli translation compile icinga fr_FR
     */
    public function icingaAction()
    {
        $locale = $this->validateLocaleCode($this->params->shift());

        $helper = new GettextTranslationHelper($this->app, $locale);
        $helper->compileIcingaTranslation();
    }

    /**
     * Compile a module domain
     *
     * This will compile the PO-file of the given module domain.
     *
     * USAGE:
     *
     *   icingacli translation compile <module> <locale>
     *
     * EXAMPLES:
     *
     *   icingacli translation compile monitoring de_DE
     *   icingacli trnslations compile monitoring de_DE
     */
    public function moduleAction()
    {
        $module = $this->validateModuleName($this->params->shift());
        $locale = $this->validateLocaleCode($this->params->shift());

        $helper = new GettextTranslationHelper($this->app, $locale);
        $helper->compileModuleTranslation($module);
    }
}
