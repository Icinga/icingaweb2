<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
     *   icingacli setup token show
     */
    public function showAction()
    {
        $token = file_get_contents($this->app->getConfigDir() . '/setup.token');
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
     *   icingacli setup token create
     */
    public function createAction()
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $token = bin2hex(openssl_random_pseudo_bytes(8));
        } else {
            $token = substr(md5(mt_rand()), 16);
        }

        $filepath = $this->app->getConfigDir() . '/setup.token';

        if (false === file_put_contents($filepath, $token)) {
            $this->fail(sprintf($this->translate('Cannot write setup token "%s" to disk.'), $filepath));
        }

        if (false === chmod($filepath, 0660)) {
            $this->fail(sprintf($this->translate('Cannot change access mode of "%s" to %o.'), $filepath, 0660));
        }

        printf($this->translate("The newly generated setup token is: %s\n"), $token);
    }
}
