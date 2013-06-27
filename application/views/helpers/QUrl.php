<?php

use Icinga\Web\Url;

class Zend_View_Helper_QUrl extends Zend_View_Helper_Abstract
{
    public function qUrl()
    {
        $params = func_get_args();
        $url = array_shift($params);
        if (isset($params[0])) {
            $params = $params[0];
        } else {
            $params = array();
        }
        return Url::create($url, $params);
        $params = array_map('rawurlencode', $params);
        return $this->view->baseUrl(vsprintf($url, $params));
    }
}

