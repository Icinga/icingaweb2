<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

/**
 * Retrieve the version of Icinga Web 2
 */
class Version
{
    /**
     * Get the version of this instance of Icinga Web 2
     *
     * @return array|false array on success, false otherwise
     */
    public static function get()
    {
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
                    return $matches;
                }
            }
        }

        if (false === ($appVersion = @file_get_contents(Icinga::app()->getApplicationDir('VERSION')))) {
            return false;
        }

        $matches = array();
        if (! @preg_match('/^(?P<gitCommitID>\S+)(?: \((.+?)\))? (?P<gitCommitDate>\S+)/', $appVersion, $matches)) {
            return false;
        }

        if (array_key_exists(1, $matches)) {
            $tagMatches = array();
            foreach (explode(', ', $matches[1]) as $gitRef) {
                if (@preg_match('/^tag: v(.+)/', $gitRef, $tagMatches)) {
                    $matches['appVersion'] = $tagMatches[1];
                    break;
                }
            }
            unset($matches[1]);
        }

        return $matches;
    }
}
