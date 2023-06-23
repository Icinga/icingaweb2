<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use Dompdf\Dompdf;
use Dompdf\Options;
use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;
use Icinga\Util\Environment;
use Icinga\Web\Hook;
use Icinga\Web\Url;

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

        Environment::raiseMemoryLimit('512M');
        Environment::raiseExecutionTime(300);

        $viewRenderer = $controller->getHelper('viewRenderer');
        $viewRenderer->postDispatch();

        $layoutHelper = $controller->getHelper('layout');
        $oldLayout = $layoutHelper->getLayout();
        $layout = $layoutHelper->setLayout('pdf');

        $layout->content = $controller->getResponse();
        $html = $layout->render();

        // Restore previous layout and reset content, to properly show errors
        $controller->getResponse()->clearBody($viewRenderer->getResponseSegment());
        $layoutHelper->setLayout($oldLayout);

        $imgDir = Url::fromPath('img');
        $html = preg_replace(
            '~src="' . $imgDir . '/~',
            'src="' . Icinga::app()->getBootstrapDirectory() . '/img/',
            $html
        );

        $request = $controller->getRequest();

        if (Hook::has('Pdfexport')) {
            $pdfexport = Hook::first('Pdfexport');
            $pdfexport->streamPdfFromHtml($html, sprintf(
                '%s-%s-%d',
                $request->getControllerName(),
                $request->getActionName(),
                time()
            ));

            return;
        }

        $options = new Options();
        $options->set('defaultPaperSize', 'A4');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->render();
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
