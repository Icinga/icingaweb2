<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\View;

use Icinga\Date\DateFormatter;
use Icinga\Util\Format;

$this->addHelperFunction('format', function () {
    return Format::getInstance();
});

$this->addHelperFunction('formatDate', function ($date) {
    if (! $date) {
        return '';
    }
    return DateFormatter::formatDate($date);
});

$this->addHelperFunction('formatDateTime', function ($dateTime) {
    if (! $dateTime) {
        return '';
    }
    return DateFormatter::formatDateTime($dateTime);
});

$this->addHelperFunction('formatDuration', function ($seconds) {
    if (! $seconds) {
        return '';
    }
    return DateFormatter::formatDuration($seconds);
});

$this->addHelperFunction('formatTime', function ($time) {
    if (! $time) {
        return '';
    }
    return DateFormatter::formatTime($time);
});

$this->addHelperFunction('timeAgo', function ($time, $timeOnly = false) {
    if (! $time) {
        return '';
    }
    return sprintf(
        '<span class="relative-time time-ago" title="%s">%s</span>',
        DateFormatter::formatDateTime($time),
        DateFormatter::timeAgo($time, $timeOnly)
    );
});

$this->addHelperFunction('timeSince', function ($time, $timeOnly = false) {
    if (! $time) {
        return '';
    }
    return sprintf(
        '<span class="relative-time time-since" title="%s">%s</span>',
        DateFormatter::formatDateTime($time),
        DateFormatter::timeSince($time, $timeOnly)
    );
});

$this->addHelperFunction('timeUntil', function ($time, $timeOnly = false) {
    if (! $time) {
        return '';
    }
    return sprintf(
        '<span class="relative-time time-until" title="%s">%s</span>',
        DateFormatter::formatDateTime($time),
        DateFormatter::timeUntil($time, $timeOnly)
    );
});
