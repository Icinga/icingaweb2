<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Clicommands;

use Exception;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Module\Translation\Statistics;
use Icinga\Module\Translation\Cli\TranslationCommand;
use Icinga\Util\Translator;

class StatisticsCommand extends TranslationCommand
{
    /**
     * Colors for translation status indicators
     */
    protected $colors = [
        'untranslated'  => 'purple',
        'translated'    => 'green',
        'fuzzy'         => 'blue',
        'faulty'        => 'red'
    ];

    /**
     * Calculates the percentages from the statistics
     *
     * @param   array   $numbers    The collected data
     *
     * @return  array
     */
    protected function calculatePercentages($numbers)
    {
        $percentages = [];
        $percentages['translated'] = $this->roundPercentage($numbers['translatedCount'], $numbers['messageCount']);
        $percentages['fuzzy'] = $this->roundPercentage($numbers['fuzzyCount'], $numbers['messageCount']);
        $percentages['faulty'] = $this->roundPercentage($numbers['faultyCount'], $numbers['messageCount']);
        $percentages['untranslated'] = $this->roundPercentage($numbers['untranslatedCount'], $numbers['messageCount']);

        $percentageSum = array_sum($percentages);
        if ($percentageSum != 100) {
            $toAdapt = array_search(max($percentages), $percentages);
            $percentages[$toAdapt] += 100 - $percentageSum;
        }

        return $percentages;
    }

    /**
     * Rounds the percentage so that it always is a full percent
     *
     * @param   int   $number     The percentage value
     * @param   int   $maxCount   The fundamental value
     *
     * @return  int
     */
    protected function roundPercentage($number, $maxCount)
    {
        $percentage = $number / $maxCount * 100;
        if ($percentage !== 0 && $percentage < 1) {
            return 1;
        }

        return round($percentage);
    }

    /**
     * Gets the absolute amount of the different message types
     *
     * @param   Statistics  $statistics     The collected data
     *
     * @return  array
     */
    protected function getMessageCounts(Statistics $statistics)
    {
        $numbers = [];
        $numbers['messageCount'] =  $statistics->countEntries();
        $numbers['untranslatedCount'] = $statistics->countUntranslatedEntries();
        $numbers['translatedCount'] = $statistics->countTranslatedEntries();
        $numbers['fuzzyCount'] = $statistics->countFuzzyEntries();
        $numbers['faultyCount'] = $statistics->countFaultyEntries();

        return $numbers;
    }

    /**
     * Get all paths for the input language
     *
     * When there is no input it will get paths for all available languages.
     *
     * @param   string  $language   The language to display the statistics for
     *
     * @return  array
     */
    protected function getLanguagePaths($language)
    {
        $this->app->getModuleManager()->loadEnabledModules();

        $allLocales = Translator::getAvailableLocaleCodes();
        if (($key = array_search(Translator::DEFAULT_LOCALE, $allLocales)) !== false) {
            unset($allLocales[$key]);
        }

        if (! $language) {
            $locales = $allLocales;
        } else {
            $locales = [$language];
        }

        $paths = [];
        foreach ($locales as $locale) {
            if (! $language || in_array($locale, $allLocales)) {
                $paths[] = implode(
                    DIRECTORY_SEPARATOR,
                    [$this->app->getLocaleDir(), $locale, 'LC_MESSAGES', 'icinga.po']
                );
                foreach ($this->app->getModuleManager()->listEnabledModules() as $module) {
                    $localeDir = $this->app->getModuleManager()->getModule($module)->getLocaleDir();
                    if (is_dir($localeDir)) {
                        $paths[] = implode(
                            DIRECTORY_SEPARATOR,
                            [$localeDir, $locale, 'LC_MESSAGES', $module . '.po']
                        );
                    }
                }
            } else {
                $this->validateLocaleCode($locale);
                $this->fail(
                    sprintf($this->translate('\'%s\' is an unknown locale code.'), $locale)
                );
            }
        }

        return $paths;
    }

