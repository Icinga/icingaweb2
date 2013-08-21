<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}


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

namespace Test\Icinga\Web\Form {

    require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';
    require_once 'Zend/Form.php';
    require_once 'Zend/View.php';
    require_once 'Zend/Form/Element/Submit.php';
    require_once 'Zend/Form/Element/Text.php';
    require_once 'Zend/Form/Element/Password.php';
    require_once 'Zend/Form/Element/Reset.php';
    require_once 'Zend/Form/Element/Checkbox.php';
    require_once 'Zend/Form/Element/Hidden.php';
    require_once 'Zend/Form/Decorator/Abstract.php';
    require_once 'Zend/Validate/Date.php';
    $base = '../../';

    require_once realpath($base.'library/Icinga/Exception/ProgrammingError.php');
    require_once realpath($base.'library/Icinga/Web/Form.php');
    require_once realpath($base.'library/Icinga/Web/Form/InvalidCSRFTokenException.php');
    require_once realpath($base.'library/Icinga/Web/Form/Element/Note.php');
    require_once realpath($base.'library/Icinga/Web/Form/Element/DateTimePicker.php');
    require_once realpath('../../library/Icinga/Web/Form/Decorator/ConditionalHidden.php');
    require_once realpath('../../library/Icinga/Web/Form/Decorator/HelpText.php');
    require_once realpath('../../library/Icinga/Web/Form/Validator/WritablePathValidator.php');
    require_once realpath('../../library/Icinga/Web/Form/Validator/DateFormatValidator.php');
    require_once realpath('../../library/Icinga/Web/Form/Validator/TimeFormatValidator.php');

    use \Zend_Form;
    use \Zend_Test_PHPUnit_ControllerTestCase;

    /**
     * Base test to be extended for testing forms
     */
    class BaseFormTest extends Zend_Test_PHPUnit_ControllerTestCase
    {

        /**
         * Returns a formclass with the given set of POST data applied
         *
         * @param array         $data           The POST parameters to ste
         * @param string        $formClass      The class name (full namespace) to return
         *
         * @return Zend_Form    $form           A form of type $formClass
         */
        public function getRequestForm(array $data, $formClass)
        {
            $form = new $formClass();
            $form->setSessionId('test');
            $form->initCsrfToken();
            $request = $this->getRequest();
            $data[$form->getTokenElementName()] = $form->getValue($form->getTokenElementName());

            $request->setMethod('POST')->setPost($data);
            $form->setRequest($request);

            return $form;
        }

        /**
         * This is just a test to avoid warnings being submitted from the testrunner
         *
         */
        public function testForRemovingWarnings()
        {
            $this->assertTrue(true);
        }
    }

}
