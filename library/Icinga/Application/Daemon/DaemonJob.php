<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Daemon;

use React\EventLoop\LoopInterface;

interface DaemonJob
{
    /**
     * Attach this job to the given event loop
     *
     * @param LoopInterface $loop
     *
     * @return void
     */
    public function attach(LoopInterface $loop): void;

    /**
     * Stop processing of this job
     *
     * Cancelling pending runs of the job is optional.
     *
     * @return void
     */
    public function cancel(): void;
}
