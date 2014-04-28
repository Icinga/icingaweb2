<?php
// @codeCoverageIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

class File
{
    public static function create($filename, $mode = 0664)
    {
        fclose(fopen($filename, 'a'));
        chmod($filename, $mode);
    }
}
// @codeCoverageIgnoreEnd
