<?php

use Icinga\Application\EmbeddedWeb;
use Icinga\Web\StyleSheet;

require_once '/vagrant/library/Icinga/Application/EmbeddedWeb.php';
EmbeddedWeb::start('/etc/icingaweb');
Stylesheet::send();
