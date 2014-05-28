<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Zend_Controller_Action_Exception;
use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;

class Doc_IcingawebController extends DocController
{
    /**
     * View toc of Icinga Web 2's documentation
     */
    public function tocAction()
    {
        $this->populateToc(Icinga::app()->getApplicationDir('/../doc'), 'Icinga Web 2');
    }

    /**
     * View a chapter of Icinga Web 2's documentation
     *
     * @throws Zend_Controller_Action_Exception
     */
    public function chapterAction()
    {
        $chapterName = $this->getParam('chapterName');
        if ($chapterName === null) {
            throw new Zend_Controller_Action_Exception('Missing parameter "chapterName"', 404);
        }
        $this->renderChapter($chapterName, Icinga::app()->getApplicationDir('/../doc'));
    }
}
