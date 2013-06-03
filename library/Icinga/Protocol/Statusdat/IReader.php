<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 1/17/13
 * Time: 10:21 AM
 * To change this template use File | Settings | File Templates.
 */
namespace Icinga\Protocol\Statusdat;
interface IReader
{

    public function getState();
    public function getObjects();

    public function getObjectByName($type,$name);

}
