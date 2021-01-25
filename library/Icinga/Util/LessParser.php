<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Util;

use Exception;
use lessc;
use Icinga\Application\Logger;

require_once 'lessphp/lessc.inc.php';

class LessParser extends lessc
{
    protected function get($name)
    {
        try {
            return parent::get($name);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }

        return ['string', '', []];
    }

    protected function compileProp($prop, $block, $out)
    {
        try {
            parent::compileProp($prop, $block, $out);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'is undefined:') === false) {
                // We silence mixin errors only
                throw $e;
            }

            Logger::error($e->getMessage());
        }
    }
}
