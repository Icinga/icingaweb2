<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Clicommands;

use Icinga\Cli\Command;

/**
 * Setup Icinga Web 2
 *
 * The setup command allows you to install/configure Icinga Web 2
 *
 * Usage: icingacli setup <action> [<argument>]
 */
class SetupCommand extends Command
{
    /**
     * Display the current setup token
     *
     * Shows you the current setup token used to authenticate when installing Icinga Web 2 using the web-based wizard
     *
     * USAGE:
     *
     *   icingacli setup showToken
     */
    public function showTokenAction()
    {
        $token = @file_get_contents($this->app->getConfigDir() . '/setup.token');
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
     * Re-generates the setup token used to authenticate when installing Icinga Web 2 using the web-based wizard.
     * Note that it is required to run this command while logged in as your webserver's user or to make him the
     * owner of the created file afterwards manually.
     *
     * USAGE:
     *
     *   icingacli setup generateToken
     */
    public function generateTokenAction()
    {
        $token = bin2hex(openssl_random_pseudo_bytes(8));
        $filepath = $this->app->getConfigDir() . '/setup.token';

        if (false === @file_put_contents($filepath, $token)) {
            $this->fail(sprintf($this->translate('Cannot write setup token "%s" to disk.'), $filepath));
        }

        if (false === @chmod($filepath, 0640)) {
            $this->fail(sprintf($this->translate('Cannot change access mode of "%s" to %o.'), $filepath, 0640));
        }

        printf($this->translate("The newly generated setup token is: %s\n"), $token);
    }
}
