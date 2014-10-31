<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Icinga\Application\Platform;
use Icinga\Application\ApplicationBootstrap;
use Icinga\Cli\Params;
use Icinga\Cli\Loader;
use Icinga\Cli\Screen;
use Icinga\Application\Logger;
use Icinga\Application\Benchmark;
use Icinga\Exception\ProgrammingError;
use Zend_Config;

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
            ->loadConfig()
            ->setupTimezone()
            ->setupInternationalization()
            ->parseBasicParams()
            ->setupLogger()
            ->setupResourceFactory()
            ->setupModuleManager();
    }

    protected function setupLogging()
    {
        Logger::create(
            new Zend_Config(
                array(
                    'level' => Logger::INFO,
                    'log'   => 'file',
                    'file'  => 'php://stderr'
                )
            )
        );
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
        if ($this->params->shift('autocomplete')) {
            $this->params->unshift('autocomplete');
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
        $loader->dispatch();
        Benchmark::measure('All done');
        if ($this->showBenchmark) {
            Benchmark::dump();
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
