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
 * Translation updater
 *
 * This command will create a new or update any existing PO-file of a domain. The
 * actions below allow to select a particular domain for whom to touch the PO-file.
 *
 * Domains are the global one 'icinga' and all available and enabled modules
 * identified by their name.
 *
 * Once a PO-file has been created/updated one can open it with a editor for
 * PO-files and start with the actual translation.
 */
class RefreshCommand extends TranslationCommand
{
    /**
     * Touch the global domain
     *
     * This will create/update the PO-file of the global 'icinga' domain.
     *
     * USAGE:
     *
     *   icingaweb translation refresh icinga <locale>
     *
     * EXAMPLES:
     *
     *   icingaweb translation refresh icinga de_DE
     *   icingaweb translation refresh icinga fr_FR
     */
    public function icingaAction()
    {
        $locale = $this->validateLocaleCode($this->params->shift());

        $helper = new GettextTranslationHelper($this->app, $locale);
        $helper->updateIcingaTranslations();
    }

    /**
     * Touch a module domain
     *
     * This will create/update the PO-file of the given module domain.
     *
     * USAGE:
     *
     *   icingaweb translation refresh module <module> <locale>
     *
     * EXAMPLES:
     *
     *   icingaweb translation refresh module monitoring de_DE
     *   icingaweb translation refresh module monitoring fr_FR
     */
    public function moduleAction()
    {
        $module = $this->validateModuleName($this->params->shift());
        $locale = $this->validateLocaleCode($this->params->shift());

        $helper = new GettextTranslationHelper($this->app, $locale);
        $helper->updateModuleTranslations($module);
    }
}
