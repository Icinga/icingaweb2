<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use DOMPDF;
use DOMDocument;
use DOMXPath;
use Font_Metrics;
use Icinga\Application\Icinga;
use Icinga\Web\StyleSheet;
use Icinga\Web\Url;
use Icinga\Exception\ProgrammingError;

require_once 'dompdf/dompdf_config.inc.php';
require_once 'dompdf/include/autoload.inc.php';

class Pdf extends DOMPDF
{
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
        $viewRenderer = $controller->getHelper('viewRenderer');
        $controller->render(
            $viewRenderer->getScriptAction(),
            $viewRenderer->getResponseSegment(),
            $viewRenderer->getNoController()
        );
        $layout = $controller->getHelper('layout')->setLayout('pdf');
        $layout->content = $controller->getResponse();
        $html = $layout->render();
        $imgDir = Url::fromPath('img');
        $html = preg_replace('~src="' . $imgDir . '/~', 'src="' . Icinga::app()->getBootstrapDirectory() . '/img/', $html);
        $this->load_html($html);
        $this->render();
        $request = $controller->getRequest();
        $this->stream(
            sprintf(
                '%s-%s-%d.pdf',
                $request->getControllerName(),
                $request->getActionName(),
                time()
            )
        );
    }
}
