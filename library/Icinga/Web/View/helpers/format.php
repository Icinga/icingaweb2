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

$this->addHelperFunction('timeUntil', function ($timestamp) {
    if (! $timestamp) return '';
    return '<span class="timeuntil">'
        . Format::timeUntil($timestamp)
        . '</span>';
});

$this->addHelperFunction('timeUntilPrefix', function ($timestamp) {
    if (! $timestamp) return '';
    return '<span class="timeuntil">'
        . $this->translate(Format::timeUntilPrefix($timestamp))
        . '</span>';
});
