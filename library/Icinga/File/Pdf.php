<?php

namespace Icinga\File;

use \DOMPDF;

require_once 'vendor/dompdf/dompdf_config.inc.php';

spl_autoload_register("DOMPDF_autoload");

class Pdf extends DOMPDF
{
    public function __construct() {
        $this->set_paper(DOMPDF_DEFAULT_PAPER_SIZE, "portrait");
        parent::__construct();
    }

    /**
     * @param $body
     * @param $css
     */
    public function renderPage($body, $css)
    {
        $html =
          '<html><head></head>'
            . '<body>'
               . '<style>' . Pdf::prepareCss($css) . '</style>'
               . $body
            . '</body>'
          . '</html>';
        $this->load_html($html);
        $this->render();
    }

    /**
     * Prepare the given css for rendering with DOMPDF, by removing or hiding all incompatible
     * styles
     *
     * @param $css  The css-string
     *
     * @return string   A css-string that is ready to use for DOMPDF
     */
    public static function prepareCss($css)
    {
        $css = preg_replace('/\*:\s*before\s*,\s*/', '', $css);
        $css = preg_replace('/\*\s*:\s*after\s*\{[^\}]*\}/', '', $css);

        // TODO: Move into own .css file that is loaded when requesting a pdf
        return $css . "\n"
          . 'form { display: none; }' . "\n"

          // Don't show any link outline
          . 'a { outline: 0; }' . "\n"

          // Fix badge positioning  TODO: Badge should be at the right border
          . 'span.badge { float: right; max-width: 5px; }'

          // prevent table rows from growing too big on page breaks
          . 'tr { max-height: 30px; height: 30px; } ' . "\n"

          // Hide buttons
          . '*.button { display: none; }' . "\n"
          . 'button > i { display: none; }' . "\n"

          // Hide navigation
          . '*.nav {display: none; }' . "\n"
          . '*.nav > li { display: none; }' . "\n"
          . '*.nav > li > a { display: none; }' . "\n"

          // Hide pagination
          . '*.pagination { display: none; }' . "\n"
          . '*.pagination > li { display: none; }' . "\n"
          . '*.pagination > li > a { display: none; }' . "\n"
          . '*.pagination > li > span { display: none; }' . "\n";
    }
}
