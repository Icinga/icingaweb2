<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Test\Clicommands;

use DOMDocument;
use DOMXPath;
use Icinga\Application\Icinga;
use Icinga\Cli\Command;
use Icinga\File\Storage\TemporaryLocalFileStorage;

/**
 * PHP unit- & style-tests
 */
class PhpCommand extends Command
{
    /**
     * Run all unit-test suites
     *
     * This command runs the unit- and regression-tests of icingaweb and installed modules.
     *
     * USAGE
     *
     * icingacli test php unit [options]
     *
     * OPTIONS
     *
     *   --verbose  Be more verbose.
     *   --build    Enable reporting.
     *   --include  Pattern to use for including files/test cases.
     *
     * EXAMPLES
     *
     *   icingacli test php unit --verbose
     *   icingacli test php unit --build
     *   icingacli test php unit --include=*SpecialTest
     *
     * NOTES
     *
     *   Default settings are defined via `modules/test/phpunit.xml` under icingaweb'
     *   installation directory.
     */
    public function unitAction()
    {
        $build = $this->params->shift('build');
        $include = $this->params->shift('include');

        $phpUnit = exec('which phpunit');
        if (!file_exists($phpUnit)) {
            $this->fail('PHPUnit not found. Please install PHPUnit to be able to run the unit-test suites.');
        }

        $options = array();
        if ($this->isVerbose) {
            $options[] = '--verbose --testdox';
        }
        if ($build) {
            $reportPath = $this->setupAndReturnReportDirectory();
            $options[] = '--log-junit';
            $options[] = $reportPath . '/phpunit_results.xml';
            $options[] = '--coverage-html';
            $options[] = $reportPath . '/php_html_coverage';
        }
        if ($include !== null) {
            $options[] = '--filter';
            $options[] = $include;
        }

        $baseDir = Icinga::app()->getBaseDir();
        $phpunitXml = new DOMDocument();
        $temp = new TemporaryLocalFileStorage();

        $phpunitXml->loadXML(file_get_contents("$baseDir/modules/test/phpunit.xml"));
        $this->adjustPhpunitDom($phpunitXml);
        $temp->create('phpunit.xml', $phpunitXml->saveXML());

        chdir($baseDir);
        $command = $this->getEnvironmentVariables()
            . $phpUnit
            . " -c {$temp->resolvePath('phpunit.xml')}"
            . ' ' . join(' ', array_merge($options, $this->params->getAllStandalone()));
        
        exec($command, $output, $resultCode);

        foreach ($output as $line) {
            if ($this->isVerbose && preg_match('~\s+\[([x\s])\]\s~', $line, $m)) {
                echo $this->screen->colorize($line, $m[1] === 'x' ? 'green' : 'red');
            } else {
                echo $line;
            }

            echo "\n";
        }

        $temp = null;
        exit($resultCode);
    }

    /**
     * Run code-style checks
     *
     * This command checks whether icingaweb and installed modules match the PSR-2 coding standard.
     *
     * USAGE
     *
     * icingacli test php style [options]
     *
     * OPTIONS
     *
     *   --verbose  Be more verbose.
     *   --build    Enable reporting.
     *   --include  Include only specific files. (Can be supplied multiple times.)
     *   --exclude  Pattern to use for excluding files. (Can be supplied multiple times.)
     *
     * EXAMPLES
     *
     *   icingacli test php style --verbose
     *   icingacli test php style --build
     *   icingacli test php style --include=path/to/your/file
     *   icingacli test php style --exclude=*someFile* --exclude=someOtherFile*
     *
     * NOTES
     *
     *   Default rules are defined via `phpcs.xml` and `icingaweb2.ruleset.xml` in icingaweb'
     *   installation directory.
     */
    public function styleAction()
    {
        $build = $this->params->shift('build');
        $include = (array) $this->params->shift('include', array());
        $exclude = (array) $this->params->shift('exclude', array());

        $phpcs = exec('which phpcs');
        if (!file_exists($phpcs)) {
            $this->fail(
                'PHP_CodeSniffer not found. Please install PHP_CodeSniffer to be able to run code style tests.'
            );
        }

        $options = array();
        if ($this->isVerbose) {
            $options[] = '-v';
        }
        if ($build) {
            $options[] = '--report-checkstyle=' . $this->setupAndReturnReportDirectory();
        }
        if (!empty($exclude)) {
            $options[] = '--ignore=' . join(',', $exclude);
        }
        $arguments = array_filter(array_map(function ($p) {
            return realpath($p);
        }, $include));

        chdir(Icinga::app()->getBaseDir());
        passthru(
            $phpcs . ' ' . join(
                ' ',
                array_merge(
                    $options,
                    $arguments,
                    $this->params->getAllStandalone()
                )
            )
        );
    }

