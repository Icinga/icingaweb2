<?php

namespace Tests\Icinga\Web;

require_once "../../library/Icinga/Exception/ProgrammingError.php";
require_once "../../library/Icinga/Web/Notification.php";
require_once "../../library/Icinga/Application/Platform.php";
require_once "../../library/Icinga/Application/Logger.php";

require_once "Zend/Session/Namespace.php";
require_once "Zend/Config.php";
require_once "Zend/Log.php";
require_once "Zend/Session.php";
require_once "Zend/Log/Writer/Abstract.php";
require_once "Zend/Log/Writer/Stream.php";

use Icinga\Application\Logger;
use Icinga\Web\Notification;

class NotificationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Logger
     */
    private $logger = null;

    /**
     * @var string
     */
    private $loggerPath = null;

    protected function setUp()
    {
        \Zend_Session::$_unitTestEnabled = true;

        $this->loggerPath = "/tmp/icinga2-web-notify-test";
        $this->dropLog();
        $logConfig = new \Zend_Config(array(
            "debug"     => array(
                "enable" => 0,
                "type"=>"mock",
                "target"=>"target3"
            ),
            "type"      => "stream",
            "target"    => $this->loggerPath
        ));

        $this->logger = new Logger($logConfig);

        // $this->notification = Notification::getInstance();
    }

    protected function dropLog()
    {
        if (file_exists($this->loggerPath)) {
            unlink($this->loggerPath);
        }
    }

    public function testAddMessage1()
    {
        $this->markTestSkipped();
        $notify = Notification::getInstance();
        $notify->setCliFlag(true);
        $notify->error('OK1');
        $notify->warning('OK2');
        $notify->info('OK3');
        $this->logger->flushQueue();

        $content = file_get_contents($this->loggerPath);

        $this->assertContains('[error] OK1', $content);
        $this->assertContains('[warning] OK2', $content);
        $this->assertNotContains('[info] OK3', $content);

        $this->dropLog();
    }

    public function testAddMessage2()
    {
        $this->markTestSkipped();
        $notify = Notification::getInstance();
        $notify->setCliFlag(false);

        $notify->success('test123');
        $notify->error('test456');

        $this->assertTrue($notify->hasMessages());

        $messages = $notify->getMessages();

        $this->assertInternalType('array', $messages);

        $this->assertEquals('test123', $messages[0]->message);
        $this->assertEquals('success', $messages[0]->type);

        $this->assertEquals('test456', $messages[1]->message);
        $this->assertEquals('error', $messages[1]->type);
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage "NOT_EXIST_123" is not a valid notification type
     */
    public function testWrongType1()
    {
        $this->markTestSkipped();
        $notify = Notification::getInstance();
        $notify->addMessage('test', 'NOT_EXIST_123');
    }

    public function testSetterAndGetter1()
    {
        $this->markTestSkipped();
        $notify = Notification::getInstance();
        $notify->setCliFlag(true);
        $this->assertTrue($notify->getCliFlag());

        $notify->setCliFlag(false);
        $this->assertFalse($notify->getCliFlag());
    }
}
