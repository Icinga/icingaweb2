<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

class Zend_View_Helper_PluginOutput extends Zend_View_Helper_Abstract
{
    protected static $purifier;

    public function pluginOutput($output)
    {
        if (empty($output)) {
            return '';
        }
        $output = preg_replace('~<br[^>]+>~', "\n", $output);
        if (preg_match('~<\w+[^>]*>~', $output)) {
            // HTML
            $output = preg_replace('~<table~', '<table style="font-size: 0.75em"',
                $this->getPurifier()->purify($output)
            );
        } elseif (preg_match('~\\\n~', $output)) {
            // Plaintext
            $output = '<pre style="font-family: monospace; font-size: 1em;'
                    . ' width: 100%; overflow: auto; white-space: pre-wrap;">'
               . preg_replace(
              '~\\\n~', "\n", preg_replace(
                '~\\\n\\\n~', "\n",
                preg_replace('~\[OK\]~', '<span class="ok">[OK]</span>',
                 preg_replace('~\[WARNING\]~', '<span class="warning">[WARNING]</span>',
                  preg_replace('~\[CRITICAL\]~', '<span class="error">[CRITICAL]</span>',
                   preg_replace('~\@{6,}~', '@@@@@@',
                     $this->view->escape($output)
                ))))
              )
            ) . '</pre>';
        } else {
            $output = '<pre style="font-family: monospace; font-size: 1em;'
                    . ' width: 100%; overflow: auto; white-space: pre-wrap;">'
               . preg_replace('~\@{6,}~', '@@@@@@',
                $this->view->escape($output)
            ) . '</pre>';
        }
        $output = $this->fixLinks($output);
        return $output;
    }

    protected function fixLinks($html)
    {

        $ret = array();
        $dom = new DOMDocument;
        $dom->loadXML('<div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $dom->preserveWhiteSpace = false;
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $tag)
        {
            $href = $tag->getAttribute('href');
            if (preg_match('~^/cgi\-bin/status\.cgi\?(.+)$~', $href, $m)) {
                parse_str($m[1], $params);
                if (isset($params['host'])) {
                    $tag->setAttribute('href', $this->view->baseUrl(
                        '/monitoring/detail/show?host=' . urlencode($params['host']
                    )));
                }
            } else {
                // ignoring
            }
            //$ret[$tag->getAttribute('href')] = $tag->childNodes->item(0)->nodeValue;
        }
        return substr($dom->saveHTML(), 5, -7);
    }

    protected function getPurifier()
    {
        if (self::$purifier === null) {
            require_once 'IcingaVendor/htmlpurifier-4.6.0-lite/library/HTMLPurifier.auto.php';
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.EscapeNonASCIICharacters', true);
            $config->set('HTML.Allowed', 'p,br,b,a[href],i,table,tr,td[colspan],div[class]');
            // This avoids permission problems:
            // $config->set('Core.DefinitionCache', null);
            $config->set('Cache.DefinitionImpl', null);
            // TODO: Use a cache directory:
            // $config->set('Cache.SerializerPath', '/var/spool/whatever');

            // $config->set('URI.Base', 'http://www.example.com');
            // $config->set('URI.MakeAbsolute', true);
            // $config->set('AutoFormat.AutoParagraph', true);
            self::$purifier = new HTMLPurifier($config);
        }
        return self::$purifier;
    }
}