    /**
     * Setup the directory where to put report files and return its path
     *
     * @return  string
     */
    protected function setupAndReturnReportDirectory()
    {
        $path = realpath(__DIR__ . '/../../../..') . '/build/log';
        if (!file_exists($path) && !@mkdir($path, 0755, true)) {
            $this->fail("Could not create directory: $path");
        }

        return $path;
    }

    /**
     * Setup some required environment variables
     */
    protected function getEnvironmentVariables()
    {
        $modulePaths = [];
        foreach (Icinga::app()->getModuleManager()->getModuleInfo() as $module) {
            if (! file_exists($module->path . '/phpunit.xml')) {
                $modulePaths[] = $module->path;
            }
        }

        $vars = array();
        $vars[] = sprintf('ICINGAWEB_BASEDIR=%s', $this->app->getBaseDir());
        $vars[] = sprintf('ICINGAWEB_ICINGA_LIB=%s', $this->app->getLibraryDir('Icinga'));
        $vars[] = sprintf('ICINGAWEB_MODULE_DIRS=%s', implode(':', $modulePaths));

        // Disabled as the bootstrap.php for PHPUnit and class BaseTestCase can't handle multiple paths yet
        $vars[] = sprintf(
            'ICINGAWEB_MODULES_DIR=%s',
            implode(PATH_SEPARATOR, $this->app->getModuleManager()->getModuleDirs())
        );

        return join(' ', $vars) . ' ';
    }

    /**
     * Make all relative paths absolute and include all installed modules
     *
     * @param   DOMDocument $phpunitXml
     */
    protected function adjustPhpunitDom(DOMDocument $phpunitXml)
    {
        $app = Icinga::app();
        $modulesTest = "{$app->getBaseDir()}/modules/test/";
        $domPath = new DOMXPath($phpunitXml);

        $phpunit = $domPath->query("//phpunit")->item(0);
        $phpunit->setAttribute('bootstrap', $modulesTest . $phpunit->getAttribute('bootstrap'));

        foreach ([
            '//phpunit/testsuites/testsuite/directory',
            '//phpunit/testsuites/testsuite/exclude',
            '//phpunit/filter/whitelist/directory',
            '//phpunit/filter/whitelist/exclude/directory',
            '//phpunit/filter/whitelist/exclude/file'
        ] as $xPath) {
            $nodes = $domPath->query($xPath);

            for ($i = 0; $i < $nodes->length; ++$i) {
                $element = $nodes->item($i);
                $element->nodeValue = $modulesTest . $element->nodeValue;
            }
        }

        $unitModules = $domPath->query("//phpunit/testsuites/testsuite[@name='unit-modules']")->item(0);
        $regressionModules = $domPath->query("//phpunit/testsuites/testsuite[@name='regression-modules']")->item(0);

        while ($unitModules->hasChildNodes()) {
            $unitModules->removeChild($unitModules->childNodes->item(0));
        }

        while ($regressionModules->hasChildNodes()) {
            $regressionModules->removeChild($regressionModules->childNodes->item(0));
        }

        foreach ($app->getModuleManager()->getModuleInfo() as $module) {
            $testPhp = "$module->path/test/php";
            if (file_exists($testPhp) && ! file_exists($module->path . '/phpunit.xml')) {
                $unitModules->appendChild($phpunitXml->createElement('directory', $testPhp));

                $testPhpRegression = "$testPhp/regression";
                if (file_exists($testPhpRegression)) {
                    $regressionModules->appendChild($phpunitXml->createElement('directory', $testPhpRegression));
                    $unitModules->appendChild($phpunitXml->createElement('exclude', $testPhpRegression));
                }
            }
        }

        if (! $unitModules->hasChildNodes()) {
            $unitModules->parentNode->removeChild($unitModules);
        }

        if (! $regressionModules->hasChildNodes()) {
            $regressionModules->parentNode->removeChild($regressionModules);
        }
    }
}
