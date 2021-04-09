<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use Icinga\Util\File;
use Icinga\Test\BaseTestCase;

class FileTest extends BaseTestCase
{
    public function testWhetherWritingToNonWritableFilesThrowsAnException()
    {
        $this->expectException(\Icinga\Exception\NotWritableError::class);

        $file = new File('/dev/null');
        $file->fwrite('test');
    }

    public function testWhetherTruncatingNonWritableFilesThrowsAnException()
    {
        $this->expectException(\Icinga\Exception\NotWritableError::class);

        $file = new File('/dev/null');
        $file->ftruncate(0);
    }
}
