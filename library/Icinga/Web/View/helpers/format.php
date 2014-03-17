<?php

namespace Icinga\Web\View;

use Icinga\Web\Url;
use Icinga\Util\Format;

$this->addHelperFunction('format', function () {
    return Format::getInstance();
});

$this->addHelperFunction('timeSince', function ($timestamp) {
    return '<span class="timesince">'
        . Format::timeSince($timestamp)
        . '</span>';
});

$this->addHelperFunction('timeUnless', function ($timestamp) {
    if (! $timestamp) return '';
    return '<span class="timeunless">'
        . Format::timeUntil($timestamp)
        . '</span>';
});

