<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Zend_Controller_Action_Exception;
use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;

class Doc_IcingawebController extends DocController
{
    /**
     * View the toc of Icinga Web 2's documentation
     */
    public function tocAction()
    {
        return $this->renderToc(Icinga::app()->getApplicationDir('/../doc'), 'Icinga Web 2', 'doc/icingaweb/chapter');
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
            Icinga::app()->getApplicationDir('/../doc'),
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
        return $this->renderPdf(Icinga::app()->getApplicationDir('/../doc'), 'Icinga Web 2', 'doc/icingaweb/chapter');
    }
}
