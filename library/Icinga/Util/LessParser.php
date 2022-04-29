<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Util;

use Icinga\Less\Visitor;
use lessc;

require_once 'lessphp/lessc.inc.php';

class LessParser extends lessc
{
    public function __construct()
    {
        $this->setOption('plugins', [new Visitor()]);
    }
}
