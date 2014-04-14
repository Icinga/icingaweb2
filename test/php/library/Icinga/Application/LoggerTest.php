<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Application;

use \Zend_Config;
use Icinga\Logger\Logger;
use Icinga\Test\BaseTestCase;

class LoggerTest extends BaseTestCase
{
    public function testLogfileCreation()
    {
        $target = tempnam(sys_get_temp_dir(), 'log');
        unlink($target);
        new Logger(
            new Zend_Config(
                array(
                    'enable'    => true,
                    'level'     => Logger::$ERROR,
                    'type'      => 'stream',
                    'target'    => $target
                )
            )
        );
        $this->assertFileExists($target, 'Logger did not create the log file');
        unlink($target);
    }

    /**
     * @depends                 testLogfileCreation
     */
    public function testLoggingErrorMessages()
    {
        $target = tempnam(sys_get_temp_dir(), 'log');
        unlink($target);
        $logger = new Logger(
            new Zend_Config(
                array(
                    'enable'    => true,
                    'level'     => Logger::$ERROR,
                    'type'      => 'stream',
                    'target'    => $target
                )
            )
        );
        $logger->log('This is a test error', Logger::$ERROR);
        $log = file_get_contents($target);
        unlink($target);
        $this->assertContains('This is a test error', $log, 'Log does not contain the error "This is a test error"');
    }
}
