<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Cli;

use Exception;
use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Exception\IcingaException;
use Icinga\Module\Translation\Statistics;
use Icinga\Module\Translation\Util\GettextTranslationHelper;
use Icinga\Util\Translator;

/**
 * Base class for translation commands
 */
class TranslationCommand extends Command
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
     * Get the gettext translation helper
     *
     * @param   string $locale
     *
     * @return  GettextTranslationHelper
     */
    public function getTranslationHelper($locale)
    {
        $helper = new GettextTranslationHelper($this->app, $locale);
        $helper->setConfig($this->Config());
        return $helper;
    }

    /**
     * Check whether the given locale code is valid
     *
     * @param   string  $code   The locale code to validate
     *
     * @return  string          The validated locale code
     *
     * @throws  Exception       In case the locale code is invalid
     */
    public function validateLocaleCode($code)
    {
        if (! preg_match('@[a-z]{2}_[A-Z]{2}@', $code)) {
            throw new IcingaException(
                'Locale code \'%s\' is not valid. Expected format is: ll_CC',
                $code
            );
        }

        return $code;
    }

    /**
     * Check whether the given module is available and enabled
     *
     * @param   string  $name   The module name to validate
     *
     * @return  string          The validated module name
     *
     * @throws  Exception       In case the given module is not available or not enabled
     */
    public function validateModuleName($name)
    {
        $enabledModules = $this->app->getModuleManager()->listEnabledModules();

        if (! in_array($name, $enabledModules)) {
            throw new IcingaException(
                'Module with name \'%s\' not found or is not enabled',
                $name
            );
        }

        return $name;
    }

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
    public function roundPercentage($number, $maxCount)
    {
        $percentage = $number / $maxCount * 100;
        if ($percentage !== 0 && $percentage < 1) {
            return 1;
        }

        return round($percentage);
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

    public function extendShowActionLanguages($language = true)
    {
        $paths = $this->getLanguagePaths($language);

        $dataPackages = [];
        foreach ($paths as $path) {
            try {
                $data = $this->getMessageCounts(Statistics::load($path));
            } catch (IcingaException $e) {
                // TODO (JeM): error handling
                Logger::error($e);
                continue;
            }

            // TODO (JeM): Displaying the locale when you sort by specific locale is superfluous
            $pathParts = explode('/', $path);
            $locale = $pathParts[count($pathParts) - 3];
            $data['locale'] = $locale;

            $data['moduleName'] = substr($pathParts[count($pathParts) - 1], 0, -3); //trims the '.po'
            $dataPackages[] = $data;
        }

        foreach ($dataPackages as $data) {
            $this->printOutput($data);
        }
    }

    public function extendShowActionModules($module = true)
    {
        if ($module === true) {
            $paths = $this->getLanguagePaths();
            //TODO (JeM): Sort by modules and add up counts for all languages
        } else {
            $paths = $this->getModulePaths($module);

            foreach ($paths as $path) {
                try {
                    $data = $this->getMessageCounts(Statistics::load($path));
                } catch (IcingaException $e) {
                    // TODO (JeM): error handling
                    Logger::error($e);
                    continue;
                }

                $pathParts = explode('/', $path);
                $locale = $pathParts[count($pathParts) - 3];
                $data['locale'] = $locale;

                $this->printOutput($data);
            }
        }
    }
}
