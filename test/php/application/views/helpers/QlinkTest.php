<?php


require_once('Zend/View/Helper/Abstract.php');
require_once('Zend/View.php');

require_once('../../application/views/helpers/Qlink.php');


/**
*
* Test class for Zend_View_Helper_Qlink
* Created Thu, 24 Jan 2013 12:56:08 +0000
*
**/
class Zend_View_Helper_QlinkTest extends \PHPUnit_Framework_TestCase
{
    public function testQlink()
    {
        $this->markTestIncomplete('testQlink is not implemented yet');
    }

    /*
     * TODO: Url handling has benn moved to `library\Icinga\Web\Url`. Replace following tests.
     */
//    public function testURLPathParameter()
//    {
//        $view = new Zend_View();
//
//        $helper = new Zend_View_Helper_Qlink();
//        $helper->setView($view);
//        $pathTpl = "/path/%s/to/%s";
//        $this->assertEquals(
//            "/path/param1/to/param2",
//            $helper->getFormattedURL($pathTpl,array('param1','param2'))
//        );
//    }
//
//    public function testUrlGETParameter()
//    {
//        $view = new Zend_View();
//        $helper = new Zend_View_Helper_Qlink();
//        $helper->setView($view);
//        $pathTpl = 'path';
//        $this->assertEquals(
//            '/path?param1=value1&amp;param2=value2',
//            $helper->getFormattedURL($pathTpl,array('param1'=>'value1','param2'=>'value2'))
//        );
//    }
//
//    public function testMixedParameters()
//    {
//        $view = new Zend_View();
//        $helper = new Zend_View_Helper_Qlink();
//        $helper->setView($view);
//        $pathTpl = 'path/%s/to/%s';
//        $this->assertEquals(
//            '/path/path1/to/path2?param1=value1&amp;param2=value2',
//            $helper->getFormattedURL($pathTpl,array(
//                'path1','path2',
//                'param1'=>'value1',
//                'param2'=>'value2'))
//        );
//    }
//
//    // TODO: Test error case
//    public function testWrongUrl() {
//
//    }

}
