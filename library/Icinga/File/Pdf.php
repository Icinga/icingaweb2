<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use Dompdf\Dompdf;
use Dompdf\Options;
use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Url;

require_once 'dompdf/autoload.inc.php';

class Pdf
{
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
        $html = preg_replace(
            '~src="' . $imgDir . '/~',
            'src="' . Icinga::app()->getBootstrapDirectory() . '/img/',
            $html
        );
        $options = new Options();
        $options->set('defaultPaperSize', 'A4');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->render();
        $request = $controller->getRequest();
        $dompdf->stream(
            sprintf(
                '%s-%s-%d',
                $request->getControllerName(),
                $request->getActionName(),
                time()
            )
        );
    }
}
