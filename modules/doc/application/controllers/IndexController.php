<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Doc\Controller as DocController;

class Doc_IndexController extends DocController
{
    /**
     * Display the application's documentation
     */
    public function indexAction()
    {
        $this->populateView();
    }
}
// @codingStandardsIgnoreEnd
