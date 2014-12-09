<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
        if (($path = $this->Config()->get('documentation', 'icingaweb2')) !== null) {
            if (is_dir($path)) {
                return $path;
            }
        }
        $path = Icinga::app()->getBaseDir('doc');
        if (is_dir($path)) {
            return $path;
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
        return $this->renderToc($this->getPath(), 'Icinga Web 2', 'doc/icingaweb/chapter');
    }

    /**
     * View a chapter of Icinga Web 2's documentation
     *
     * @throws Zend_Controller_Action_Exception If the required parameter 'chapterId' is missing
     */
    public function chapterAction()
    {
        $chapterId = $this->getParam('chapterId');
        if ($chapterId === null) {
            throw new Zend_Controller_Action_Exception(
                $this->translate('Missing parameter \'chapterId\''),
                404
            );
        }
        return $this->renderChapter(
            $this->getPath(),
            $chapterId,
            'doc/icingaweb/toc',
            'doc/icingaweb/chapter'
        );
    }

    /**
     * View Icinga Web 2's documentation as PDF
     */
    public function pdfAction()
    {
        return $this->renderPdf($this->getPath(), 'Icinga Web 2', 'doc/icingaweb/chapter');
    }
}
