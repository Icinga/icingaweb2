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

namespace Test\Monitoring\Forms\Command {

    require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';
    require_once 'Zend/Form.php';
    require_once 'Zend/View.php';
    require_once 'Zend/Form/Element/Submit.php';
    require_once 'Zend/Form/Element/Reset.php';
    require_once 'Zend/Form/Element/Checkbox.php';
    require_once 'Zend/Form/Element/Hidden.php';
    require_once 'Zend/Validate/Date.php';
    $base = __DIR__.'/../../../../../../../';
    require_once realpath($base.'library/Icinga/Exception/ProgrammingError.php');
    require_once realpath($base.'library/Icinga/Web/Form.php');
    require_once realpath($base.'library/Icinga/Web/Form/InvalidCSRFTokenException.php');
    require_once realpath($base.'library/Icinga/Web/Form/Element/Note.php');
    require_once realpath($base.'library/Icinga/Web/Form/Element/DateTimePicker.php');

    use \Zend_View;
    use \Zend_Form;
    use \Zend_View_Interface;
    use \Zend_Form_Element_Reset;
    use \Zend_Form_Element_Submit;
    use \Zend_Controller_Request_Abstract;
    use \Zend_Test_PHPUnit_ControllerTestCase;


    class BaseFormTest extends Zend_Test_PHPUnit_ControllerTestCase
    {

        public function getRequestForm(array $data, $formClass)
        {
            $form = new $formClass();
            $form->setSessionId("test");
            $form->initCsrfToken();
            $request = $this->getRequest();
            $data[$form->getTokenElementName()] = $form->getValue($form->getTokenElementName());

            $request->setMethod("POST")->setPost($data);
            $form->setRequest($request);

            return $form;
        }
    }

}
