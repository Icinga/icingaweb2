<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

/**
 * Base class for plugin output hooks
 *
 * The Plugin Output Hook allows you to rewrite the plugin output based on check commands.
 * You have to implement the following methods:
 * * {@link getCommands()}
 * * and {@link render()}
 */
abstract class PluginOutputHook
{
    /**
     * Get the command or list of commands the hook is responsible for
     *
     * With this method you specify for which commands the provided hook is responsible for. You may return a single
     * command as string or a list of commands as array.
     * If you want your hook to be responsible for every command, you have to return the asterisk `'*'`.
     *
     * @return  string|array
     */
    abstract public function getCommands();

    /**
     * Render the given plugin output based on the specified check command
     *
     * With this method you rewrite the plugin output based on check commands. The parameter `$command` specifies the
     * check command of the host or service and `$output` specifies the plugin output. The parameter `$detail` tells you
     * whether the output is requested from the detail area of the host or service.
     *
     * Do not use complex logic for rewriting plugin output in list views because of the performance impact!
     *
     * You have to return the rewritten plugin output as string. It is also possible to return a HTML string here.
     * Please refer to {@link \Icinga\Module\Monitoring\Web\Helper\PluginOutputPurifier} for a list of allowed tags.
     *
     * @param   string  $command    Check command
     * @param   string  $output     Plugin output
     * @param   bool    $detail     Whether the output is requested from the detail area
     *
     * @return  string              Rewritten plugin output
     */
    abstract public function render($command, $output, $detail);
}
