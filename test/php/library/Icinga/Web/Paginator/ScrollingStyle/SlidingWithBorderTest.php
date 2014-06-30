<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web\Paginator\ScrollingStyle;

require_once realpath(ICINGA_LIBDIR . '/Icinga/Web/Paginator/ScrollingStyle/SlidingWithBorder.php');

use \Mockery;
use \Zend_Config;
use \Zend_Paginator;
use Icinga\Test\BaseTestCase;
use Icinga\Protocol\Statusdat\Reader;
use Icinga\Web\Paginator\Adapter\QueryAdapter;
use Icinga\Module\Monitoring\Backend;

class SlidingwithborderTest extends BaseTestCase
{
    private $cacheDir;

    private $backendConfig;

    private $resourceConfig;

    public function setUp()
    {
        parent::setUp();
        $this->cacheDir = '/tmp'. Reader::STATUSDAT_DEFAULT_CACHE_PATH;

        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir);
        }

        $statusdatFile = BaseTestCase::$testDir . '/res/status/icinga.status.dat';
        $cacheFile = BaseTestCase::$testDir . '/res/status/icinga.objects.cache';

        $this->backendConfig = new Zend_Config(
            array(
                'type' => 'statusdat'
            )
        );
        $this->resourceConfig = new Zend_Config(
            array(
                'status_file'   => $statusdatFile,
                'object_file'   => $cacheFile,
                'type'          => 'statusdat'
            )
        );
    }

    public function testGetPages1()
    {
        $backend = new Backend($this->backendConfig, $this->resourceConfig);
        $adapter = new QueryAdapter($backend->select()->from('status'));

        $this->assertEquals(30, $adapter->count());

        $scrollingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();
        $paginator = new Zend_Paginator($adapter);

        $pages = $scrollingStyle->getPages($paginator);
        $this->assertInternalType('array', $pages);
        $this->assertCount(3, $pages);
    }

    public function testGetPages2()
    {
        $scrollingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();
        $paginator = new Zend_Paginator($this->getPaginatorAdapter());

        $pages = $scrollingStyle->getPages($paginator);
        $this->assertInternalType('array', $pages);
        $this->assertCount(13, $pages);
        $this->assertEquals('...', $pages[11]);
    }

    public function testGetPages3()
    {
        $scrollingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();
        $paginator = new Zend_Paginator($this->getPaginatorAdapter());
        $paginator->setCurrentPageNumber(9);

        $pages = $scrollingStyle->getPages($paginator);
        $this->assertInternalType('array', $pages);
        $this->assertCount(16, $pages);
        $this->assertEquals('...', $pages[3]);
        $this->assertEquals('...', $pages[14]);
    }

    protected function getPaginatorAdapter()
    {
        return Mockery::mock('\Zend_Paginator_Adapter_Interface')->shouldReceive('count')->andReturn(1000)->getMock();
    }
}
