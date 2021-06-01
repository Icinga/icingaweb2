<?php
/* Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Migrate\Clicommands;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\DashboardContainer;
use Icinga\Cli\Command;
use Icinga\Application\Logger;
use ipl\I18n\GettextTranslator;
use ipl\I18n\StaticTranslator;
use ReflectionClass;

class DashboardCommand extends Command
{
    /**
     * Rename translated dashboard sections
     *
     * Migrates all locally found dashboard configurations so that the effects of
     * https://github.com/Icinga/icingaweb2/issues/3542 are reversed.
     *
     * USAGE
     *
     *  icingacli migrate dashboard sections
     */
    public function sectionsAction()
    {
        $moduleReflection = new ReflectionClass('Icinga\Application\Modules\Module');
        // There's no direct way to invoke this method
        $launchConfigScriptMethod = $moduleReflection->getMethod('launchConfigScript');
        $launchConfigScriptMethod->setAccessible(true);
        // Calling getDashboard() results in Url::fromPath() getting called as well == the CLI's death
        $paneItemsProperty = $moduleReflection->getProperty('paneItems');
        $paneItemsProperty->setAccessible(true);

        /** @var GettextTranslator $translator */
        $translator = StaticTranslator::$instance;

        $locales = $translator->listLocales();
        $modules = Icinga::app()->getModuleManager()->getLoadedModules();
        foreach ($this->listDashboardConfigs() as $path) {
            Logger::info('Migrating dashboard config: %s', $path);

            $config = Config::fromIni($path);
            foreach ($modules as $module) {
                if (! $module->hasLocales()) {
                    // Modules without any translations are not affected
                    continue;
                }

                $launchConfigScriptMethod->invoke($module);

                foreach ($locales as $locale) {
                    if ($locale === 'en_US') {
                        continue;
                    }

                    try {
                        $translator->setLocale($locale);
                    } catch (Exception $e) {
                        Logger::debug('Ignoring locale "%s". Reason: %s', $locale, $e->getMessage());
                        continue;
                    }

                    foreach ($paneItemsProperty->getValue($module) as $paneName => $container) {
                        /** @var DashboardContainer $container */
                        foreach ($config->toArray() as $section => $options) {
                            if (strpos($section, '.') !== false) {
                                list($paneTitle, $dashletTitle) = explode('.', $section, 2);
                            } else {
                                $paneTitle = $section;
                                $dashletTitle = null;
                            }

                            if (mt($module->getName(), $paneName) !== $paneTitle) {
                                continue;
                            }

                            $dashletName = null;
                            if ($dashletTitle !== null) {
                                foreach ($container->getDashlets() as $name => $url) {
                                    if (mt($module->getName(), $name) === $dashletTitle) {
                                        $dashletName = $name;
                                        break;
                                    }
                                }

                                if ($dashletName === null) {
                                    $dashletName = $dashletTitle;
                                }
                            }

                            $newSection = $paneName . ($dashletName ? '.' . $dashletName : '');
                            $config->removeSection($section);
                            $config->setSection($newSection, $options);

                            Logger::info('Migrated section "%s" to "%s"', $section, $newSection);
                        }
                    }
                }
            }

            $config->saveIni();
        }
    }

    protected function listDashboardConfigs()
    {
        $dashboardConfigPath = Config::resolvePath('dashboards');

        try {
            $dashboardConfigDir = opendir($dashboardConfigPath);
        } catch (Exception $e) {
            Logger::error('Cannot access dashboard configuration: %s', $e);
            exit(1);
        }

        while ($entry = readdir($dashboardConfigDir)) {
            $userDashboardPath = join(DIRECTORY_SEPARATOR, [$dashboardConfigPath, $entry, 'dashboard.ini']);
            if (is_file($userDashboardPath)) {
                yield $userDashboardPath;
            }
        }
    }
}
