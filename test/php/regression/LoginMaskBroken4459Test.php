<?php
namespace {
    if (!function_exists('t')) {
        function t() {
            return func_get_arg(0);
        }
    }

    if (!function_exists('mt')) {
        function mt() {
            return func_get_arg(0);
        }
    }
}

namespace Tests\Icinga\Regression
{

    use Icinga\Form\Authentication\LoginForm;

    require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';
    require_once 'Zend/Form.php';
    require_once 'Zend/View.php';
    require_once 'Zend/Form/Element/Submit.php';
    require_once 'Zend/Form/Element/Reset.php';
    require_once 'Zend/Form/Element/Checkbox.php';
    require_once 'Zend/Form/Element/Hidden.php';
    require_once 'Zend/Validate/Date.php';
    require_once '../../library/Icinga/Web/Form.php';
    require_once realpath('../../application/forms/Authentication/LoginForm.php');


    class LoginMaskBrokenRegression_4459Test extends \Zend_Test_PHPUnit_ControllerTestCase
    {

        public function testShowLoginForm()
        {
            $view = new \Zend_View();
            $form = new LoginForm();
            $form->buildForm();
            $rendered = $form->render($view);

            $this->assertContains("<form", $rendered, "Asserting a form being returned when displaying the login form");
        }

        public function testSubmitLoginForm()
        {
            $request = $this->getRequest();

            $request->setMethod("POST")->setPost(array(
                "username" => "test",
                "password" => "test"
            ));

            $view = new \Zend_View();
            $form = new LoginForm();
            $form->setRequest($request);
            $form->buildForm();
            $this->assertTrue($form->isPostAndValid());

        }
    }

}