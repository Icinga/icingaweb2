<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\View;

use Icinga\Date\DateFormatter;
use Icinga\Util\Format;

$this->addHelperFunction('format', function () {
    return Format::getInstance();
});

$this->addHelperFunction('timeAgo', function ($time, $timeOnly = false) {
    if (! $time) {
        return '';
    }
    return sprintf(
        '<span class="time-ago" title="%s">%s</span>',
        date('Y-m-d H:i:s', $time), // TODO: internationalized format
        DateFormatter::timeAgo($time, $timeOnly)
    );
});

$this->addHelperFunction('timeSince', function ($time, $timeOnly = false) {
    if (! $time) {
        return '';
    }
    return sprintf(
        '<span class="time-since" title="%s">%s</span>',
        date('Y-m-d H:i:s', $time), // TODO: internationalized format
        DateFormatter::timeSince($time, $timeOnly)
    );
});

$this->addHelperFunction('timeUntil', function ($time, $timeOnly = false) {
    if (! $time) {
        return '';
    }
    return sprintf(
        '<span class="time-until" title="%s">%s</span>',
        date('Y-m-d H:i:s', $time), // TODO: internationalized format
        DateFormatter::timeUntil($time, $timeOnly)
    );
});

$this->addHelperFunction('dateTimeRenderer', function ($dateTimeOrTimestamp, $future = false) {
    return DateTimeRenderer::create($dateTimeOrTimestamp, $future);
});
