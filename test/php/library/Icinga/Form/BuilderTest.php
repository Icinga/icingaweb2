<?php

namespace Test\Icinga\Form;

require_once("Zend/Form.php");
require_once("Zend/View.php");
require_once("../../library/Icinga/Form/Builder.php");

use Icinga\Form\Builder as Builder;

class BuilderTestModel
{
    public $username = '';
    public $password = '';
    private $test;

    public function getTest()
    {
        return $this->test;
    }

    public function setTest($test)
    {
        $this->test = $test;
    }
}

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
    *    
    **/
    public function testFormCreation()
    {
        $builder = new Builder(null, array("CSRFProtection" => false));
        $this->assertInstanceOf("Zend_Form", $builder->getForm());
    }

    /**
    *    
    **/
    public function testCSRFProtectionTokenCreation()
    {
        $view = new \Zend_View();
        $builder = new Builder(); // when no token is given, a CRSF field should be added
        $builder->setView($view);
        
        $DOM = new \DOMDocument;
        $DOM->loadHTML($builder);
        $this->assertNotNull($DOM->getElementById(Builder::CSRF_ID));
        
        $builder->disableCSRF();
        $DOM->loadHTML($builder);
        $this->assertNull($DOM->getElementById(Builder::CSRF_ID));
        
    }
    /**
    *   Test whether form methods are passed to the Zend_Form object 
    *   When called in the Builder instance
    *    
    **/
    public function testMethodPassing()
    {
        $DOM = new \DOMDocument;
        $view = new \Zend_View();
        $builder = new Builder(null, array("CSRFProtection" => false));
        $builder->setView($view);
        
        $DOM->loadHTML($builder);
        $this->assertEquals(0, $DOM->getElementsByTagName("input")->length);
        
        $builder->addElement("text", "username");
        $DOM->loadHTML($builder);
        $inputEls = $DOM->getElementsByTagName("input");
        $this->assertEquals(1, $inputEls->length);
        $this->assertEquals("username", $inputEls->item(0)->attributes->getNamedItem("name")->value);
    }
    /**
    *
    *   
    **/
    public function testCreateByArray()
    {
        $DOM = new \DOMDocument;
        $view = new \Zend_View();
        $builder = Builder::fromArray(
            array(
                'username' => array(
                    'text',
                    array(
                        'label' => 'Username',
                        'required' => true,
                    )
                ),
                'password' => array(
                    'password',
                    array(
                        'label' => 'Password',
                        'required' => true,
                    )
                ),
                'submit' => array(
                    'submit',
                    array(
                        'label' => 'Login'
                    )
                )
            ),
            array(
                "CSRFProtection" => false
            )
        );
        $builder->setView($view);
        
        $DOM->loadHTML($builder);
        $inputEls = $DOM->getElementsByTagName("input");
        $this->assertEquals(3, $inputEls->length);
        
        $username = $inputEls->item(0);
        $this->assertEquals("username", $username->attributes->getNamedItem("name")->value);

        $password= $inputEls->item(1);
        $this->assertEquals("password", $password->attributes->getNamedItem("name")->value);
        $this->assertEquals("password", $password->attributes->getNamedItem("type")->value);
        
        $submitBtn= $inputEls->item(2);
        $this->assertEquals("submit", $submitBtn->attributes->getNamedItem("name")->value);
        $this->assertEquals("submit", $submitBtn->attributes->getNamedItem("type")->value);
    }

    /**
    *
    *     
    */
    public function testModelBindingWithArray()
    {
        $view = new \Zend_View();

        $myModel = array(
            "username" => "",
            "password" => ""
        );

        $builder = new Builder(
            null,
            array(
                "CSRFProtection" => false,
                "model" => &$myModel
            )
        );

        $builder->setView($view);

        // $builder->bindToModel($myModel);
        $builder->addElement("text", "username");
        $builder->addElement("password", "password");
        // test sync from form to model
        $builder->populate(
            array(
                "username" => "User input<html>",
                "password" => "Secret$123"
            )
        );
        $this->assertEquals("User input<html>", $myModel["username"]);
        $this->assertEquals("Secret$123", $myModel["password"]);

        // test sync from model to form
        $myModel["username"] = "Another user";
        $myModel["password"] = "Another pass";

        $builder->syncWithModel();
        $this->assertEquals("Another user", $builder->getElement("username")->getValue());
        $this->assertEquals("Another pass", $builder->getElement("password")->getValue());
    }

    /**
    *
    *   
    */
    public function testModelBindingWithObject()
    {
        $view = new \Zend_View();
        $builder = new Builder(null, array("CSRFProtection" => false));
        $builder->setView($view);



        $myModel = new BuilderTestModel();

        $builder->bindToModel($myModel);
        $builder->addElement("text", "username");
        $builder->addElement("password", "password");
        $builder->addElement("text", "test");
        // test sync from form to model
        $builder->populate(
            (object) array(
                "username" => "User input<html>",
                "password" => "Secret$123",
                "test" => 'test334'
            )
        );
        $this->assertEquals("User input<html>", $myModel->username);
        $this->assertEquals("Secret$123", $myModel->password);
        $this->assertEquals("test334", $myModel->getTest());

        // test sync from model to form
        $myModel->username = "Another user";
        $myModel->password = "Another pass";

        $builder->syncWithModel();
        $this->assertEquals("Another user", $builder->getElement("username")->getValue());
        $this->assertEquals("Another pass", $builder->getElement("password")->getValue());
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method doesNotExist123 does not exist either in \Icinga\Form\Builder nor in Zend_Form
     */
    public function testBadCall1()
    {
        $builder = new Builder(null, array("CSRFProtection" => false));
        $builder->doesNotExist123();
    }
}
