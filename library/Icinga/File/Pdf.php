<?php

namespace Icinga\File;

use \DOMPDF;
use \DOMDocument;
use \DOMXPath;

require_once 'vendor/dompdf/dompdf_config.inc.php';

spl_autoload_register("DOMPDF_autoload");

class Pdf extends DOMPDF
{
    /**
     * The amount of table rows that fit on one page before a page-break is inserted.
     *
     * @var int
     */
    public $rowsPerPage = 10;

    /**
     * If occuring tables should be split up into smaller tables to avoid errors in the document layout.
     *
     * @var bool
     */
    public $paginateTable = true;

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
        if ($this->paginateTable === true) {
            $html = $this->paginateHtmlTables($html);
        }
        $this->load_html($html);
        $this->render();
    }

    /**
     * Split up tables into multiple elements that each contain $rowsPerPage of all original rows
     *
     * NOTE: This is a workaround to fix the buggy page-break on table-rows in dompdf.
     *
     * @param  string   A html-string.
     *
     * @return string   The html string with the paginated rows.
     */
    private function paginateHtmlTables($html)
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        $tables =  $xpath->query('.//table');
        foreach ($tables as $table) {
            $containerType  = null;
            $rows           = $xpath->query('.//tr', $table);
            $rowCnt         = $rows->length;
            $tableCnt       = (Integer)ceil($rowCnt / $this->rowsPerPage);
            if ($rowCnt <= $this->rowsPerPage) {
                continue;
            }
            // remove all rows from the original parent
            foreach ($rows as $row) {
                if (!isset($containerType)) {
                    $containerType = $row->parentNode->nodeName;
                }
                $row->parentNode->removeChild($row);
            }

            // clone table for each additional page and fetch the row containers
            $containers = array();
            $pages = array();

            // insert page-break
            $div = $doc->createElement('div');
            $div->setAttribute('style', 'page-break-before: always;');
            $table->parentNode->insertBefore($div, $table);

            for ($i = 0; $i < $tableCnt; $i++) {
                // clone table
                $currentPage = $table->cloneNode(true);
                $pages[$i] = $currentPage;
                $table->parentNode->insertBefore($currentPage, $table);

                // insert page-break
                if ($i < $tableCnt - 1) {
                    $div = $doc->createElement('div');
                    $div->setAttribute('style', 'page-break-before: always;');
                    $table->parentNode->insertBefore($div, $table);
                }

                // fetch row container
                $container = $xpath->query('.//' . $containerType, $currentPage)->item(0);
                $containers[$i] = $container;
            }

            $i = 0;
            foreach ($rows as $row) {
                $p = (Integer)floor($i / $this->rowsPerPage);
                $containers[$p]->appendChild($row);
                //echo "Inserting row $i into container $p <br />";
                $i++;
            }

            // remove original table
            $table->parentNode->removeChild($table);
        }
        return $doc->saveHTML();
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
        return $css  . "\n"
          . '*, html { font-size: 100%; } ' . "\n"

          . 'form { display: none; }' . "\n"

          // Insert page breaks
          . 'div.pdf-page { page-break-before: always; } ' . "\n"

          // Don't show any link outline
          . 'a { outline: 0; }' . "\n"

          // Fix badge positioning
          . 'span.badge { float: right; max-width: 5px; }'

          // prevent table rows from growing too big on page breaks
          . 'tr { max-height: 30px; height: 30px; } ' . "\n"

          // Hide buttons
          . '*.button { display: none; }' . "\n"
          . '*.btn-group { display: none; }' . "\n"
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
