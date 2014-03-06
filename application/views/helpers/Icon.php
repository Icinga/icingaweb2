<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Url;

/**
 * Class Zend_View_Helper_Icon
 */
class Zend_View_Helper_Icon extends Zend_View_Helper_Abstract
{
    public function icon($img, $title = null, array $properties = array())
    {
        $attributes = array();
        $has_alt = false;
        $has_class = false;
        foreach ($properties as $key => $val) {
            $attributes[] = sprintf(
                '%s="%s"',
                filter_var($key, FILTER_SANITIZE_URL),
                filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS)
            );
        }
        if (! array_key_exists('alt', $properties)) {
            $attributes[] = 'alt=""';
        }
        if (! array_key_exists('class', $properties)) {
            $attributes[] = 'class="icon"';
        }
        if (! array_key_exists('title', $properties) && $title !== null) {
            $attributes[] = 'title="' . htmlspecialchars($title) . '"';
        }

        return sprintf(
            '<img src="%s"%s />',
            Url::fromPath('img/icons/' . $img),
            !empty($attributes) ? ' ' . implode(' ', $attributes) : ''
        );
    }
}

// @codingStandardsIgnoreStart
