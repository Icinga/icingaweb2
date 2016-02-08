<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Clicommands;

use Icinga\Cli\Command;

/**
 * Maintain the setup wizard's authentication
 *
 * The token command allows you to display the current setup token or to create a new one.
 *
 * Usage: icingacli setup token <action>
 */
class TokenCommand extends Command
{
    /**
     * Display the current setup token
     *
     * Shows you the current setup token used to authenticate when setting up Icinga Web 2 using the web-based wizard.
     *
     * USAGE:
     *
     *   icingacli setup token show [options]
     *
     * OPTIONS:
     *
     *  --config=<directory>    Path to Icinga Web 2's configuration files [/etc/icingaweb2]
     */
    public function showAction()
    {
        $configDir = $this->params->get('config', $this->app->getConfigDir());
        if (! is_string($configDir) || strlen(trim($configDir)) === 0) {
            $this->fail($this->translate(
                'The argument --config expects a path to Icinga Web 2\'s configuration files'
            ));
        }

        $token = file_get_contents($configDir . '/setup.token');
        if (! $token) {
            $this->fail(
                $this->translate('Nothing to show. Please create a new setup token using the generateToken action.')
            );
        }

        printf($this->translate("The current setup token is: %s\n"), $token);
    }

    /**
     * Create a new setup token
     *
     * Re-generates the setup token used to authenticate when setting up Icinga Web 2 using the web-based wizard.
     *
     * USAGE:
     *
     *   icingacli setup token create [options]
     *
     * OPTIONS:
     *
     *  --config=<directory>    Path to Icinga Web 2's configuration files [/etc/icingaweb2]
     */
    public function createAction()
    {
        $configDir = $this->params->get('config', $this->app->getConfigDir());
        if (! is_string($configDir) || strlen(trim($configDir)) === 0) {
            $this->fail($this->translate(
                'The argument --config expects a path to Icinga Web 2\'s configuration files'
            ));
        }

        $file = $configDir . '/setup.token';

        if (function_exists('openssl_random_pseudo_bytes')) {
            $token = bin2hex(openssl_random_pseudo_bytes(8));
        } else {
            $token = substr(md5(mt_rand()), 16);
        }

        if (false === file_put_contents($file, $token)) {
            $this->fail(sprintf($this->translate('Cannot write setup token "%s" to disk.'), $file));
        }

        if (! chmod($file, 0660)) {
            $this->fail(sprintf($this->translate('Cannot change access mode of "%s" to %o.'), $file, 0660));
        }

        printf($this->translate("The newly generated setup token is: %s\n"), $token);
    }
}
