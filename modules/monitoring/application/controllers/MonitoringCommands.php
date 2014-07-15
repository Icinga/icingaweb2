<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Icinga\Module\Monitoring\Command\Meta;

/**
 * Class MonitoringCommands
 *
 * Helper which produces a list of command buttons
 * depending on object states
 */
class Zend_View_Helper_MonitoringCommands extends Zend_View_Helper_Abstract
{
    /**
     * Fetch all monitoring commands that are currently available for *all*
     * given objects and render a html string that contains buttons to execute
     * these commands.
     *
     * NOTE: This means that if you give multiple commands, the commands that
     * are not available on every single host, will be left out.
     *
     * @param array|stdClass    $object host or service object or something other
     * @param string            $type   small or full
     *
     * @return string           The rendered html
     */
    public function monitoringCommands($object, $type)
    {
        $commands = new Meta();
        $definitions = $commands->getCommandForObject($object, $type);
        $out = '<div>';
        $i = 0;

        foreach ($definitions as $definition) {

            if ($i % 5 === 0) {
                $out .= '</div><div class="command-section pull-left">';
            }

            if ($type === Meta::TYPE_FULL) {
                $out .= '<div>';
            }

            $out .= sprintf(
                '<button type="button" data-target="command"'
                . ' data-command-id="%1$s" class="btn %5$s"'
                . ' title="%3$s">'
                . '<i class="%4$s"></i> %2$s'
                . '</button>',
                $definition->id,
                $definition->shortDescription,
                $definition->longDescription,
                $definition->iconCls,
                ($definition->btnCls) ? $definition->btnCls : 'btn-default'
            );

            if ($type === Meta::TYPE_FULL) {
                $out .= '</div>';
            }

            $i++;
        }

        $out .= '</div>';

        $out .= '<div class="clearfix"></div>';

        if ($type === Meta::TYPE_FULL) {
            return '<div>'. $out. '</div>';
        }

        return $out;
    }
}
