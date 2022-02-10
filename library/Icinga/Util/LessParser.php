<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Util;

use Icinga\Less\Visitor;
use lessc;

require_once 'lessphp/lessc.inc.php';

class LessParser extends lessc
{
    /**
     * @param bool $disableModes Disable replacing compiled Less colors with CSS var() function calls and don't inject
     *                           light mode calls
     */
    public function __construct($disableModes = false)
    {
        if (! $disableModes) {
            $this->setOption('plugins', [new Visitor()]);
        }
    }
}
