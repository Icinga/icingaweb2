<?php

$client = new SoapClient('http://itenos-devel.tom.local/monitoring/soap?wsdl', array(
  'login' => 'icingaadmin',
  'password' => 'tomtom',
  'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
//  'authentication' => SOAP_AUTHENTICATION_BASIC

));

// print_r($client->__getFunctions());
try {
print_r($client->problems());
} catch (Exception $e) {
    echo $e->getMessage() . "\n\n";
   echo $client->__getLastRequest() . "\n\n";
   echo $client->__getLastResponse() . "\n\n";
}
