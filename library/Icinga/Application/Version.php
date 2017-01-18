<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

/**
 * Retrieve the version of Icinga Web 2
 */
class Version
{
    const VERSION = '2.4.1';

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
     * Get the current commit of the Git repository in the given path
     *
     * @param   string  $repo       Path to the Git repository
     * @param   bool    $bare       Whether the Git repository is bare
     *
     * @return  string|bool         False if not available
     */
    public static function getGitHead($repo, $bare = false)
    {
        if (! $bare) {
            $repo .= '/.git';
        }

        $head = @file_get_contents($repo . '/HEAD');

        if ($head !== false) {
            if (preg_match('/^ref: (.+)/', $head, $matches)) {
                return @file_get_contents($repo . '/' . $matches[1]);
            }

            return $head;
        }

        return false;
    }
}
