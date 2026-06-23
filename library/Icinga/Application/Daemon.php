<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application;

use Icinga\Application\Daemon\DaemonJob;
use LogicException;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class Daemon
{
    /** @var LoopInterface */
    protected $loop;

    /** @var bool */
    protected $shuttingDown = false;

    /** @var DaemonJob[] */
    protected $jobs = [];

    /**
     * Create a new Daemon
     *
     * @param LoopInterface|null $loop If not given, the default loop is used
     */
    public function __construct(LoopInterface $loop = null)
    {
        $this->loop = $loop ?? Loop::get();
    }

    /**
     * Initialize the daemon
     *
     * @return void
     */
    protected function initialize(): void
    {
        $this->registerSignals();
    }

    /**
     * Register signals
     *
     * @return void
     */
    protected function registerSignals(): void
    {
        $this->loop->addSignal(SIGTERM, [$this, 'shutdown']);
        $this->loop->addSignal(SIGINT, [$this, 'shutdown']);
    }

    /**
     * Deregister signals
     *
     * @return void
     */
    protected function deregisterSignals(): void
    {
        $this->loop->removeSignal(SIGTERM, [$this, 'shutdown']);
        $this->loop->removeSignal(SIGINT, [$this, 'shutdown']);
    }

    /**
     * Launch the daemon
     *
     * @return void
     */
    public function run(): void
    {
        if (empty($this->jobs)) {
            throw new LogicException('No jobs added');
        }

        $this->loop->futureTick(function () {
            $this->initialize();
        });
    }

    /**
     * Stop the daemon
     *
     * @return void
     */
    public function shutdown(): void
    {
        if ($this->shuttingDown) {
            return;
        }

        $this->shuttingDown = true;
        $this->deregisterSignals();

        foreach ($this->jobs as $job) {
            $job->cancel();
        }
    }

    /**
     * Add a job to this daemon
     *
     * @param DaemonJob $job
     *
     * @return $this
     */
    public function addJob(DaemonJob $job): self
    {
        $this->jobs[] = $job;
        $job->attach($this->loop);

        return $this;
    }
}
