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
     * If tables should only start at new pages.
     *
     * @var bool
     */
    public $tableInitialPageBreak = false;

    /**
     * If occurring tables should be split up into smaller tables to avoid errors in the document layout.
     *
     * @var bool
     */
    public $paginateTable = true;

    public function __construct()
    {
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
            $doc = new DOMDocument();
            @$doc->loadHTML($html);
            $this->paginateHtmlTables($doc);
            $html = $doc->saveHtml();
        }
        $this->load_html($html);
        $this->render();
    }

    /**
     * Split up tables into multiple elements that each contain $rowsPerPage of all original rows
     *
     * NOTE: This is a workaround to fix the buggy page-break on table-rows in dompdf.
     *
     * @param DOMDocument   $doc    The html document containing the tables.
     *
     * @return array    All paginated tables from the document.
     */
    private function paginateHtmlTables(DOMDocument $doc)
    {
        $xpath     = new DOMXPath($doc);
        $tables    = $xpath->query('.//table');
        $paginated = array();
        $j         = 0;

        foreach ($tables as $table) {
            $containerType  = null;
            $rows           = $xpath->query('.//tr', $table);
            $rowCnt         = $rows->length;
            $tableCnt       = (Integer)ceil($rowCnt / $this->rowsPerPage);
            $paginated[$j]  = array();
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

            if ($this->tableInitialPageBreak) {
                $this->pageBreak($doc, $table);
            }
            for ($i = 0; $i < $tableCnt; $i++) {
                // clone table
                $currentPage = $table->cloneNode(true);
                $pages[$i] = $currentPage;
                $table->parentNode->insertBefore($currentPage, $table);

                // put it in current paginated table
                $paginated[$j] = $currentPage;

                // insert page-break
                if ($i < $tableCnt - 1) {
                    $this->pageBreak($doc, $table);
                }

                // fetch row container
                $container = $xpath->query('.//' . $containerType, $currentPage)->item(0);
                $containers[$i] = $container;
            }

            $i = 0;
            foreach ($rows as $row) {
                $p = (Integer)floor($i / $this->rowsPerPage);
                $containers[$p]->appendChild($row);
                $i++;
            }

            // remove original table
            $table->parentNode->removeChild($table);
            $j++;
        }
        return $paginated;
    }

    private function pageBreak($doc, $before)
    {
        $div = $doc->createElement('div');
        $div->setAttribute('style', 'page-break-before: always;');
        $before->parentNode->insertBefore($div, $before);
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
        return $css;
    }
}
