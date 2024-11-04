<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Closure;
use Exception;
use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Module\Monitoring\Object\MonitoredObject;

abstract class CustomVarRendererHook
{
    /**
     * Prefetch the data the hook needs to render custom variables
     *
     * @param MonitoredObject $object The object for which they'll be rendered
     *
     * @return bool Return true if the hook can render variables for the given object, false otherwise
     */
    abstract public function prefetchForObject(MonitoredObject $object);

    /**
     * Render the given variable name
     *
     * @param string $key
     *
     * @return ?mixed
     */
    abstract public function renderCustomVarKey($key);

    /**
     * Render the given variable value
     *
     * @param string $key
     * @param mixed $value
     *
     * @return ?mixed
     */
    abstract public function renderCustomVarValue($key, $value);

    /**
     * Return a group name for the given variable name
     *
     * @param string $key
     *
     * @return ?string
     */
    abstract public function identifyCustomVarGroup($key);

    /**
     * Prepare available hooks to render custom variables of the given object
     *
     * @param MonitoredObject $object
     *
     * @return Closure A callback ($key, $value) which returns an array [$newKey, $newValue, $group]
     */
    final public static function prepareForObject(MonitoredObject $object)
    {
        $hooks = [];
        foreach (Hook::all('Monitoring/CustomVarRenderer') as $hook) {
            /** @var self $hook */
            try {
                if ($hook->prefetchForObject($object)) {
                    $hooks[] = $hook;
                }
            } catch (Exception $e) {
                Logger::error('Failed to load hook %s: %s', get_class($hook), $e);
                Logger::debug($e);
            }
        }

        return function ($key, $value) use ($hooks) {
            $newKey = $key;
            $newValue = $value;
            $group = null;
            foreach ($hooks as $hook) {
                /** @var self $hook */

                try {
                    $renderedKey = $hook->renderCustomVarKey($key);
                    $renderedValue = $hook->renderCustomVarValue($key, $value);
                    $group = $hook->identifyCustomVarGroup($key);
                } catch (Exception $e) {
                    Logger::error('Failed to use hook %s: %s', get_class($hook), $e);
                    Logger::debug($e);
                    continue;
                }

                if ($renderedKey !== null || $renderedValue !== null) {
                    $newKey = $renderedKey !== null ? $renderedKey : $key;
                    $newValue = $renderedValue !== null ? $renderedValue : $value;
                    break;
                }
            }

            return [$newKey, $newValue, $group];
        };
    }
}
