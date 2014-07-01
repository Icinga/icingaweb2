<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Util;

use Icinga\Util\File;
use Icinga\Test\BaseTestCase;

class FileTest extends BaseTestCase
{
    /**
     * @expectedException \Icinga\Exception\NotWritableError
     */
    public function testWhetherWritingToNonWritableFilesThrowsAnException()
    {
        $file = new File('/dev/null');
        $file->fwrite('test');
    }

    /**
     * @expectedException \Icinga\Exception\NotWritableError
     */
    public function testWhetherTruncatingNonWritableFilesThrowsAnException()
    {
        $file = new File('/dev/null');
        $file->ftruncate(0);
    }
}
