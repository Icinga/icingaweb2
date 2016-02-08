<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Cli;

use Exception;
use Icinga\Cli\Command;
use Icinga\Exception\IcingaException;
use Icinga\Module\Translation\Util\GettextTranslationHelper;

/**
 * Base class for translation commands
 */
class TranslationCommand extends Command
{
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
}
