<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Controllers;

use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;

class IcingawebController extends DocController
{
    /**
     * Get the path to Icinga Web 2's documentation
     *
     * @return  string
     *
     * @throws  \Icinga\Exception\Http\HttpNotFoundException If Icinga Web 2's documentation is not available
     */
    protected function getPath()
    {
        $path = Icinga::app()->getBaseDir('doc');
        if (is_dir($path)) {
            return $path;
        }
        if (($path = $this->Config()->get('documentation', 'icingaweb2')) !== null) {
            if (is_dir($path)) {
                return $path;
            }
        }
        $this->httpNotFound($this->translate('Documentation for Icinga Web 2 is not available'));
    }

    /**
     * View the toc of Icinga Web 2's documentation
     */
    public function tocAction()
    {
        $this->renderToc($this->getPath(), 'Icinga Web 2', 'doc/icingaweb/chapter');
    }

    /**
     * View a chapter of Icinga Web 2's documentation
     *
     * @throws \Icinga\Exception\MissingParameterException If the required parameter 'chapter' is missing
     */
    public function chapterAction()
    {
        $chapter = $this->params->getRequired('chapter');
        $this->renderChapter(
            $this->getPath(),
            $chapter,
            'doc/icingaweb/chapter'
        );
    }

    /**
     * View Icinga Web 2's documentation as PDF
     */
    public function pdfAction()
    {
        $this->renderPdf($this->getPath(), 'Icinga Web 2', 'doc/icingaweb/chapter');
    }
}
