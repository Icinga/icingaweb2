<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Controller\ActionController;

class Doc_IndexController extends ActionController
{
    public function indexAction()
    {
        $this->_forward('index', 'view');
    }
}
// @codingStandardsIgnoreEnd