    /**
     * Get all paths for the input module
     *
     * @param   string    $module   The module to display the statistics for
     *
     * @return  array
     */
    protected function getModulePaths($module)
    {
        $this->app->getModuleManager()->loadEnabledModules();
        if (! $this->app->getModuleManager()->hasLoaded($module)) {
            $this->fail(sprintf($this->translate('Please make sure the module "%s" is loaded'), $module));
        }
        $localeDir = $this->app->getModuleManager()->loadEnabledModules()->getModule($module)->getLocaleDir();

        if (! is_dir($localeDir)) {
            $this->fail(sprintf($this->translate('There are no translations for module "%s"'), $module));
        }

        try {
            $locales = array_diff(scandir($localeDir), ['.', '..']);
        } catch (Exception $e) {
            $this->fail(sprintf(
                $this->translate('Failed to read %s. An error occurred: %s'),
                $localeDir,
                $e->getMessage()
            ));
        }

        $paths = [];
        foreach ($locales as $locale) {
            $paths[] = implode(DIRECTORY_SEPARATOR, [$localeDir, $locale, 'LC_MESSAGES', $module . '.po']);
        }

        return $paths;
    }

    /**
     * Generates and prints the output of a given statistics object
     *
     * @param   array   $data   All information about a .po file
     */
    public function printOutput($data)
    {
        $percentages = $this->calculatePercentages($data);

        foreach ($percentages as $key => $value) {
            echo $this->screen->colorize(str_repeat('█', $value), $this->colors[$key]);
        }

        if (array_key_exists('moduleName', $data)) {
            printf(
                PHP_EOL . '↳ %s: %s (%s messages)' . PHP_EOL . PHP_EOL,
                $data['locale'],
                $data['moduleName'],
                $data['messageCount']
            );
        } else {
            printf(
                PHP_EOL . '↳ %s (%s messages)' . PHP_EOL . PHP_EOL,
                $data['locale'],
                $data['messageCount']
            );
        }

        printf(
            "\t %s: %d%% (%d messages)" . PHP_EOL,
            $this->screen->colorize('Translated', $this->colors['translated']),
            $percentages['translated'],
            $data['translatedCount']
        );

        printf(
            "\t %s: %d%% (%d messages)" . PHP_EOL,
            $this->screen->colorize('Fuzzy', $this->colors['fuzzy']),
            $percentages['fuzzy'],
            $data['fuzzyCount']
        );

        printf(
            "\t %s: %d%% (%d messages)" . PHP_EOL,
            $this->screen->colorize('Faulty', $this->colors['faulty']),
            $percentages['faulty'],
            $data['faultyCount']
        );

        printf(
            "\t %s: %d%% (%d messages)" . PHP_EOL . PHP_EOL,
            $this->screen->colorize('Untranslated', $this->colors['untranslated']),
            $percentages['untranslated'],
            $data['untranslatedCount']
        );

        echo PHP_EOL;
    }

    /**
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
        } elseif ($module) {
            if ($module === true) {
                $this->fail($this->translate('No module given.'));
            }
            $paths = $this->getModulePaths($module);
        } else {
            if ($language === true) {
                $this->fail($this->translate('No language given.'));
            }
            $paths = $this->getLanguagePaths($language);
        }

        $dataPackages = [];
        foreach ($paths as $path) {
            if (! file_exists($path)) {
                continue;
            }

            try {
                $data = $this->getMessageCounts(new Statistics($path));
            } catch (IcingaException $e) {
                // TODO (JeM): error handling
                Logger::error($e);
                continue;
            }

            $pathParts = explode('/', $path);
            $locale = $pathParts[count($pathParts) - 3];
            $data['locale'] = $locale;

            if ($language) {
                $data['moduleName'] = $pathParts[count($pathParts) - 1];
                $dataPackages[] = $data;
            } elseif (isset($dataPackages[$locale])) {
                foreach ($dataPackages[$locale] as $key => $value) {
                    if ($key !== 'locale') {
                        $dataPackages[$locale][$key] += $data[$key];
                    }
                }
            } else {
                $dataPackages[$locale] = $data;
            }
        }

        foreach ($dataPackages as $data) {
            $this->printOutput($data);
        }
    }
}
