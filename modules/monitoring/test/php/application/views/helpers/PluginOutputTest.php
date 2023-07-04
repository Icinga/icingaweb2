<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Module\Monitoring\Application\Views\Helpers;

use Icinga\Application\Icinga;
use Icinga\Web\View;
use Zend_View_Helper_PluginOutput;
use Icinga\Test\BaseTestCase;

class PluginOutputTest extends BaseTestCase
{
    /** @var  Zend_View_Helper_PluginOutput */
    protected $helper;

    const PREFIX_PRE = '<div class="plugin-output preformatted">';
    const PREFIX = '<div class="plugin-output">';
    const SUFFIX = '</div>';

    protected static $statusTags = array('OK', 'WARNING', 'CRITICAL', 'UNKNOWN', 'UP', 'DOWN');

    public function setUp(): void
    {
        parent::setUp();

        require_once realpath(
            Icinga::app()->getModuleManager()->getModuleDir('monitoring')
            . '/application/views/helpers/PluginOutput.php'
        );

        $this->helper = $h = new Zend_View_Helper_PluginOutput;
        $h->setView(new View());
    }

    protected function checkOutput($output, $html, $regexp = false, $isHtml = false)
    {
        $actual = $this->helper->pluginOutput($output);

        if ($isHtml) {
            $prefix = self::PREFIX;
        } else {
            $prefix = self::PREFIX_PRE;
        }

        if ($regexp) {
            $expect = sprintf(
                '~%s%s%s~',
                preg_quote($prefix, '~'),
                $html,
                preg_quote(self::SUFFIX, '~')
            );
            $this->assertMatchesRegularExpression($expect, $actual, 'Output must match example regexp');
        } else {
            $expect = $prefix . $html . self::SUFFIX;
            $this->assertEquals($expect, $actual, 'Output must match example');
        }
    }

    protected function checkHtmlOutput($outputHtml, $html, $regexp = false)
    {
        return $this->checkOutput($outputHtml, $html, $regexp, true);
    }

    public function testSimpleOutput()
    {
        $this->checkOutput(
            'Foobar',
            'Foobar'
        );
    }

    public function testOutputWithNewlines()
    {
        $this->checkOutput(
            'foo\nbar\n\nraboof',
            "foo\nbar\n\nraboof"
        );
    }

    public function testOutputWithHtmlEntities()
    {
        $this->checkOutput(
            'foo&nbsp;&amp;&nbsp;bar',
            'foo&nbsp;&amp;&nbsp;bar'
        );
    }

    public function testSimpleHtmlOutput()
    {
        /** @noinspection HtmlUnknownAttribute */
        $this->checkHtmlOutput(
            'OK - Teststatus <a href="http://localhost/test.php" target="_blank">Info</a>',
            'OK - Teststatus <a href="http://localhost/test.php" target="_blank"[^>]*>Info</a>',
            true
        );
    }

    public function testMultilineHtmlOutput()
    {
        $input = array(
            'Teststatus',
            '<a href="http://localhost/test.php" target="_blank">Info</a><br/><br/>'
            . '<a href="http://localhost/test2.php" target="_blank">Info2</a>'
        );
        /** @noinspection HtmlUnknownAttribute */
        $output = array(
            'Teststatus',
            '<a href="http://localhost/test.php" target="_blank"[^>]*>Info</a><br><br>'
            . '<a href="http://localhost/test2.php" target="_blank"[^>]*>Info2</a>'
        );
        $this->checkHtmlOutput(
            join("\n", $input),
            join("\n", $output),
            true
        );
    }

    public function testHtmlTable()
    {
        $this->markTestIncomplete();
    }

    public function testAllowedHtmlTags()
    {
        $this->markTestIncomplete();
    }

    public function testTextStatusTags()
    {
        foreach (self::$statusTags as $s) {
            $l = strtolower($s);
            $this->checkOutput(
                sprintf('[%s] Test', $s),
                sprintf('<span class="state-%s">[%s]</span> Test', $l, $s)
            );
            $this->checkOutput(
                sprintf('(%s) Test', $s),
                sprintf('<span class="state-%s">(%s)</span> Test', $l, $s)
            );
        }
    }

    public function testHtmlStatusTags()
    {
        $dummyHtml = '<a href="#"></a>';

        foreach (self::$statusTags as $s) {
            $l = strtolower($s);
            $this->checkHtmlOutput(
                sprintf('%s [%s] Test', $dummyHtml, $s),
                sprintf('%s <span class="state-%s">[%s]</span> Test', $dummyHtml, $l, $s)
            );
            $this->checkHtmlOutput(
                sprintf('%s (%s) Test', $dummyHtml, $s),
                sprintf('%s <span class="state-%s">(%s)</span> Test', $dummyHtml, $l, $s)
            );
        }
    }

    public function testNewlineProcessingInHtmlOutput()
    {
        $this->checkHtmlOutput(
            'This is plugin output\n\n<ul>\n    <li>with a HTML list</li>\n</ul>\n\n'
            . 'and more text that\nis split onto multiple\n\nlines',
            <<<HTML
This is plugin output

<ul>
    <li>with a HTML list</li>
</ul>

and more text that
is split onto multiple

lines
HTML
        );
    }
}
