<?php

namespace Tests\Icinga\Web\Paginator\Adapter;

use Icinga\Backend\Statusdat;
use Icinga\Protocol\Statusdat\Reader;
use Icinga\Web\Paginator\Adapter\QueryAdapter;

require_once 'Zend/Paginator/Adapter/Interface.php';
require_once 'Zend/Config.php';
require_once 'Zend/Cache.php';

require_once '../../library/Icinga/Web/Paginator/Adapter/QueryAdapter.php';
require_once '../../library/Icinga/Backend/Criteria/Order.php';
require_once '../../library/Icinga/Backend/AbstractBackend.php';
require_once '../../library/Icinga/Backend/Query.php';
require_once '../../library/Icinga/Backend/Statusdat/Query.php';
require_once '../../library/Icinga/Backend/Statusdat.php';
require_once '../../library/Icinga/Backend/MonitoringObjectList.php';
require_once '../../library/Icinga/Backend/Statusdat/HostlistQuery.php';
require_once '../../library/Icinga/Backend/DataView/AbstractAccessorStrategy.php';
require_once '../../library/Icinga/Backend/DataView/ObjectRemappingView.php';
require_once '../../library/Icinga/Backend/Statusdat/DataView/StatusdatHostView.php';
require_once '../../library/Icinga/Protocol/AbstractQuery.php';
require_once '../../library/Icinga/Protocol/Statusdat/IReader.php';
require_once '../../library/Icinga/Protocol/Statusdat/Reader.php';
require_once '../../library/Icinga/Protocol/Statusdat/Query.php';

class QueryAdapterTest extends \PHPUnit_Framework_TestCase
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

    public function testLimit1()
    {
        $backend = new Statusdat($this->config);
        $query = $backend->select()->from('hostlist');

        $paginator = new QueryAdapter($query);

        $this->assertEquals(30, $paginator->count());

        $data = $paginator->getItems(0, 10);

        $this->assertCount(10, $data);

        $data = $paginator->getItems(10, 20);
        $this->assertCount(10, $data);
    }
}