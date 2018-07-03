<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Helper;

use Icinga\Application\Logger;
use Icinga\Web\Hook;

/**
 * Renderer for plugin output based on hooks
 */
class PluginOutputHookRenderer
{
    /** @var array */
    protected $commandMap = [];

    /**
     * Register PluginOutput hooks
     *
     * Map PluginOutput hooks to their responsible commands.
     *
     * @return  $this
     */
    public function registerHooks()
    {
        if (! Hook::has('monitoring/PluginOutput')) {
            return $this;
        }

        foreach (Hook::all('monitoring/PluginOutput') as $hook) {
            /** @var \Icinga\Module\Monitoring\Hook\PluginOutputHook $hook */
            try {
                $commands = $hook->getCommands();
            } catch (\Exception $e) {
                Logger::error(
                    'Failed to get applicable commands from hook "%s". An error occurred: %s',
                    get_class($hook),
                    $e
                );

                continue;
            }

            if (! is_array($commands)) {
                $commands = [$commands];
            }

            foreach ($commands as $command) {
                if (! isset($this->commandMap[$command])) {
                    $this->commandMap[$command] = [];
                }

                $this->commandMap[$command][] = $hook;
            }
        }

        return $this;
    }

    protected function renderCommand($command, $output, $detail)
    {
        if (isset($this->commandMap[$command])) {
            foreach ($this->commandMap[$command] as $hook) {
                /** @var \Icinga\Module\Monitoring\Hook\PluginOutputHook $hook */

                try {
                    $output = $hook->render($command, $output, $detail);
                } catch (\Exception $e) {
                    Logger::error(
                        'Failed to render plugin output from hook "%s". An error occurred: %s',
                        get_class($hook),
                        $e
                    );

                    continue;
                }
            }
        }

        return $output;
    }

    /**
     * Render the given plugin output based on the specified check command
     *
     * Traverse all hooks which are responsible for the specified check command and call their `render()` methods.
     *
     * @param   string  $command    Check command
     * @param   string  $output     Plugin output
     * @param   bool    $detail     Whether the output is requested from the detail area
     *
     * @return  string
     */
    public function render($command, $output, $detail)
    {
        if (empty($this->commandMap)) {
            return $output;
        }

        $output = $this->renderCommand('*', $output, $detail);
        $output = $this->renderCommand($command, $output, $detail);

        return $output;
    }
}
