<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Logger\Writer;

use Zend_Config;
use Icinga\Application\Logger;
use Icinga\Application\Logger\Writer\FileWriter;
use Icinga\Test\BaseTestCase;

class StreamWriterTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->target = tempnam(sys_get_temp_dir(), 'log');
    }

    public function tearDown()
    {
        parent::tearDown();

        unlink($this->target);
    }

    public function testWhetherStreamWriterCreatesMissingFiles()
    {
        new FileWriter(new Zend_Config(array('file' => $this->target)));
        $this->assertFileExists($this->target, 'StreamWriter does not create missing files on initialization');
    }

    /**
     * @depends testWhetherStreamWriterCreatesMissingFiles
     */
    public function testWhetherStreamWriterWritesMessages()
    {
        $writer = new FileWriter(new Zend_Config(array('file' => $this->target)));
        $writer->log(Logger::ERROR, 'This is a test error');
        $log = file_get_contents($this->target);
        $this->assertContains('This is a test error', $log, 'StreamWriter does not write log messages');
    }
}
