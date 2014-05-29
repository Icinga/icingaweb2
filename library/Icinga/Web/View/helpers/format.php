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

$this->addHelperFunction('timeSincePrefix', function ($timestamp) {
    return '<span class="timesince">'
        . $this->translate(Format::timeSincePrefix($timestamp))
        . ' </span>';
});

$this->addHelperFunction('timeUnless', function ($timestamp) {
    if (! $timestamp) return '';
    return '<span class="timeunless">'
        . Format::timeUntil($timestamp)
        . '</span>';
});

$this->addHelperFunction('timeUnlessPrefix', function ($timestamp) {
    if (! $timestamp) return '';
    return '<span class="timeunless">'
        . $this->translate(Format::timeUntilPrefix($timestamp))
        . '</span>';
});
