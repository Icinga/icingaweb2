<?php
// @codeCoverageIgnoreStart

namespace Icinga\File;

use DOMPDF;
use DOMDocument;
use DOMXPath;
use Font_Metrics;
use Icinga\Application\Icinga;
use Icinga\Web\StyleSheet;
use Icinga\Web\Url;
use Icinga\Exception\ProgrammingError;

require_once 'vendor/dompdf/dompdf_config.inc.php';

spl_autoload_register('DOMPDF_autoload');

class Pdf extends DOMPDF
{
    /**
     * The amount of table rows that fit on one page before a page-break is inserted.
     *
     * @var int
     */
    public $rowsPerPage = 10;

    /**
     * Wether tables should only start at new pages.
     *
     * @var bool
     */
    public $tableInitialPageBreak = false;

    /**
     * Whether occurring tables should be split up into smaller tables to avoid
     * errors in the document layout.
     *
     * @var bool
     */
    public $paginateTable = false;

    public function __construct()
    {
        $this->set_paper('A4', 'portrait');
        parent::__construct();
    }

    protected function assertNoHeadersSent()
    {
        if (headers_sent()) {
            throw new ProgrammingError(
                'Could not send pdf-response, content already written to output.'
            );
        }
    }

    public function renderControllerAction($controller)
    {
        $this->assertNoHeadersSent();

        ini_set('memory_limit', '384M');
        ini_set('max_execution_time', 300);

        $request = $controller->getRequest();
        $layout = $controller->getHelper('layout')->setLayout('pdf');
        $controller->render();
        $layout->content = $controller->getResponse();
        $html = $layout->render();

        $imgDir = Url::fromPath('img');
        $html = preg_replace('~src="' . $imgDir . '/~', 'src="' . Icinga::app()->getBootstrapDirecory() . '/img/', $html);
         //echo $html; exit;
        $this->load_html($html);

		/*
		// TODO: We need to find a solution for page footers
		$font = Font_Metrics::get_font("helvetica", "bold");
		$canvas = $this->get_canvas();
		$canvas->page_text(555, 750, "{PAGE_NUM}/{PAGE_COUNT}", $font, 10, array(0,0,0));
		$dompdf->page_script('
		  // $pdf is the variable containing a reference to the canvas object provided by dompdf
		  $pdf->line(10,730,800,730,array(0,0,0),1);
		');
		*/
        $this->render();
        $this->stream(
            sprintf(
                '%s-%s-%d.pdf',
                $request->getControllerName(),
                $request->getActionName(),
                time()
            )
        );
    }

    /**
     * @param $body
     * @param $css
     */
/*    public function renderPage($body, $css)
    {
        $html =
          '<html><head></head>'
            . '<body>'
               . '<style>' . $css
               // . Pdf::prepareCss($css)
               . '</style>'
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
    }*/

    /**
     * Split up tables into multiple elements that each contain $rowsPerPage of all original rows
     *
     * NOTE: This is a workaround to fix the buggy page-break on table-rows in dompdf.
     *
     * @param DOMDocument   $doc    The html document containing the tables.
     *
     * @return array    All paginated tables from the document.
     */
/*    private function paginateHtmlTables(DOMDocument $doc)
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
*/
    /**
     * Prepare the given css for rendering with DOMPDF, by removing or hiding all incompatible
     * styles
     *
     * @param $css  The css-string
     *
     * @return string   A css-string that is ready to use for DOMPDF
     */

//    public static function prepareCss($css)
//    {
//        $css = preg_replace('/\*:\s*before\s*,\s*/', '', $css);
//        $css = preg_replace('/\*\s*:\s*after\s*\{[^\}]*\}/', '', $css);
//        return $css;
//    }

}
// @codeCoverageIgnoreEnd
