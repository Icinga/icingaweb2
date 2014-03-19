<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2014 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2014 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

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
 * Once a PO-file is compiled it's content is used by Icinga Web 2 to display
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
