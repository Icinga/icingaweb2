<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use Dompdf\Autoloader;
use Dompdf\Dompdf;
use Dompdf\Options;
use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Url;

call_user_func(function () {
    /**
     * @package dompdf
     * @link    http://dompdf.github.com/
     * @author  Benj Carson <benjcarson@digitaljunkies.ca>
     * @author  Fabien Ménager <fabien.menager@gmail.com>
     * @author  Alexander A. Klimov <alexander.klimov@icinga.com>
     * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
     */

    $baseDir = Icinga::app()->getBaseDir('library/vendor/dompdf');

    require_once "$baseDir/lib/html5lib/Parser.php";
    require_once "$baseDir/lib/php-font-lib/src/FontLib/Autoloader.php";
    require_once "$baseDir/lib/php-svg-lib/src/autoload.php";
    require_once "$baseDir/src/Autoloader.php";

    Autoloader::register();
});

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
