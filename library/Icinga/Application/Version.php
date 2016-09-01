<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

/**
 * Retrieve the version of Icinga Web 2
 */
class Version
{
    const VERSION = '2.3.4';

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

        $gitCommitId = static::getGitHead(Icinga::app()->getBaseDir());
        if ($gitCommitId !== false) {
            $version['gitCommitID'] = $gitCommitId;
        }

        return $version;
    }

    /**
     * Get the hexadecimal ID of the HEAD commit of the Git repository in $repoDir
     *
     * @param   string  $repoDir
     * @param   bool    $bare       Whether the repository has been created with
     *                              $ git init|clone --bare
     *
     * @return  string|bool         false if not available
     */
    public static function getGitHead($repoDir, $bare = false)
    {
        if (! $bare) {
            $repoDir .= DIRECTORY_SEPARATOR . '.git';
        }

        $gitHead = @ file_get_contents($repoDir . DIRECTORY_SEPARATOR . 'HEAD');
        if ($gitHead !== false) {
            $matches = array();
            if (preg_match('/(?<!.)ref:\s+(.+?)$/ms', $gitHead, $matches)) {
                $gitCommitID = @ file_get_contents($repoDir . DIRECTORY_SEPARATOR . $matches[1]);
            } else {
                $gitCommitID = $gitHead;
            }

            if ($gitCommitID !== false) {
                $matches = array();
                if (preg_match('/(?<!.)([0-9a-f]+)$/ms', $gitCommitID, $matches)) {
                    return $matches[1];
                }
            }
        }

        return false;
    }
}
