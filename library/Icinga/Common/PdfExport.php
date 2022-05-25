<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Common;

use Icinga\Application\Icinga;
use Icinga\Date\DateFormatter;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Pdfexport\PrintableHtmlDocument;
use Icinga\Util\Environment;
use Icinga\Web\Controller;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Html\ValidHtml;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

trait PdfExport
{
    /** @var string The image to show in a pdf exports page header */
    private $pdfHeaderImage = 'img/icinga-logo-big-dark.png';

    /**
     * Export the requested action to PDF and send it
     *
     * @return never
     * @throws ConfigurationError If the pdfexport module is not available
     */
    protected function sendAsPdf()
    {
        if (! Icinga::app()->getModuleManager()->has('pdfexport')) {
            throw new ConfigurationError('The pdfexport module is required for exports to PDF');
        }

        putenv('ICINGAWEB_EXPORT_FORMAT=pdf');
        Environment::raiseMemoryLimit('512M');
        Environment::raiseExecutionTime(300);

        $time = DateFormatter::formatDateTime(time());
        $iconPath = is_readable($this->pdfHeaderImage)
            ? $this->pdfHeaderImage
            : Icinga::app()->getBootstrapDirectory() . '/' . $this->pdfHeaderImage;
        $encodedIcon = is_readable($iconPath) ? base64_encode(file_get_contents($iconPath)) : null;
        $html = $this instanceof CompatController && ! empty($this->content)
            ? $this->content
            : $this->renderControllerAction();

        $doc = (new PrintableHtmlDocument())
            ->setTitle($this->view->title)
            ->setHeader(Html::wantHtml([
                Html::tag('span', ['class' => 'title']),
                $encodedIcon
                    ? Html::tag('img', ['height' => 13, 'src' => 'data:image/png;base64,' . $encodedIcon])
                    : null,
                Html::tag('time', null, $time)
            ]))
            ->setFooter(Html::wantHtml([
                Html::tag('span', null, [
                    t('Page') . ' ',
                    Html::tag('span', ['class' => 'pageNumber']),
                    ' / ',
                    Html::tag('span', ['class' => 'totalPages'])
                ]),
                Html::tag('p', null, rawurldecode(Url::fromRequest()->setParams($this->params)))
            ]))
            ->addHtml($html);

        if (($moduleName = $this->getRequest()->getModuleName()) !== 'default') {
            $doc->getAttributes()->add('class', 'icinga-module module-' . $moduleName);
        }

        \Icinga\Module\Pdfexport\ProvidedHook\Pdfexport::first()->streamPdfFromHtml($doc, sprintf(
            '%s-%s',
            $this->view->title ?: $this->getRequest()->getActionName(),
            $time
        ));
    }

    /**
     * Render the requested action
     *
     * @return ValidHtml
     */
    protected function renderControllerAction()
    {
        /** @var Controller $this */
        $this->view->compact = true;

        $viewRenderer = $this->getHelper('viewRenderer');
        $viewRenderer->postDispatch();

        $layoutHelper = $this->getHelper('layout');
        $oldLayout = $layoutHelper->getLayout();
        $layout = $layoutHelper->setLayout('inline');

        $layout->content = $this->getResponse();
        $html = $layout->render();

        // Restore previous layout and reset content, to properly show errors
        $this->getResponse()->clearBody($viewRenderer->getResponseSegment());
        $layoutHelper->setLayout($oldLayout);

        return HtmlString::create($html);
    }
}
