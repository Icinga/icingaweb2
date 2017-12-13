<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Application;

use DirectoryIterator;
use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\File\Storage\TemporaryLocalFileStorage;
use Icinga\Test\BaseTestCase;

class PhpCodeValidityTest extends BaseTestCase
{
    /**
     * Collect all classes, interfaces and traits and let PHP validate them as if included
     */
    public function testAllClassesInterfacesAndTraits()
    {
        $baseDir = realpath(__DIR__ . '/../../../../..');
        $storage = new TemporaryLocalFileStorage();

        $storage->create('etc/icingaweb2/config.ini', (string) new Config(new ConfigObject(array(
            'global' => array(
                'module_path'       => "$baseDir/modules",
                'config_backend'    => 'ini'
            )
        ))));

        $icingacli = 'ICINGAWEB_CONFIGDIR=' . $storage->resolvePath('etc/icingaweb2')
            . ' ' . realpath('/proc/self/exe') . " $baseDir/bin/icingacli";

        foreach (new DirectoryIterator("$baseDir/modules") as $module) {
            if (! $module->isDot() && $module->isDir()) {
                $this->system("$icingacli module enable {$module->getFilename()}");
            }
        }

        $this->system("$icingacli test php validity");
    }

    protected function system($command)
    {
        echo "+ $command" . PHP_EOL;

        $return = 127;
        system($command, $return);

        $this->assertSame(0, $return);
    }
}
