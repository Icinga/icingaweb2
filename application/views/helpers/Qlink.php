<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Class Zend_View_Helper_Qlink
 * @package Application\Views
 */
class Zend_View_Helper_Qlink extends Zend_View_Helper_Abstract
{
    /**
     * @param $htmlContent
     * @param $urlFormat
     * @param array $uriParams
     * @param array $properties
     * @return string
     */
    public function qlink(
        $htmlContent,
        $urlFormat,
        array $uriParams = array(),
        array $properties = array()
    ) {
        $quote = true;
        $attributes = array();
        $baseUrl = null;
        foreach ($properties as $key => $val) {
            if ($key === 'baseUrl') {
                // $baseUrl = filter_var($val, FILTER_SANITIZE_URL) . '/';
                $baseUrl = rawurlencode($val) . '/';
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

        return sprintf(
            '<a href="%s"%s>%s</a>',
            $this->getFormattedUrl($urlFormat, $uriParams, $baseUrl),
            !empty($attributes) ? ' ' . implode(' ', $attributes) : '',
            $quote ? filter_var(
                $htmlContent,
                FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                FILTER_FLAG_NO_ENCODE_QUOTES
            ) : $htmlContent // Alternative: htmlentities($htmlContent)
        );
    }

    /**
     * @param $urlFormat
     * @param $uriParams
     * @param null $baseUrl
     * @return string
     */
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
        if (!empty($args)) {
            $url .= '?' . implode('&amp;', $args);
        }
        return is_null($baseUrl) ? $this->view->baseUrl($url) : $baseUrl . $url;
    }
}
// @codingStandardsIgnoreEnd