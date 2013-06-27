<?php

class Zend_View_Helper_DetailTabs extends Zend_View_Helper_Abstract
{
    const URL_PARAMS = "urlParams";
    const URL_SUFFIX = "urlSuffix";
    const MODULE = "module";


    public function detailTabs($settings,$params = array())
    {
        $urlParams = array();
        $url_suffix = "";
        $module = "";
        if(isset($params[self::URL_PARAMS]))
            $urlParams = $params[self::URL_PARAMS];
        if(isset($params[self::URL_SUFFIX]))
            $url_suffix = $params[self::URL_SUFFIX];
        if(isset($params[self::MODULE]))
            $module = $params[self::MODULE];


        $tabs = array(
            'host' => $settings->qlink(
                'Host',
                $module.'/detail/show',
                $urlParams + array('active' => 'host')
            ),
        );

        if ($settings->service) {
            $tabs['service'] = $settings->qlink(
                'Service',
                $module.'/detail/show',
                $urlParams
            );
        }

        $tabs['history'] = $settings->qlink(
            'History',
            $module.'/history',
            $urlParams
        );


        $tabs['hostservices'] = $settings->qlink(
            'Services',
            $module.'/hostservices',
            $urlParams
        );


        $html = '<ul class="nav nav-tabs">';

        foreach ($tabs as $name => $tab) {
            $class = $name === $settings->active ? ' class="active"' : '';
            $html .= "<li $class>$tab</li>";
        }
        $html .= "</ul>";


        return $html;
    }

}
