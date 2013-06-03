<?php

if(!class_exists('Zend_View_Helper_Abstract')) {
    abstract class Zend_View_Helper_Abstract {
        public $basename;

        public function __construct($basename = '') {
            $this->view = $this;
            $this->basename = $basename;
        }

        public function baseUrl($url) {
            return $this->basename.$url;
        }
    };
}

require('../application/views/helpers/Qlink.php');


/**
*
* Test class for Zend_View_Helper_Qlink 
* Created Thu, 24 Jan 2013 12:56:08 +0000 
*
**/
class Zend_View_Helper_QlinkTest extends \PHPUnit_Framework_TestCase
{


    public function testURLPathParameter()
    {
        $helper = new Zend_View_Helper_Qlink();
        $pathTpl = "path/%s/to/%s";
        $this->assertEquals(
            "path/param1/to/param2",
            $helper->getFormattedURL($pathTpl,array('param1','param2'))
        );
    }

    public function testUrlGETParameter()
    {
        $helper = new Zend_View_Helper_Qlink();
        $pathTpl = 'path';
        $this->assertEquals(
            'path?param1=value1&amp;param2=value2',
            $helper->getFormattedURL($pathTpl,array('param1'=>'value1','param2'=>'value2'))
        );
    }

    public function testMixedParameters()
    {
        $helper = new Zend_View_Helper_Qlink();
        $pathTpl = 'path/%s/to/%s';
        $this->assertEquals(
            'path/path1/to/path2?param1=value1&amp;param2=value2',
            $helper->getFormattedURL($pathTpl,array(
                'path1','path2',
                'param1'=>'value1',
                'param2'=>'value2'))
        );
    }

    // TODO: Test error case
    public function testWrongUrl() {

    }

}
