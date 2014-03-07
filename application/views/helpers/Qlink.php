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
        $url->setParams($uriParams);
        return sprintf(
            '<a href="%s"%s>%s</a>',
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
}

