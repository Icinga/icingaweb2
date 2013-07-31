<?php

namespace Icinga\Protocol\Commandpipe\Transport;

interface Transport
{
    public function setEndpoint(\Zend_Config $config);
    public function send($message);
}