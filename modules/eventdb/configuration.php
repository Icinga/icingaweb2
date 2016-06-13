<?php

$section = $this->menuSection('EventDB', array(
    'url'       => 'eventdb/events',
    'priority'  => 200
));

$this->providePermission('example/example-controller', 'Permits access to the example controller');

$this->provideConfigTab('backend', array(
    'title' => $this->translate('Configure how to retrieve monitoring information'),
    'label' => $this->translate('Backend'),
    'url' => 'config'
));
