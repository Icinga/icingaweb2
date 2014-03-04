<?php

// TODO: Search for the best and safest quoting
// TODO: Check whether attributes are safe. Script, title in combination with
//       Hover-Tips etc. Eventually create a whitelist for a few options only.
use Icinga\Web\Url;

class Zend_View_Helper_Qlink extends Zend_View_Helper_Abstract
{

    public function qlink($htmlContent, $urlFormat, array $uriParams = array(),
        array $properties = array())
    {
        $quote = true;
        $attributes = array();
        $baseUrl = null;
        foreach ($properties as $key => $val) {
            if ($key === 'baseUrl' ) {
                // $baseUrl = filter_var($val, FILTER_SANITIZE_URL) . '/';
                $baseUrl = $val; //rawurlencode($val) . '/';
                continue;
            }
            if ($key === 'quote') {
                $quote = $val;
                continue;
            }
            if ($key === 'style' && is_array($val)) {
                if (empty($val)) {
                    continue;
                }
                $parts = array();
                foreach ($val as $k => $v) {
                    $parts[] = "$k: $v";
                }
                $attributes[] = 'style="' . implode('; ', $parts) . '"';
                continue;
            }
            $attributes[] = sprintf(
                '%s="%s"',
                //filter_var($key, FILTER_SANITIZE_URL),
                rawurlencode($key),
                //filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS)
                rawurlencode($val)
            );

        }
        if ($urlFormat instanceof Url) {
            $url = $urlFormat;
            $uriParams = $url->getParams() + $uriParams;
        } else {
            $url = Url::fromPath($urlFormat);
        }
        $url->setParams($uriParams)->setBaseUrl($baseUrl);
        return sprintf(
            '<a href="%s"%s>%s</a>',
//            $this->getFormattedUrl($urlFormat, $uriParams, $baseUrl),
            $url,
            !empty($attributes) ? ' ' . implode(' ', $attributes) : '',
            $quote
          ? filter_var(
                $htmlContent,
                FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                FILTER_FLAG_NO_ENCODE_QUOTES
            )
            // Alternativ: htmlentities($htmlContent)
          : $htmlContent
        );
    }
/*
    public function getFormattedUrl($urlFormat, $uriParams, $baseUrl = null)
    {
        $params = $args = array();
        foreach ($uriParams as $name => $value) {
            if (is_int($name)) {
                $params[] = rawurlencode($value);
            } else {
                $args[] = rawurlencode($name) . '=' . rawurlencode($value);
            }
        }
        $url = $urlFormat;
        $url = vsprintf($url, $params);
        if (! empty($args)) {
            $url .= '?' . implode('&amp;', $args);
        }
        return is_null($baseUrl) ? $this->view->baseUrl($url) : $baseUrl.$url;
    }
*/
}

