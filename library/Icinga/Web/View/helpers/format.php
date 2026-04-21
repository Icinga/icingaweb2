<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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

$this->addHelperFunction('timeAgo', function ($time, $timeOnly = false, $requireTime = false) {
    if (! $time) {
        return '';
    }
    return sprintf(
        '<time class="time-ago" data-relative-time="ago" title="%s" datetime="%1$s">%s</time>',
        DateFormatter::formatDateTime($time),
        DateFormatter::timeAgo($time, $timeOnly, $requireTime)
    );
});

$this->addHelperFunction('timeSince', function ($time, $timeOnly = false, $requireTime = false) {
    if (! $time) {
        return '';
    }
    return sprintf(
        '<time class="time-since" data-relative-time="since" title="%s" datetime="%1$s">%s</time>',
        DateFormatter::formatDateTime($time),
        DateFormatter::timeSince($time, $timeOnly, $requireTime)
    );
});

$this->addHelperFunction('timeUntil', function ($time, $timeOnly = false, $requireTime = false) {
    if (! $time) {
        return '';
    }
    return sprintf(
        '<time class="time-until" data-relative-time="until" title="%s" datetime="%1$s">%s</time>',
        DateFormatter::formatDateTime($time),
        DateFormatter::timeUntil($time, $timeOnly, $requireTime)
    );
});
