<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Zend_Controller_Action_Exception;
use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;

class Doc_IcingawebController extends DocController
{
    /**
     * Get the path to Icinga Web 2's documentation
     *
     * @return  string
     *
     * @throws  Zend_Controller_Action_Exception    If Icinga Web 2's documentation is not available
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
        throw new Zend_Controller_Action_Exception(
            $this->translate('Documentation for Icinga Web 2 is not available'),
            404
        );
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
     * @throws Zend_Controller_Action_Exception If the required parameter 'chapterId' is missing
     */
    public function chapterAction()
    {
        $chapter = $this->getParam('chapter');
        if ($chapter === null) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Missing parameter %s'), 'chapter'),
                404
            );
        }
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
