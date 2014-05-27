<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;

class Doc_IcingawebController extends DocController
{
    /**
     * View toc of Icinga Web 2's documentation
     */
    public function tocAction()
    {
        $this->renderToc(Icinga::app()->getApplicationDir('/../doc'), 'Icinga Web 2');
    }
}
