<?php
/* Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Helper;

use Parsedown;

class Markdown
{
    public static function text($content)
    {
        require_once 'Parsedown/Parsedown.php';
        return HtmlPurifier::process(Parsedown::instance()->text($content));
    }
}
