<?php

class Zend_View_Helper_TimeSince extends Zend_View_Helper_Abstract
{
    public function timeSince($timestamp)
    {

        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        if (!is_numeric($timestamp)) {
            return '?';
        }

        if (! $timestamp) return '-';
        $duration = time() - $timestamp;
        $prefix = '';
        if ($duration < 0) {
            $prefix = '-';
            $duration *= -1;
        }
        if ($duration > 3600 * 24 * 3) {
            if (date('Y') === date('Y', $timestamp)) {
                return date('d.m.', $timestamp);
            }
            return date('m.Y', $timestamp);
        }
        return $prefix . $this->showHourMin($duration);
    }

    public static function showHourMin($sec)
    {
        $min = floor($sec / 60);
        if ($min < 60) {
            return $min . 'm ' . ($sec % 60) . 's';
        }
        $hour = floor($min / 60);
        if ($hour < 24) {
            return date('H:i', time() - $sec);
        }
        return floor($hour / 24) . 'd ' . ($hour % 24) . 'h';
    }
}
