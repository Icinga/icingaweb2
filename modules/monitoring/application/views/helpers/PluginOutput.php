<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

class Zend_View_Helper_PluginOutput extends Zend_View_Helper_Abstract
{
    protected static $purifier;

    protected static $txtPatterns = array(
        '~\\\n~',
        '~\\\t~',
         '~\\\n\\\n~',
        '~(\[|\()OK(\]|\))~',
        '~(\[|\()WARNING(\]|\))~',
        '~(\[|\()CRITICAL(\]|\))~',
        '~(\[|\()UNKNOWN(\]|\))~',
        '~\@{6,}~'
    );

    protected static $txtReplacements = array(
        "\n",
        "\t",
        "\n",
        '<span class="state ok">$1OK$2</span>',
        '<span class="state warning">$1WARNING$2</span>',
        '<span class="state critical">$1CRITICAL$2</span>',
        '<span class="state error">$1UNKNOWN$2</span>',
        '@@@@@@',
    );

    public function pluginOutput($output)
    {
        if (empty($output)) {
            return '';
        }
        $output = preg_replace('~<br[^>]+>~', "\n", $output);
        if (preg_match('~<\w+[^>^\\\]{,60}>~', $output)) {
            // HTML
            $output = preg_replace('~<table~', '<table style="font-size: 0.75em"',
                $this->getPurifier()->purify($output)
            );
        } else {
            // Plaintext
            $output = '<pre class="pluginoutput">' . preg_replace(
                self::$txtPatterns,
                self::$txtReplacements,
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
                        '/monitoring/host/show?host=' . urlencode($params['host']
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
            require_once 'HTMLPurifier/Bootstrap.php';
            require_once 'HTMLPurifier/HTMLPurifier.php';
            require_once 'HTMLPurifier/HTMLPurifier.autoload.php';

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
