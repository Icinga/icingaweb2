<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Icinga;
use Icinga\Application\Version;
use Icinga\Util\LessParser;
use Icinga\Web\Controller;

class AboutController extends Controller
{
    public function indexAction()
    {
        $this->view->version = Version::get();
        $this->view->libraries = Icinga::app()->getLibraries();
        $this->view->modules = Icinga::app()->getModuleManager()->getLoadedModules();
        $this->view->title = $this->translate('About');
        $this->view->tabs = $this->getTabs()->add(
            'about',
            array(
                'label' => $this->translate('About'),
                'title' => $this->translate('About Icinga Web 2'),
                'url'   => 'about'
            )
        )->activate('about');
    }

    public function lessAction()
    {
        $less = <<<LESS
@button-bg-color: black;
@foobar: @button-bg-color;

.button(@bg-color: @foobar) {
  background-color: @bg-color;
}

.foobar() {
  &.foobar {
    .child {
      color: @button-bg-color;
      &:before {
        color: @foobar;
      }
    }
  }
}

.button {
  .button();
  .foobar();
  border-color: var(--foobar, @foobar);
}
LESS;

        $parser = new LessParser();
        echo '<pre>' . $parser->compile($less) . '</pre>';die;

    }
}
