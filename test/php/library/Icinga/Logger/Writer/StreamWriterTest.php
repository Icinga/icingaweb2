<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Logger\Writer;

use Icinga\Data\ConfigObject;
use Icinga\Application\Logger;
use Icinga\Application\Logger\Writer\FileWriter;
use Icinga\Test\BaseTestCase;

class StreamWriterTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->target = tempnam(sys_get_temp_dir(), 'log');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unlink($this->target);
    }

    public function testWhetherStreamWriterCreatesMissingFiles()
    {
        new FileWriter(new ConfigObject(['file' => $this->target]));
        $this->assertFileExists($this->target, 'StreamWriter does not create missing files on initialization');
    }

    /**
     * @depends testWhetherStreamWriterCreatesMissingFiles
     */
    public function testWhetherStreamWriterWritesMessages()
    {
        $writer = new FileWriter(new ConfigObject(['file' => $this->target]));
        $writer->log(Logger::ERROR, 'This is a test error');
        $log = file_get_contents($this->target);
        $this->assertStringContainsString('This is a test error', $log, 'StreamWriter does not write log messages');
    }
}
