<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Module\Monitoring\Regression;

require_once __DIR__ . '/../../../application/views/helpers/PluginOutput.php';

use Icinga\Test\BaseTestCase;
use Zend_View_Helper_PluginOutput;

/**
 * Regression-Test for bug #11796
 *
 * Plugin output renderer must not destroy links by adding zero width space characters.
 *
 * @see https://dev.icinga.com/issues/11796
 */
class Bug11796Test extends BaseTestCase
{
    public function testWhetherZeroWidthSpaceDoesntDestroyLinksInPluginOutput()
    {
        $helper = new Zend_View_Helper_PluginOutput();
        $this->assertTrue(
            strpos($helper->pluginOutput('<a href="http://example.com">EXAMPLE.COM', true), 'example.com') !== false
        );
    }
}
