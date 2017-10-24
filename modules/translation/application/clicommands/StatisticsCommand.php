<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Clicommands;

use Icinga\Module\Translation\Cli\TranslationStatisticsCommand;

class StatisticsCommand extends TranslationStatisticsCommand
{    /**
     * Generates statistics
     *
     * This shows translation statistics for a given language and all modules or a given module and all languages.
     * Alternatively without any options it will give you the overall state of all languages.
     *
     * USAGE:
     *
     *   icingacli translation statistics show --<option> <module/locale>
     *
     * EXAMPLES:
     *
     *   icingacli translation statistics show --module monitoring
     *   icingacli translation statistics show --language de_DE
     */
    public function showAction()
    {
        $module = $this->params->get('module');
        $language = $this->params->get('language');

        if ($module && $language) {
            $this->fail($this->translate('Options --module and --language cannot be used at the same time.'));
        } else {
            $this->showStatistics($module, $language);
        }
    }
}
