<?php

namespace Icinga\Module\Setup\Hook;

use Icinga\Application\Hook;
use Icinga\Module\Setup\RequirementSet;

class RequirementsHook extends Hook
{
    /**
     * @return RequirementSet
     */
    public static function allRequirements($skipModules = false)
    {
        $hooks = static::all('setup/requirements', true);

        $set = new RequirementSet();

        /** @var RequirementsHook $hook */
        foreach ($hooks as $hook) {
            $module = $hook->getModuleName();

            if ($skipModules && $module !== null && $module !== 'setup') {
                continue;
            }

            $req = $hook->getRequirements();
            if ($req !== null) {
                $set->merge($hook->getRequirements());
            }
        }

        return $set;
    }

    /**
     * @abstract Implement your requirements here
     *
     * @return RequirementSet|null
     */
    public function getRequirements()
    {
        return null;
    }

    /**
     * Returns the module name of the Hook implementation
     * @return string|null
     */
    public function getModuleName()
    {
        $class = get_class($this);
        if (substr($class, 0, 14) === 'Icinga\\Module\\') {
            $parts = explode('\\', $class);
            return strtolower($parts[2]);
        } else {
            return null;
        }
    }
}
