<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Doc\Controller as DocController;

class Doc_IcingawebController extends DocController
{
    public function indexAction()
    {
        $this->populateView();
    }
}
