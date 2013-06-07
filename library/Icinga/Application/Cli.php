<?php

namespace Icinga\Application;

require_once dirname(__FILE__) . '/ApplicationBootstrap.php';

class Cli extends ApplicationBootstrap
{
    protected $isCli = true;

    protected function bootstrap()
    {
        $this->assertRunningOnCli();
        return $this->loadConfig()
                    ->configureErrorHandling()
                    ->setTimezone();
    }

    /**
     * Fail if Icinga has not been called on CLI
     *
     * @throws Exception
     * @return void
     */
    private static function assertRunningOnCli()
    {
        if (Platform::isCli()) {
            return;
        }
        throw new Exception('Icinga is not running on CLI');
    }
}
