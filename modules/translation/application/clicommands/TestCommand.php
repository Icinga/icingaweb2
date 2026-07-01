<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Translation\Clicommands;

use Icinga\Date\DateFormatter;
use Icinga\Module\Translation\Cli\ArrayToTextTableHelper;
use Icinga\Module\Translation\Cli\TranslationCommand;
use ipl\I18n\GettextTranslator;
use ipl\I18n\StaticTranslator;

/**
 * Timestamp test helper
 *
 *
 */
class TestCommand extends TranslationCommand
{
    protected $locales = [];

    /**
     * Get translation examples for DateFormatter
     *
     * To help you check if the values got translated correctly
     *
     * USAGE:
     *
     *   icingacli translation test dateformatter <locale>
     *
     * EXAMPLES:
     *
     *   icingacli translation test dateformatter de_DE
     *   icingacli translation test dateformatter fr_FR
     */
    public function dateformatterAction()
    {
        $time = time();

        /** @uses DateFormatter::timeAgo */
        $this->printTable($this->getMultiTranslated(
            'Time Ago',
            ['Icinga\Date\DateFormatter', 'timeAgo'],
            [
                "15 sec" => $time - 15,
                "62 sec" => $time - 62,
                "10 min" => $time - 600,
                "1h"     => $time - 1 * 3600,
                "3h"     => $time - 3 * 3600,
                "25h"    => $time - 25 * 3600,
                "31d"    => $time - 31 * 24 * 3600,
            ]
        ));

        $this->printTable($this->getMultiTranslated(
            'Time Since',
            ['Icinga\Date\DateFormatter', 'timeSince'],
            [
                "15 sec" => $time - 15,
                "62 sec" => $time - 62,
                "10 min" => $time - 600,
                "1h"     => $time - 1 * 3600,
                "3h"     => $time - 3 * 3600,
                "25h"    => $time - 25 * 3600,
                "31d"    => $time - 31 * 24 * 3600,
            ]
        ));

        $this->printTable($this->getMultiTranslated(
            'Time Until',
            ['Icinga\Date\DateFormatter', 'timeUntil'],
            [
                "15 sec" => $time + 15,
                "62 sec" => $time + 62,
                "10 min" => $time + 600,
                "1h"     => $time + 1 * 3600,
                "3h"     => $time + 3 * 3600,
                "25h"    => $time + 25 * 3600,
                "31d"    => $time + 31 * 24 * 3600,
            ]
        ));
    }

    public function defaultAction()
    {
        $this->dateformatterAction();
    }

    public function init()
    {
        foreach ($this->params->getAllStandalone() as $l) {
            $this->locales[] = $l;
        }

        if (empty($this->locales)) {
            /** @var GettextTranslator $translator */
            $translator = StaticTranslator::$instance;
            $this->locales = $translator->listLocales();
        }
    }

    protected function callTranslated($callback, $arguments, $locale = 'en_US')
    {
        /** @var GettextTranslator $translator */
        $translator = StaticTranslator::$instance;
        $translator->setLocale($locale);
        return call_user_func_array($callback, $arguments);
    }

    protected function getMultiTranslated($name, $callback, $arguments, $locales = null)
    {
        if ($locales === null) {
            $locales = $this->locales;
        }
        array_unshift($locales, 'C');

        $rows = [];

        foreach ($arguments as $k => $args) {
            $row = [$name => $k];

            if (! is_array($args)) {
                $args = [$args];
            }
            foreach ($locales as $locale) {
                $row[$locale] = $this->callTranslated($callback, $args, $locale);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    protected function printTable($rows)
    {
        $tt = new ArrayToTextTableHelper($rows);
        $tt->showHeaders(true);
        $tt->render();
        echo "\n\n";
    }
}
