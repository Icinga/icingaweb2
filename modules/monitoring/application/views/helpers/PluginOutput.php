<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

use Icinga\Web\Dom\DomNodeIterator;

/**
 * Plugin output renderer
 */
class Zend_View_Helper_PluginOutput extends Zend_View_Helper_Abstract
{
    /**
     * The return value of getPurifier()
     *
     * @var HTMLPurifier
     */
    protected static $purifier;

    /**
     * Patterns to be replaced in plain text plugin output
     *
     * @var array
     */
    protected static $txtPatterns = array(
        '~\\\n~',
        '~\\\t~',
         '~\\\n\\\n~',
        '~(\[|\()OK(\]|\))~',
        '~(\[|\()WARNING(\]|\))~',
        '~(\[|\()CRITICAL(\]|\))~',
        '~(\[|\()UNKNOWN(\]|\))~',
        '~(\[|\()UP(\]|\))~',
        '~(\[|\()DOWN(\]|\))~',
        '~\@{6,}~'
    );

    /**
     * Replacements for $txtPatterns
     *
     * @var array
     */
    protected static $txtReplacements = array(
        "\n",
        "\t",
        "\n",
        '<span class="state-ok">$1OK$2</span>',
        '<span class="state-warning">$1WARNING$2</span>',
        '<span class="state-critical">$1CRITICAL$2</span>',
        '<span class="state-unknown">$1UNKNOWN$2</span>',
        '<span class="state-up">$1UP$2</span>',
        '<span class="state-down">$1DOWN$2</span>',
        '@@@@@@',
    );

    /**
     * Render plugin output
     *
     * @param   string  $output
     * @param   bool    $raw
     *
     * @return  string
     */
    public function pluginOutput($output, $raw = false)
    {
        if (empty($output)) {
            return '';
        }
        $output = preg_replace('~<br[^>]*>~', "\n", $output);
        if (preg_match('~<[^>]*["/\'][^>]*>~', $output)) {
            // HTML
            $output = preg_replace(
                '~<table~',
                '<table style="font-size: 0.75em"',
                $this->getPurifier()->purify($output)
            );
            $isHtml = true;
        } else {
            // Plaintext
            $output = preg_replace(
                self::$txtPatterns,
                self::$txtReplacements,
                $this->view->escape($output)
            );
            $isHtml = false;
        }
        $output = trim($output);
        // Add space after comma where missing, to help browsers to break words in plugin output
        $output = preg_replace('/,(?=[^\s])/', ', ', $output);
        if (! $raw) {
            if ($isHtml) {
                $output = $this->processHtml($output);
                $output = '<div class="plugin-output">' . $output . '</div>';
            } else {
                $output = '<div class="plugin-output preformatted">' . $output . '</div>';
            }
        }
        return $output;
    }

    /**
     * Replace classic Icinga CGI links with Icinga Web 2 links and color state information, if any
     *
     * @param   string  $html
     *
     * @return  string
     */
    protected function processHtml($html)
    {
        $pattern = '/[([](OK|WARNING|CRITICAL|UNKNOWN|UP|DOWN)[)\]]/';
        $doc = new DOMDocument();
        $doc->loadXML('<div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $dom = new RecursiveIteratorIterator(new DomNodeIterator($doc), RecursiveIteratorIterator::SELF_FIRST);
        $nodesToRemove = array();
        foreach ($dom as $node) {
            /** @var \DOMNode $node */
            if ($node->nodeType === XML_TEXT_NODE) {
                $start = 0;
                while (preg_match($pattern, $node->nodeValue, $match, PREG_OFFSET_CAPTURE, $start)) {
                    $offsetLeft = $match[0][1];
                    $matchLength = strlen($match[0][0]);
                    $leftLength = $offsetLeft - $start;
                    if ($leftLength) {
                        $text = new DOMText(substr($node->nodeValue, $start, $leftLength));
                        $node->parentNode->insertBefore($text, $node);
                    }
                    $span = $doc->createElement('span', $match[0][0]);
                    $span->setAttribute('class', 'state-' . strtolower($match[1][0]));
                    $node->parentNode->insertBefore($span, $node);
                    $start = $offsetLeft + $matchLength;
                }
                if ($start) {
                    $nodesToRemove[] = $node;
                }
            } elseif ($node->nodeType === XML_ELEMENT_NODE) {
                /** @var \DOMElement $node */
                if ($node->tagName === 'a'
                    && preg_match('~^/cgi\-bin/status\.cgi\?(.+)$~', $node->getAttribute('href'), $match)
                ) {
                    parse_str($match[1], $params);
                    if (isset($params['host'])) {
                        $node->setAttribute(
                            'href',
                            $this->view->baseUrl('/monitoring/host/show?host=' . urlencode($params['host']))
                        );
                    }
                }
            }
        }
        foreach ($nodesToRemove as $node) {
            /** @var \DOMNode $node */
            $node->parentNode->removeChild($node);
        }

        return substr($doc->saveHTML(), 5, -7);
    }

    /**
     * Initialize and return self::$purifier
     *
     * @return HTMLPurifier
     */
    protected function getPurifier()
    {
        if (self::$purifier === null) {
            require_once 'HTMLPurifier/Bootstrap.php';
            require_once 'HTMLPurifier.php';

            $oldErrorReportingLevel = error_reporting();
            error_reporting($oldErrorReportingLevel & ~ E_DEPRECATED);

            require_once 'HTMLPurifier.autoload.php';

            error_reporting($oldErrorReportingLevel);

            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.EscapeNonASCIICharacters', true);
            $config->set('HTML.Allowed', 'p,br,b,a[href|target],i,table,tr,th[colspan],td[colspan],div,*[class]');
            $config->set('Attr.AllowedFrameTargets', array('_blank'));
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
