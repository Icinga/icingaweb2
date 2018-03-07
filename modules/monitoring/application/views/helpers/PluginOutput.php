<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

use Icinga\Web\Dom\DomNodeIterator;
use Icinga\Module\Monitoring\Web\Helper\PluginOutputPurifier;

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
     * Patterns to be replaced in html plugin output
     *
     * @var array
     */
    protected static $htmlPatterns = array(
        '~\\\n~',
        '~\\\t~',
        '~\\\n\\\n~',
        '~<table~'
    );

    /**
     * Replacements for $htmlPatterns
     *
     * @var array
     */
    protected static $htmlReplacements = array(
        "\n",
        "\t",
        "\n",
        '<table style="font-size: 0.75em"'
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
                self::$htmlPatterns,
                self::$htmlReplacements,
                PluginOutputPurifier::process($output)
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
                    // if there is text before the match
                    if ($leftLength) {
                        // create node for leading text
                        $text = new DOMText(substr($node->nodeValue, $start, $leftLength));
                        $node->parentNode->insertBefore($text, $node);
                    }
                    // create the new element for the match
                    $span = $doc->createElement('span', $match[0][0]);
                    $span->setAttribute('class', 'state-' . strtolower($match[1][0]));
                    $node->parentNode->insertBefore($span, $node);

                    // start for next match
                    $start = $offsetLeft + $matchLength;
                }
                if ($start) {
                    // is there text left?
                    if (strlen($node->nodeValue) > $start) {
                        // create node for trailing text
                        $text = new DOMText(substr($node->nodeValue, $start));
                        $node->parentNode->insertBefore($text, $node);
                    }
                    // delete the old node later
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
}
