<?php

namespace Icinga\Module\Setup;

use Icinga\Application\Modules\Module;
use Icinga\Module\Setup\Requirement\ModuleMissingRequirement;
use Icinga\Module\Setup\Requirement\SetRequirement;
use Icinga\Module\Setup\Requirement\WebLibraryRequirement;
use Icinga\Module\Setup\Requirement\WebModuleRequirement;

class ModuleDependency
{
    /** @var Module The given Module */
    protected $module;

    /** @var array The chosen modules */
    protected $checkedModules;

    /**
     * @param Module $module The given module
     *
     * @param array $checkedModules The checked modules from module page
     */
    public function __construct(Module $module, array $checkedModules)
    {
        $this->module = $module;
        $this->checkedModules = $checkedModules;
    }

    /**
     * Get the module dependency requirements
     *
     * @return RequirementSet
     */
    public function getRequirements()
    {
        $icingadbAndMonitoring = [];
        $set = new RequirementSet();

        foreach ($this->module->getRequiredModules() as $name => $requiredVersion) {
            if ($name === 'monitoring' || $name === 'icingadb') {
                $icingadbAndMonitoring[$name] = $requiredVersion;

                continue;
            }

            $options = [
                'alias'         => $name,
                'description'   => sprintf(
                    t('Module %s (%s) is required.'),
                    $name,
                    $requiredVersion
                )
            ];

            if (! in_array($name, $this->checkedModules)) {
                $set->add(new ModuleMissingRequirement($options));
            } else {
                $options['condition'] = [$name, $requiredVersion];
                $set->add(new WebModuleRequirement($options));
            }
        }

        if (! empty($icingadbAndMonitoring)) {
            $icingadbOrMonitoring = new RequirementSet(false, RequirementSet::MODE_OR);
            foreach ($icingadbAndMonitoring as $name => $requiredVersion) {
                $options = [
                    'alias'         => $name,
                    'optional'      => true,
                    'description'   => sprintf(
                        t('Module %s (%s) is required.'),
                        $name,
                        $requiredVersion
                    )
                ];

                if (! in_array($name, $this->checkedModules)) {
                    $icingadbOrMonitoring->add(new ModuleMissingRequirement($options));
                } else {
                    $options['condition'] = [$name, $requiredVersion];
                    $icingadbOrMonitoring->add(new WebModuleRequirement($options));
                }
            }

            $set->merge($icingadbOrMonitoring);

            $requirement = (new SetRequirement([
                'title'         =>'Base Module',
                'alias'         => 'Monitoring OR Icingadb',
                'optional'      => false,
                'condition'     => $icingadbOrMonitoring,
                'description'   => t('Module Monitoring OR Icingadb is required.')
            ]));

            $set->add($requirement);
        }

        foreach ($this->module->getRequiredLibraries() as $name => $requiredVersion) {
            $set->add(new WebLibraryRequirement([
                'condition'     => [$name, $requiredVersion],
                'alias'         => $name,
                'description'   => sprintf(t('The %s library (%s) is required'), $name, $requiredVersion)
            ]));
        }

        return $set;
    }
}
