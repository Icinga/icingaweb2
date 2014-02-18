<?php
require_once '/vagrant/library/Icinga/Application/Web.php';
use Icinga\Application\Web;
Web::start('/etc/icingaweb')->dispatch();