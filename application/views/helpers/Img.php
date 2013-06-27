<?php

// TODO: Search for the best and safest quoting
// TODO: Check whether attributes are safe. Script, title in combination with
//       Hover-Tips etc. Eventually create a whitelist for a few options only.
class Zend_View_Helper_Img extends Zend_View_Helper_Abstract
{
    public function img($url, array $properties = array())
    {
        $attributes = array();
        $has_alt = false;
        foreach ($properties as $key => $val) {
            if ($key === 'alt') $has_alt = true;
            $attributes[] = sprintf(
                '%s="%s"',
                filter_var($key, FILTER_SANITIZE_URL),
                filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS)
            );
        }
        if (! $has_alt) $attributes[] = 'alt=""';

        return sprintf(
            '<img src="%s"%s />',
            $this->view->baseUrl($url),
            !empty($attributes) ? ' ' . implode(' ', $attributes) : ''
        );
    }
}

