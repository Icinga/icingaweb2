<?php

namespace Tests\Icinga\Web\ActionController;
use Icinga\Web\ActionController as Action;

require_once('Zend/Controller/Action.php');
require_once('../library/Icinga/Web/ActionController.php');

/**
 * This is not a nice hack, but doesn't affect the behaviour of
 * the tested methods, allowing us to avoid bootstrapping
 * the request/response System for every test
 *
 * Class ActionTestWrap
 * @package Tests\Icinga\Mvc\Controller
 */
class ActionTestWrap extends Action {
    private $args;
    public function __construct(\Zend_Controller_Request_Abstract $request = null,
                                \Zend_Controller_Response_Abstract $response = null, array $invokeArgs = array())
    {}

    public function setArguments($args) {
        $this->args = $args;
    }

    protected function _getParam($paramName, $default = null) {
        if(isset($this->args[$paramName]))
            return $this->args[$paramName];
        return $default;
    }
}

class ActionTest extends \PHPUnit_Framework_TestCase
{
    public function testSeedGeneration()
    {
        $action = new ActionTestWrap();
        list($seed1,$token1) = $action->getSeedTokenPair(600,"test");
        list($seed2,$token2) = $action->getSeedTokenPair(600,"test");
        list($seed3,$token3) = $action->getSeedTokenPair(600,"test");
        $this->assertTrue($seed1 != $seed2 && $seed2 != $seed3 && $seed1 != $seed3);
        $this->assertTrue($token1 != $token2 && $token2 != $token3 && $token1 != $token3);
    }

    public function testSeedValidation()
    {
        $action = new ActionTestWrap();
        list($seed,$token) = $action->getSeedTokenPair(600,"test");
        $action->setArguments(array(
            "seed" => $seed,
            "token" => $token
        ));
        $this->assertTrue($action->hasValidToken(600,"test"));
        $this->assertFalse($action->hasValidToken(600,"test2"));
        $action->setArguments(array(
            "seed" => $seed."ds",
            "token" => $token
        ));
        $this->assertFalse($action->hasValidToken(600,"test"));
        $action->setArguments(array(
            "seed" => $seed,
            "token" => $token."afs"
        ));
        $this->assertFalse($action->hasValidToken(600,"test"));
    }

    public function testMaxAge()
    {
        $action = new ActionTestWrap();
        list($seed,$token) = $action->getSeedTokenPair(1,"test");
        $action->setArguments(array(
            "seed" => $seed,
            "token" => $token
        ));
        $this->assertTrue($action->hasValidToken(1,"test"));
        sleep(1);
        $this->assertFalse($action->hasValidToken(1,"test"));
    }
}
