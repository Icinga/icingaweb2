<?php

use Icinga\Web\ModuleActionController;
use Icinga\Web\Url;
use Icinga\Monitoring\Backend;
use Zend_Soap_Server as ZfSoapServer;
use Zend_Soap_AutoDiscover as ZfSoapAutoDiscover;

class Api
{

    /**
     * @return array
     */
    public function problems()
    {
try {
        $backend = Backend::getInstance('localdb');
        $result = $backend->select()->from('status', array(
    'host', 'service', 'host_state', 'service_state', 'service_output'
))->where('problems', 1)->fetchAll();
} catch (Exception $e) {
        return array('error' => $e->getMessage());
}
        return $result; 
    }
}


class Monitoring_SoapController extends ModuleActionController
{
    protected $handlesAuthentication = true;

    public function indexAction()
    {
        $wsdl = new ZfSoapAutoDiscover();
        $wsdl->setClass('Api');
        if (isset($_GET['wsdl'])) {
            $wsdl->handle();
        } else {
            $wsdl->dump('/tmp/test.wsdl');
            $uri = 'http://itenos-devel.tom.local/' . Url::create('monitoring/soap');
            $server = new Zend_Soap_Server('/tmp/test.wsdl');
            $server->setClass('Api');
            $server->handle();
        }
        exit;
    }
}
