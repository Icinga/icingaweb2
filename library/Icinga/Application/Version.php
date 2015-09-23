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

        if (false === ($appVersion = @file_get_contents(
            Icinga::app()->getApplicationDir() . DIRECTORY_SEPARATOR . 'VERSION'
        ))) {
            return false;
        }

        $matches = array();
        if (false === ($res = preg_match(
            '/(?<!.)\s*(?P<gitCommitID>\w+)(?:\s*\(.*?(?:(?<=[\(,])\s*tag\s*:\s*v(?P<appVersion>.+?)\s*(?=[\),]).*?)?\))?\s*(?P<gitCommitDate>\S+)/ms',
            $appVersion,
            $matches
        )) || $res === 0) {
            return false;
        }

        foreach ($matches as $key => $value) {
            if (is_int($key) || $value === '') {
                unset($matches[$key]);
            }
        }
        return $matches;
    }
}
