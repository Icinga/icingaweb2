<?php

namespace Tests\Icinga\Web\Paginator\Adapter;

use PHPUnit_Framework_TestCase;
use Zend_Config;
use Icinga\Protocol\Statusdat\Reader;
use Icinga\Web\Paginator\Adapter\QueryAdapter;
use Icinga\Module\Monitoring\Backend;
use Tests\Icinga\Protocol\Statusdat\StatusdatTestLoader;

require_once 'Zend/Paginator/Adapter/Interface.php';

require_once '../../library/Icinga/Web/Paginator/Adapter/QueryAdapter.php';

require_once 'library/Icinga/Protocol/Statusdat/StatusdatTestLoader.php';
StatusdatTestLoader::requireLibrary();

require_once '../../modules/monitoring/library/Monitoring/Backend/Statusdat/Criteria/Order.php';
require_once '../../modules/monitoring/library/Monitoring/Backend/AbstractBackend.php';
require_once '../../modules/monitoring/library/Monitoring/Backend/Statusdat/Query/Query.php';
require_once '../../modules/monitoring/library/Monitoring/Backend/Statusdat/Query/StatusQuery.php';
require_once '../../modules/monitoring/library/Monitoring/Backend/Statusdat/DataView/HostStatusView.php';
require_once '../../modules/monitoring/library/Monitoring/View/AbstractView.php';
require_once '../../modules/monitoring/library/Monitoring/View/StatusView.php';
require_once '../../modules/monitoring/library/Monitoring/Backend.php';

require_once '../../library/Icinga/Protocol/AbstractQuery.php';
require_once '../../library/Icinga/Data/ResourceFactory.php';

class QueryAdapterTest extends PHPUnit_Framework_TestCase
{
    private $cacheDir;

    private $backendConfig;

    private $resourceConfig;

    protected function setUp()
    {
        $this->cacheDir = '/tmp'. Reader::STATUSDAT_DEFAULT_CACHE_PATH;

        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir);
        }

        $statusdatFile  = dirname(__FILE__) . '/../../../../../res/status/icinga.status.dat';
        $cacheFile      = dirname(__FILE__) . '/../../../../../res/status/icinga.objects.cache';

        $this->backendConfig = new Zend_Config(
            array(
                'type' => 'statusdat'
            )
        );
        $this->resourceConfig = new Zend_Config(
            array(
                'status_file'   => $statusdatFile,
                'objects_file'  => $cacheFile,
                'type'          => 'statusdat'
            )
        );
    }

    public function testLimit1()
    {
        $backend = new Backend($this->backendConfig, $this->resourceConfig);
        $query = $backend->select()->from('status');

        $adapter = new QueryAdapter($query);

        $this->assertEquals(30, $adapter->count());

        $data = $adapter->getItems(0, 10);

        $this->assertCount(10, $data);

        $data = $adapter->getItems(10, 20);
        $this->assertCount(10, $data);
    }

    public function testLimit2()
    {
        $backend = new Backend($this->backendConfig, $this->resourceConfig);
        $query = $backend->select()->from('status');

        $adapter = new QueryAdapter($query);
        $this->assertEquals(30, $adapter->count());
    }
}
