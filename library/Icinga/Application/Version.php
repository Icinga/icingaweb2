<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

/**
 * Retrieve the version of Icinga Web 2
 */
class Version
{
    const VERSION = '2.1.2';

    /**
     * Get the version of this instance of Icinga Web 2
     *
     * @return array
     */
    public static function get()
    {
        $version = array('appVersion' => self::VERSION);
        if (false !== ($appVersion = @file_get_contents(Icinga::app()->getApplicationDir('VERSION')))) {
            $matches = array();
            if (@preg_match('/^(?P<gitCommitID>\w+) (?P<gitCommitDate>\S+)/', $appVersion, $matches)) {
                return array_merge($version, $matches);
            }
        }

        $gitDir = Icinga::app()->getBaseDir('.git');
        $gitHead = @file_get_contents($gitDir . DIRECTORY_SEPARATOR . 'HEAD');
        if (false !== $gitHead) {
            $matches = array();
            if (@preg_match('/(?<!.)ref:\s+(.+?)$/ms', $gitHead, $matches)) {
                $gitCommitID = @file_get_contents($gitDir . DIRECTORY_SEPARATOR . $matches[1]);
            } else {
                $gitCommitID = $gitHead;
            }

            if (false !== $gitCommitID) {
                $matches = array();
                if (@preg_match('/(?<!.)(?P<gitCommitID>[0-9a-f]+)$/ms', $gitCommitID, $matches)) {
                    return array_merge($version, $matches);
                }
            }
        }

        return $version;
    }
}
