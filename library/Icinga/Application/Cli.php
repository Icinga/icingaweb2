<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Icinga\Application\Platform;
use Icinga\Application\ApplicationBootstrap;
use Icinga\Application\Modules\Manager as ModuleManager;
use Icinga\Cli\Params;
use Icinga\Cli\Loader;
use Icinga\Cli\Screen;
use Icinga\Application\Benchmark;
use Icinga\Exception\ProgrammingError;

// @codingStandardsIgnoreStart
require_once dirname(__FILE__) . '/ApplicationBootstrap.php';
require_once dirname(__FILE__). '/../Exception/ProgrammingError.php';
// @codingStandardsIgnoreStop

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
        $this->setupConfig()
             ->parseBasicParams()
             ->fixLoggingConfig()
             ->setupErrorHandling()
             ->setupResourceFactory()
             ->setupModules()
             ;
    }

    protected function fixLoggingConfig()
    {
        $conf = & $this->getConfig()->logging;
        if ($conf->type === 'stream') {
            $conf->verbose = $this->verbose;
            $conf->target = 'php://stderr';
        }
        if ($conf->debug && $conf->debug->type === 'stream') {
            $conf->debug->target = 'php://stderr';
            $conf->debug->enable = $this->debug;
        }
        return $this;
    }

    public function cliLoader()
    {
        if ($this->cliLoader === null) {
            $this->cliLoader = new Loader($this);
        }
        return $this->cliLoader;
    }

    /**
     * Setup module loader
     *
     * TODO: This can be removed once broken bootstrapping has been fixed
     *       Loading the module manager and enabling all modules have former
     *       been two different tasks. CLI does NOT enable any module by default.
     *
     * @return self
     */
    protected function setupModules()
    {
        $this->moduleManager = new ModuleManager($this, $this->getConfigDir('enabledModules'));
        return $this;
    }

    /**
     * Getter for module manager
     *
     * TODO: This can also be removed once fixed. Making everything private
     *       made this duplication necessary
     *
     * @return ModuleManager
     */
    public function getModuleManager()
    {
        return $this->moduleManager;
    }

    protected function parseBasicParams()
    {
        $this->params = Params::parse();
        if ($this->params->shift('help')) {
            $this->params->unshift('help');
        }
        $watch = $this->params->shift('watch');
        if ($watch === true) {
            $watch = 5;
        }
        if (preg_match('~^\d+$~', $watch)) {
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
        $loader = new Loader($this);
        $loader->parseParams();
        $loader->dispatch();
        Benchmark::measure('All done');
        if ($this->showBenchmark) {
            Benchmark::dump();
        }
    }

    protected function dispatchEndless()
    {
        $loader = new Loader($this);
        $loader->parseParams();
        $screen = Screen::instance();
        while (true) {
            Benchmark::measure('Watch mode - loop begins');
            echo $screen->clear();
            $params = clone($this->params);
            $loader->dispatch();
            Benchmark::measure('Dispatch done');
            if ($this->showBenchmark) {
                Benchmark::dump();
            }
            Benchmark::reset();
            $this->params = $params;
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
