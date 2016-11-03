<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

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
        '@@@@@@',
    );

    /**
     * The character &#8203;
     *
     * @var string
     */
    protected $zeroWidthSpace;

    /**
     * The encoded character &#8203;
     *
     * @var string
     */
    protected $zeroWidthSpaceEnt = '&#8203;';

    /**
     * Create a new Zend_View_Helper_PluginOutput
     */
    public function __construct()
    {
        // This is actually not required as the value is constant,
        // but as its (visual) length is 0, it's likely to be mixed up with the empty string.
        $this->zeroWidthSpace = '<span style="visibility:hidden; display:none;">'
            . html_entity_decode($this->zeroWidthSpaceEnt, ENT_NOQUOTES, 'UTF-8')
            . '</span>';
        $this->zeroWidthSpaceEnt = '<span style="visibility:hidden; display:none;">'
            . $this->zeroWidthSpaceEnt
            . '</span>';
    }

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
            $useDom = true;
        } else {
            // Plaintext
            $count = 0;
            $output = preg_replace(
                self::$txtPatterns,
                self::$txtReplacements,
                $this->view->escape($output),
                -1,
                $count
            );
            $isHtml = false;
            $useDom = (bool) $count;
        }

        // Help browsers to break words in plugin output
        $output = trim($output);
        // Add space after comma where missing
        $output = preg_replace('/,(?=[^\s])/', ', ', $output);
        $output = $useDom ? $this->fixLinksAndWrapping($output) : $this->fixWrapping($output, $this->zeroWidthSpaceEnt);

        if (! $raw) {
            if ($isHtml) {
                $output = '<div class="plugin-output">' . $output . '</div>';
            } else {
                $output = '<div class="plugin-output preformatted">' . $output . '</div>';
            }
        }
        return $output;
    }

    /**
     * Replace classic Icinga CGI links with Icinga Web 2 links and
     * add zero width space to make wrapping easier for the user agent
     *
     * @param   string  $html
     *
     * @return  string
     */
    protected function fixLinksAndWrapping($html)
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

        $this->fixWrappingRecursive($dom);

        return substr($dom->saveHTML(), 5, -7);
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
            require_once 'HTMLPurifier.autoload.php';

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

    /**
     * Add zero width space to all text in the DOM to make wrapping easier for the user agent
     *
     * @param   DOMNode $node
     */
    protected function fixWrappingRecursive(DOMNode $node)
    {
        if ($node instanceof DOMText) {
            $node->data = $this->fixWrapping($node->data, $this->zeroWidthSpace);
        } elseif ($node->childNodes !== null) {
            foreach ($node->childNodes as $childNode) {
                $this->fixWrappingRecursive($childNode);
            }
        }
    }

    /**
     * Add zero width space to make wrapping easier for the user agent
     *
     * @param   string  $output
     * @param   string  $zeroWidthSpace
     *
     * @return  string
     */
    protected function fixWrapping($output, $zeroWidthSpace)
    {
        // TODO(el): Disabled until we find a bulletproof implementation
        return $output;
        // Add zero width space after ')', ']', ':', '.', '_' and '-' if not surrounded by whitespaces
        $output = preg_replace('/([^\s])([\\)\\]:._-])([^\s])/', '$1$2' . $zeroWidthSpace . '$3', $output);
        // Add zero width space before '(' and '[' if not surrounded by whitespaces
        return preg_replace('/([^\s])([([])([^\s])/', '$1' . $zeroWidthSpace . '$2$3', $output);
    }
}
