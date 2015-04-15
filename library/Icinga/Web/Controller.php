<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Web\Controller\ModuleActionController;
use Icinga\Web\Widget\SortBox;

/**
 * This is the controller all modules should inherit from
 * We will flip code with the ModuleActionController as soon as a couple
 * of pending feature branches are merged back to the master.
 */
class Controller extends ModuleActionController
{
    /**
     * Create a SortBox widget at the `sortBox' view property
     *
     * @param   array   $columns    An array containing the sort columns, with the
     *                               submit value as the key and the label as the value
     */
    protected function setupSortControl(array $columns)
    {
        $req = $this->getRequest();
        $this->view->sortBox = SortBox::create(
            'sortbox-' . $req->getActionName(),
            $columns
        )->applyRequest($req);
    }
}
