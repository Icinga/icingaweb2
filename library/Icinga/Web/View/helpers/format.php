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

$this->addHelperFunction('prefixedTimeSince', function ($timestamp, $ucfirst = false) {
    return '<span class="timesince">'
        . Format::prefixedTimeSince($timestamp, $ucfirst)
        . ' </span>';
});

$this->addHelperFunction('timeUntil', function ($timestamp) {
    if (! $timestamp) return '';
    return '<span class="timeuntil">'
        . Format::timeUntil($timestamp)
        . '</span>';
});

$this->addHelperFunction('prefixedTimeUntil', function ($timestamp, $ucfirst = false) {
    if (! $timestamp) return '';
    return '<span class="timeuntil">'
        . Format::prefixedTimeUntil($timestamp, $ucfirst)
        . '</span>';
});
