<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

use Icinga\Application\Icinga;
use Icinga\Web\Controller;
use Icinga\Web\Widget;

class Doc_StyleController extends Controller
{
    public function guideAction()
    {
        $this->view->tabs = $this->tabs()->activate('guide');
    }

    public function fontAction()
    {
        $this->view->tabs = $this->tabs()->activate('font');
        $confFile = Icinga::app()->getApplicationDir('fonts/fontello-ifont/config.json');
        $this->view->font = json_decode(file_get_contents($confFile));
    }

    protected function tabs()
    {
        return Widget::create('tabs')->add(
            'guide',
            array(
                'title' => $this->translate('Style Guide'),
                'url' => 'doc/style/guide'
            )
        )->add(
            'font',
            array(
                'title' => $this->translate('Icons'),
                'url' => 'doc/style/font'
            )
        );
    }
}
