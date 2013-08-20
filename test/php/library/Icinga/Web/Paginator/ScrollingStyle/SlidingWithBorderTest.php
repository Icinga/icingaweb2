<?php

namespace Tests\Icinga\Web\Paginator\ScrollingStyle;

use \Icinga\Module\Monitoring\Backend\Statusdat;
use Icinga\Protocol\Statusdat\Reader;
use Icinga\Web\Paginator\Adapter\QueryAdapter;
use Tests\Icinga\Protocol\Statusdat\StatusdatTestLoader;


require_once 'Zend/Paginator/Adapter/Interface.php';
require_once 'Zend/Paginator/ScrollingStyle/Interface.php';
require_once 'Zend/Paginator.php';
require_once 'Zend/Config.php';
require_once 'Zend/Cache.php';

require_once '../../library/Icinga/Web/Paginator/Adapter/QueryAdapter.php';
require_once 'library/Icinga/Protocol/Statusdat/StatusdatTestLoader.php';

StatusdatTestLoader::requireLibrary();

require_once '../../modules/monitoring/library/Monitoring/Backend/Statusdat/Criteria/Order.php';
require_once '../../modules/monitoring/library/Monitoring/Backend/AbstractBackend.php';
require_once '../../modules/monitoring/library/Monitoring/Backend/Statusdat/Query/Query.php';
require_once '../../modules/monitoring/library/Monitoring/Backend/Statusdat.php';
require_once '../../modules/monitoring/library/Monitoring/Backend/Statusdat/Query/StatusQuery.php';
require_once '../../modules/monitoring/library/Monitoring/Backend/Statusdat/DataView/HostStatusView.php';
require_once '../../modules/monitoring/library/Monitoring/View/MonitoringView.php';
require_once '../../modules/monitoring/library/Monitoring/View/StatusView.php';
require_once '../../library/Icinga/Web/Paginator/ScrollingStyle/SlidingWithBorder.php';

class TestPaginatorAdapter implements \Zend_Paginator_Adapter_Interface
{
    private $items = array();

    public function __construct()
    {
        for ($i=0; $i<1000; $i++) {
            $this->items[] = array(
                'a' => mt_rand(0, 100),
                'b' => mt_rand(0, 100)
            );
        }
    }

    /**
     * Returns an collection of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $out = array_slice($this->items, $offset, $itemCountPerPage, true);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->items);
    }


}

/**
*
* Test class for Slidingwithborder 
* Created Wed, 16 Jan 2013 15:15:16 +0000 
*
**/
class SlidingwithborderTest extends \PHPUnit_Framework_TestCase
{
    private $cacheDir;

    private $config;

    protected function setUp()
    {
        $this->cacheDir = '/tmp'. Reader::STATUSDAT_DEFAULT_CACHE_PATH;

        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir);
        }

        $statusdatFile = dirname(__FILE__). '/../../../../../res/status/icinga.status.dat';
        $cacheFile = dirname(__FILE__). '/../../../../../res/status/icinga.objects.cache';

        $this->config = new \Zend_Config(
            array(
                'status_file' => $statusdatFile,
                'objects_file' => $cacheFile
            )
        );
    }

    public function testGetPages1()
    {
        $backend = new Statusdat($this->config);
        $query = $backend->select()->from('status');

        $adapter = new QueryAdapter($query);

        $this->assertEquals(30, $adapter->count());

        $scrolingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();

        $paginator = new \Zend_Paginator($adapter);

        $pages = $scrolingStyle->getPages($paginator);

        $this->assertInternalType('array', $pages);
        $this->assertCount(3, $pages);
    }

    public function testGetPages2()
    {
        $scrolingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();

        $adapter = new TestPaginatorAdapter();

        $paginator = new \Zend_Paginator($adapter);

        $pages = $scrolingStyle->getPages($paginator);

        $this->assertInternalType('array', $pages);

        $this->assertCount(13, $pages);
        $this->assertEquals('...', $pages[11]);
    }

    public function testGetPages3()
    {
        $scrolingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();

        $adapter = new TestPaginatorAdapter();

        $paginator = new \Zend_Paginator($adapter);
        $paginator->setCurrentPageNumber(9);

        $pages = $scrolingStyle->getPages($paginator);

        $this->assertInternalType('array', $pages);

        $this->assertCount(16, $pages);
        $this->assertEquals('...', $pages[3]);
        $this->assertEquals('...', $pages[14]);
    }

}
