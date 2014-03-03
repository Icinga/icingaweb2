<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Icinga\Application\Platform;
use Icinga\Application\ApplicationBootstrap;
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
        $this->setupLogging()
            ->setupErrorHandling()
            ->loadConfig()
            ->setupTimezone()
            ->setupInternationalization()
            ->parseBasicParams()
            ->fixLoggingConfig()
            ->setupLogger()
            ->setupResourceFactory()
            ->setupModuleManager();
    }

    protected function fixLoggingConfig()
    {
        $conf = & $this->getConfig()->logging;
        if ($conf->type === 'stream') {
            $conf->level = $this->verbose;
            $conf->target = 'php://stderr';
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
