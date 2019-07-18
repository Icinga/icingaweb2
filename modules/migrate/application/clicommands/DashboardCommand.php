<?php
/* Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Migrate\Clicommands;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\DashboardContainer;
use Icinga\Cli\Command;
use Icinga\Application\Logger;
use Icinga\Util\Translator;
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
        // Again, no direct way to access this nor to let the module setup its own translation domain
        $localeDirProperty = $moduleReflection->getProperty('localedir');
        $localeDirProperty->setAccessible(true);

        $locales = Translator::getAvailableLocaleCodes();
        $modules = Icinga::app()->getModuleManager()->loadEnabledModules()->getLoadedModules();
        foreach ($this->listDashboardConfigs() as $path) {
            Logger::info('Migrating dashboard config: %s', $path);

            $config = Config::fromIni($path);
            foreach ($modules as $module) {
                $localePath = $localeDirProperty->getValue($module);
                if (! is_dir($localePath)) {
                    // Modules without any translations are not affected
                    continue;
                }

                $launchConfigScriptMethod->invoke($module);
                Translator::registerDomain($module->getName(), $localePath);

                foreach ($locales as $locale) {
                    if ($locale === 'en_US') {
                        continue;
                    }

                    Translator::setupLocale($locale);

                    foreach ($paneItemsProperty->getValue($module) as $paneName => $container) {
                        /** @var DashboardContainer $container */
                        foreach ($config->toArray() as $section => $options) {
                            if (strpos($section, '.') !== false) {
                                list($paneTitle, $dashletTitle) = explode('.', $section, 2);
                            } else {
                                $paneTitle = $section;
                                $dashletTitle = null;
                            }

                            if (isset($options['disabled']) && mt($module->getName(), $paneName) !== $paneTitle) {
                                // `disabled` is checked because if it's a module's pane that's the only reason
                                // why it's in there. If a user utilized the same label though for a custom pane,
                                // it remains as is.
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
