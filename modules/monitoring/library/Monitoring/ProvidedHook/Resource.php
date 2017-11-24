<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\ProvidedHook;

use Icinga\Application\Config;
use Icinga\Application\Hook\ResourceHook;
use Icinga\Exception\IcingaException;

class Resource extends ResourceHook
{
    public function beforeRemove($resourceName)
    {
        $backends = array();
        foreach (Config::module('monitoring', 'backends') as $backend => $backendConfig) {
            if ($backendConfig->resource === $resourceName) {
                $backends[$backend] = null;
            }
        }

        if (! empty($backends)) {
            ksort($backends);

            throw new IcingaException(
                mtp(
                    'monitoring',
                    'The resource %s is used by the monitoring backend %s',
                    'The resource %s is used by the monitoring backends: %s',
                    count($backends)
                ),
                var_export($resourceName, true),
                implode(',', array_map(
                    function ($backend) {
                        return var_export($backend, true);
                    },
                    array_keys($backends)
                ))
            );
        }
    }

    public function beforeRename($oldResourceName, $newResourceName)
    {
        $backendsConfig = Config::module('monitoring', 'backends');
        $changed = false;

        foreach ($backendsConfig as $backend => $backendConfig) {
            if ($backendConfig->resource === $oldResourceName) {
                $backendConfig->resource = $newResourceName;
                $changed = true;
            }
        }

        if ($changed) {
            $backendsConfig->saveIni();
        }
    }
}
