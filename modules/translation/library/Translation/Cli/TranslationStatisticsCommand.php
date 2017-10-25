<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Cli;

use Exception;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Module\Translation\Statistics;
use Icinga\Util\Translator;

class TranslationStatisticsCommand extends TranslationCommand
{
    /**
     * Calculates the percentages from the statistics
     *
     * @param   array   $numbers    The collected data
     *
     * @return  array
     */
    public function calculatePercentages($numbers)
    {
        $percentages = [];
        $percentages['translated'] = $this->roundPercentage($numbers['translatedCount'], $numbers['messageCount']);
        $percentages['fuzzy'] = $this->roundPercentage($numbers['fuzzyCount'], $numbers['messageCount']);
        $percentages['untranslated'] = $this->roundPercentage($numbers['untranslatedCount'], $numbers['messageCount']);

        $percentageSum = $percentages['translated'][0]
            + $percentages['fuzzy'][0]
            + $percentages['untranslated'][0];
        if ($percentageSum != 100) {
            $toAdapt = array_search(max($percentages), $percentages);
            $percentages[($toAdapt)][0] += 100 - $percentageSum;
        }

        $percentages['faulty'] = $this->roundPercentage($numbers['faultyCount'], $numbers['messageCount']);

        return $percentages;
    }

    /**
     * Rounds the percentage so that it always is a full percent and at least one
     *
     * @param   int     $number     The percentage value
     * @param   int     $maxCount   The fundamental value
     *
     * @return  array               The first index is for drawing (whole numbers), the second is the precise percentage.
     */
    public function roundPercentage($number, $maxCount)
    {
        $percentage = $number / $maxCount * 100;
        if ($percentage !== 0 && $percentage < 1) {
            return [1, round($percentage, 2)];
        }
        return [round($percentage), round($percentage, 2)];
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
            $path = implode(DIRECTORY_SEPARATOR, [$localeDir, $locale, 'LC_MESSAGES', $module . '.po']);
            if (!file_exists($path)) {
                continue;
            }
            $paths[] = $path;
        }

        return $paths;
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
        $numbers['messageCount'] =  $statistics->getEntryCount();
        $numbers['untranslatedCount'] = $statistics->getUntranslatedEntryCount();
        $numbers['translatedCount'] = $statistics->getTranslatedEntryCount();
        $numbers['fuzzyCount'] = $statistics->getFuzzyEntryCount();

        $numbers['faultyCount'] = $statistics->getFaultyEntryCount();

        return $numbers;
    }

    /**
     * Get all paths for the input language
     *
     * When there is no input it will get paths for all available languages.
     *
     * @param   string|bool  $language   The language to display the statistics for
     *
     * @return  array
     */
    protected function getLanguagePaths($language = true)
    {
        $this->app->getModuleManager()->loadEnabledModules();

        $allLocales = Translator::getAvailableLocaleCodes();
        if (($key = array_search(Translator::DEFAULT_LOCALE, $allLocales)) !== false) {
            unset($allLocales[$key]);
        }

        if ($language === true) {
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
                        $path = implode(
                            DIRECTORY_SEPARATOR,
                            [$localeDir, $locale, 'LC_MESSAGES', $module . '.po']
                        );
                        if (!file_exists($path)) {
                            continue;
                        }
                        $paths[] = $path;
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
     * Generates and prints the output of a given statistics object
     *
     * @param   array   $data   All information about a .po file
     */
    public function printOutput($data)
    {
        $percentages = $this->calculatePercentages($data);

        foreach ($percentages as $key => $value) {
            if ($key !== 'faulty') {
                echo $this->screen->colorize(str_repeat('█', $value[0]), $this->colors[$key]);
            }
        }

        if ($percentages['faulty'][0] > 0){
            echo PHP_EOL
                . $this->screen->colorize(str_repeat('█', $percentages['faulty'][0]), $this->colors['faulty']);
            printf(
                " %s: %s%% (%d messages)",
                $this->screen->colorize('Faulty', $this->colors['faulty']),
                $percentages['faulty'][1],
                $data['faultyCount']
            );

        }

        if (array_key_exists('moduleName', $data) && array_key_exists('locale', $data)) {
            printf(
                PHP_EOL . '↳ %s: %s (%s messages)' . PHP_EOL,
                $data['locale'],
                $data['moduleName'],
                $data['messageCount']
            );
        } elseif (array_key_exists('moduleName', $data)) {
            printf(
                PHP_EOL . '↳ %s (%s messages)' . PHP_EOL,
                $data['moduleName'],
                $data['messageCount']
            );
        } else {
            printf(
                PHP_EOL . '↳ %s (%s messages)' . PHP_EOL,
                $data['locale'],
                $data['messageCount']
            );
        }

        printf(
            "\t %s: %s%% (%d messages)" . PHP_EOL,
            $this->screen->colorize('Translated', $this->colors['translated']),
            $percentages['translated'][1],
            $data['translatedCount']
        );

        printf(
            "\t %s: %s%% (%d messages)" . PHP_EOL,
            $this->screen->colorize('Fuzzy', $this->colors['fuzzy']),
            $percentages['fuzzy'][1],
            $data['fuzzyCount']
        );

        printf(
            "\t %s: %s%% (%d messages)" . PHP_EOL . PHP_EOL,
            $this->screen->colorize('Untranslated', $this->colors['untranslated']),
            $percentages['untranslated'][1],
            $data['untranslatedCount']
        );

        echo PHP_EOL;
    }

    /**
     * Extracts the locale and module name for the po-file of the given path
     *
     * @param   string  $path   Path to a po-file
     *
     * @return  array
     */
    protected function extractMetaData($path)
    {
        $metaData = [];
        $pathParts = explode('/', $path);
        $metaData['locale'] = $pathParts[count($pathParts) - 3];
        $metaData['moduleName'] = substr($pathParts[count($pathParts) - 1], 0, -3); //trims the '.po'


        return $metaData;
    }

    /**
     * Takes input from the show command and prints the statistics
     *
     * @param   string  $module
     * @param   string  $language
     */
    public function showStatistics($module = null, $language = null)
    {
        if ($language !== null) {
            $paths = $this->getLanguagePaths($language);
        } elseif ($module === true || $module === null) {
            $module = true;
            $paths = $this->getLanguagePaths();
        } else {
            $paths = $this->getModulePaths($module);
        }

        $dataPackages = [];
        foreach ($paths as $path) {
            try {
                $data = $this->getMessageCounts(Statistics::load($path));
            } catch (IcingaException $e) {
                // TODO (JeM): error handling
                Logger::error($e);
                continue;
            }

            $data = array_merge($data, $this->extractMetaData($path));

            if ($module === true || $language === true) {
                $groupByModule = (bool)$module;
                $attribute = $data[$groupByModule ? 'moduleName' : 'locale'];

                unset($data[$groupByModule ? 'locale' : 'moduleName']);
                if (array_key_exists($attribute, $dataPackages)) {
                    $dataPackages[$attribute]['messageCount'] += $data['messageCount'];
                    $dataPackages[$attribute]['translatedCount'] += $data['translatedCount'];
                    $dataPackages[$attribute]['fuzzyCount'] += $data['fuzzyCount'];
                    $dataPackages[$attribute]['faultyCount'] += $data['faultyCount'];
                    $dataPackages[$attribute]['untranslatedCount'] += $data['untranslatedCount'];
                } else {
                    $dataPackages[$attribute] = $data;
                }
            } else {
                $this->printOutput($data);
            }
        }

        if (! empty($dataPackages)) {
            foreach ($dataPackages as $data) {
                $this->printOutput($data);
            }
        }
    }
}
