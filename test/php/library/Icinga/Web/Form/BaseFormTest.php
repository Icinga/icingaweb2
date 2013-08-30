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

    require_once realpath('../../library/Icinga/Test/BaseTestCase.php');

    use \Icinga\Test\BaseTestCase;

    /**
     * Base test to be extended for testing forms
     */
    class BaseFormTest extends BaseTestCase
    {
        /**
         * Temporary wrapper for BaseTestCase::createForm until this testcase is not used anymore
         */
        public function getRequestForm(array $data, $formClass)
        {
            return $this->createForm($formClass, $data);
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
