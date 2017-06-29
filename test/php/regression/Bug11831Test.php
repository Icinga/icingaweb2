<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Regression;

use ReflectionClass;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Test\BaseTestCase;

/**
 * Regression-Test for bug #11831
 *
 * Empty lines in module.info must be ignored if they're not part of the module's description
 *
 * @see https://dev.icinga.com/issues/11831
 */
class Bug11831Test extends BaseTestCase
{
    public function testNewlinesInModuleInfo()
    {
        $moduleInfo = <<<'EOT'

version: 1.0.0

EOT;
        $moduleInfoFile = tmpfile();
        fwrite($moduleInfoFile, $moduleInfo);
        $module = new Module(Icinga::app(), 'Bug11831', '/dev/null');
        $reflection = new ReflectionClass($module);
        $prop = $reflection->getProperty('metadataFile');
        $prop->setAccessible(true);
        $meta = stream_get_meta_data($moduleInfoFile);
        $prop->setValue($module, $meta['uri']);
        $this->assertEquals($module->getVersion(), '1.0.0');
        fclose($moduleInfoFile);
    }
}
