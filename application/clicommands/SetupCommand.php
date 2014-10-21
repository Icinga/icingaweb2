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
     * Re-generates the setup token used to authenticate when installing Icinga Web 2 using the web-based wizard.
     *
     * USAGE:
     *
     *   icingacli setup generateToken
     */
    public function generateTokenAction()
    {
        if (false === $this->isSuperUser()) {
            $this->fail($this->translate('This action needs to be run as super user in order to work properly!'));
            return false;
        }

        $token = bin2hex(openssl_random_pseudo_bytes(8));
        $filepath = $this->app->getConfigDir() . '/setup.token';

        if (false === file_put_contents($filepath, $token)) {
            $this->fail(sprintf($this->translate('Cannot write setup token "%s" to disk.'), $filepath));
        }

        if (false === chmod($filepath, 0660)) {
            $this->fail(sprintf($this->translate('Cannot change access mode of "%s" to %o.'), $filepath, 0660));
        }

        printf($this->translate("The newly generated setup token is: %s\n"), $token);
    }

    /**
     * Create the configuration directory
     *
     * This command creates the configuration directory for Icinga Web 2. The `group' argument
     * is mandatory and should be the groupname of the user your web server is running as.
     *
     * USAGE:
     *
     *   icingacli setup createConfigDirectory <group> [options]
     *
     * OPTIONS:
     *
     *   --mode  The access mode to use. Default is: 2775
     *   --path  The path to the configuration directory. If omitted the default is used.
     *
     * EXAMPLES:
     *
     *   icingacli setup createConfigDirectory apache
     *   icingacli setup createConfigDirectory apache --mode 2770
     *   icingacli setup createConfigDirectory apache --path /some/path
     */
    public function createConfigDirectoryAction()
    {
        if (false === $this->isSuperUser()) {
            $this->fail($this->translate('This action needs to be run as super user in order to work properly!'));
            return false;
        }

        $group = $this->params->getStandalone();
        if ($group === null) {
            $this->fail($this->translate('The `group\' argument is mandatory.'));
            return false;
        }

        $path = $this->params->get('path', $this->app->getConfigDir());
        if (file_exists($path)) {
            $this->fail(sprintf($this->translate('Path "%s" already exists.'), $path));
            return false;
        }

        $mode = octdec($this->params->get('mode', '2775'));
        if (false === mkdir($path)) {
            $this->fail(sprintf($this->translate('Unable to create path: %s'), $path));
            return false;
        }

        $old = umask(0); // Prevent $mode from being mangled by the system's umask ($old)
        chmod($path, $mode);
        umask($old);
        chgrp($path, $group);
    }

    /**
     * Return whether the current user is a super user
     *
     * @return  bool
     */
    protected function isSuperUser()
    {
        return intval(shell_exec('echo $EUID')) === 0;
    }
}
