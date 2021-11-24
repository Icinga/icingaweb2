<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

use Icinga\Application\Platform;
use Icinga\Application\ApplicationBootstrap;
use Icinga\Authentication\Auth;
use Icinga\Cli\Params;
use Icinga\Cli\Loader;
use Icinga\Cli\Screen;
use Icinga\Application\Logger;
use Icinga\Application\Benchmark;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ProgrammingError;
use Icinga\User;

require_once __DIR__ . '/ApplicationBootstrap.php';

class Cli extends ApplicationBootstrap
{
    protected $isCli = true;

    protected $params;

    protected $showBenchmark = false;

    protected $watchTimeout;

    protected $cliLoader;

    protected $verbose;

    protected $debug;

    protected function bootstrap()
    {
        $this->assertRunningOnCli();
        $this->setupLogging()
            ->setupErrorHandling()
            ->loadLibraries()
            ->loadConfig()
            ->setupTimezone()
            ->prepareInternationalization()
            ->setupInternationalization()
            ->parseBasicParams()
            ->setupLogger()
            ->setupModuleManager()
            ->setupUserBackendFactory()
            ->loadSetupModuleIfNecessary()
            ->setupFakeAuthentication();
    }

    /**
     * {@inheritdoc}
     */
    protected function setupLogging()
    {
        Logger::create(
            new ConfigObject(
                array(
                    'log' => 'stderr'
                )
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function setupLogger()
    {
        $config = new ConfigObject();
        $config->log = $this->params->shift('log', 'stderr');
        if ($config->log === 'file') {
            $config->file = $this->params->shiftRequired('log-path');
        } elseif ($config->log === 'syslog') {
            $config->application = 'icingacli';
        }

        if ($this->params->get('verbose', false)) {
            $config->level = Logger::INFO;
        } elseif ($this->params->get('debug', false)) {
            $config->level = Logger::DEBUG;
        } else {
            $config->level = Logger::WARNING;
        }

        Logger::create($config);
        return $this;
    }

    protected function setupFakeAuthentication()
    {
        Auth::getInstance()->setUser(new User('cli'));

        return $this;
    }

    public function cliLoader()
    {
        if ($this->cliLoader === null) {
            $this->cliLoader = new Loader($this);
        }
        return $this->cliLoader;
    }

    protected function parseBasicParams()
    {
        $this->params = Params::parse();
        if ($this->params->shift('help')) {
            $this->params->unshift('help');
        }
        if ($this->params->shift('version')) {
            $this->params->unshift('version');
        }
        if ($this->params->shift('autocomplete')) {
            $this->params->unshift('autocomplete');
        }
        $watch = $this->params->shift('watch');
        if ($watch === true) {
            $watch = 5;
        }
        if ($watch !== null && preg_match('~^\d+$~', $watch)) {
            $this->watchTimeout = (int) $watch;
        }

        $this->debug = (int) $this->params->get('debug');
        $this->verbose = (int) $this->params->get('verbose');

        $this->showBenchmark = (bool) $this->params->shift('benchmark');
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function dispatchModule($name, $basedir = null)
    {
        $this->getModuleManager()->loadModule($name, $basedir);
        $this->cliLoader()->setModuleName($name);
        $this->dispatch();
    }

    public function dispatch()
    {
        Benchmark::measure('Dispatching CLI command');

        if ($this->watchTimeout === null) {
            $this->dispatchOnce();
        } else {
            $this->dispatchEndless();
        }
    }

    protected function dispatchOnce()
    {
        $loader = $this->cliLoader();
        $loader->parseParams();
        $result = $loader->dispatch();
        Benchmark::measure('All done');
        if ($this->showBenchmark) {
            Benchmark::dump();
        }
        if ($result === false) {
            exit(3);
        }
    }

    protected function dispatchEndless()
    {
        $loader = $this->cliLoader();
        $loader->parseParams();
        $screen = Screen::instance();

        while (true) {
            Benchmark::measure('Watch mode - loop begins');
            ob_start();
            $params = clone($this->params);
            $loader->dispatch($params);
            Benchmark::measure('Dispatch done');
            if ($this->showBenchmark) {
                Benchmark::dump();
            }
            Benchmark::reset();
            $out = ob_get_contents();
            ob_end_clean();
            echo $screen->clear() . $out;
            sleep($this->watchTimeout);
        }
    }

    /**
     * Fail if Icinga has not been called on CLI
     *
     * @throws ProgrammingError
     * @return void
     */
    private function assertRunningOnCli()
    {
        if (Platform::isCli()) {
            return;
        }
        throw new ProgrammingError('Icinga is not running on CLI');
    }
}
