<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Regression;

use Icinga\Test\BaseTestCase;
use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Command\Renderer\IcingaCommandFileCommandRenderer;


/**
 * A command that has a hardcoded parameter with newlines
 */
class Bug6088Command extends IcingaCommand
{
    public function getParameterWithCarriageReturnAndLineFeed()
    {
        return "foo\r\nbar";
    }

    public function getBug()
    {
        return '6088';
    }
}


/**
 * A subclass of IcingaCommandFileCommandRenderer to utiliseIcingaCommandFileCommandRenderer
 * to render an instance of Bug6088Command
 */
class Bug6088CommandFileCommandRenderer extends IcingaCommandFileCommandRenderer
{
    public function renderBug6088(Bug6088Command $command)
    {
        return 'SOLVE_BUG;' . $command->getBug() . ';' . $command->getParameterWithCarriageReturnAndLineFeed();
    }
}


/**
 * Class Bug6088
 *
 * Multi-line comments don't work
 *
 * @see https://dev.icinga.com/issues/6088
 */
class Bug6088Test extends BaseTestCase
{
    public function testWhetherCommandParametersWithMultipleLinesAreProperlyEscaped()
    {
        $command = new Bug6088Command();
        $renderer = new Bug6088CommandFileCommandRenderer();
        $commandString = $renderer->render($command);

        $this->assertEquals(
            'SOLVE_BUG;6088;foo\r\nbar',
            substr($commandString, strpos($commandString, ' ') + 1),
            'Command parameters with multiple lines are not properly escaped'
        );
    }
}
