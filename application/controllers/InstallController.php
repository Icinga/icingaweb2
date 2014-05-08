<?php
// @codeCoverageIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

require_once 'Mockery/Loader.php';
$mockeryLoader = new \Mockery\Loader;
$mockeryLoader->register();

use \Mockery;
use Icinga\Web\Controller\ActionController;

class InstallController extends ActionController
{
    /**
     * Whether the controller requires the user to be authenticated
     *
     * The install wizard has its own authentication mechanism.
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    public function indexAction()
    {
        $finished = false;
        $this->view->installer = 'some log info, as html';
        $this->view->wizard = Mockery::mock();
        $this->view->wizard->shouldReceive('isFinished')->andReturn($finished)
            ->shouldReceive('getTitle')->andReturn('Web')
            ->shouldReceive('getPages')->andReturnUsing(function () {
                $a = array(Mockery::mock(array('getTitle' => 'childTest', 'getChildPages' => array(
                    Mockery::mock(array('getTitle' => 'child1')),
                    Mockery::mock(array('getTitle' => 'child2'))
                ), 'isActiveChild' => false))); for ($i=0;$i<10;$i++) { $a[] = Mockery::mock(array('getTitle' => 'title'.$i, 'getChildPages' => array())); } return $a;
            })
            ->shouldReceive('isActivePage')->andReturnUsing(function ($p) { return $p->getTitle() == 'title4'; })
            ->shouldReceive('isCompletedPage')->andReturnUsing(function ($p) { return $p->getTitle() < 'title4'; })
            ->shouldReceive('getActivePage')->andReturnUsing(function () {
                return Mockery::mock(array('getTitle' => 'title4', '__toString' => 'teh form elements'));
            });
    }
}

// @codeCoverageIgnoreEnd
