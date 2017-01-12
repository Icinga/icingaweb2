<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Module\Monitoring\Regression;

require_once __DIR__ . '/../../../application/views/helpers/PluginOutput.php';

use Icinga\Test\BaseTestCase;
use Zend_View_Helper_PluginOutput;

/**
 * Regression-Test for bug #11728
 *
 * Plugin output renderer must preserve the first character after a comma.
 *
 * @see https://dev.icinga.com/issues/11728
 */
class Bug11728Test extends BaseTestCase
{
    public function testWhetherPluginOutputPreservesCharacterAfterComma()
    {
        $helper = new Zend_View_Helper_PluginOutput();
        $this->assertTrue(strpos($helper->pluginOutput('<a href="#">A,BC', true), 'BC') !== false);
    }
}
