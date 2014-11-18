<?php

use Icinga\Application\Icinga;
use Icinga\Web\Controller;
use Icinga\Web\Widget;

class Doc_StyleController extends Controller
{
    public function fontAction()
    {
        $this->view->tabs = Widget::create('tabs')->add(
            'fonts',
            array(
                'title' => $this->translate('Icons'),
                'url' => 'doc/style/font'
            )
        )->activate('fonts');
        $confFile = Icinga::app()->getApplicationDir('fonts/fontanello-ifont/config.json');
        $this->view->font = json_decode(file_get_contents($confFile));
    }
}
