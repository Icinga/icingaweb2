<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 8/5/13
 * Time: 11:58 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Icinga\Web\Widget;


use Icinga\Web\View;

interface Widget {

    public function render(\Zend_View_Abstract $view);
}