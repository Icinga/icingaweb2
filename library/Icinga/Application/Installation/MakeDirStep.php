<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Installation;

use Icinga\Web\Setup\Step;

class MakeDirStep extends Step
{
    protected $paths;

    protected $dirmode;

    protected $errors;

    /**
     * @param array $paths
     * @param string $dirmode
     */
    public function __construct($paths, $dirmode)
    {
        $this->paths = $paths;
        $this->dirmode = octdec($dirmode) | octdec('111'); // Make sure that the directories can be traversed
        $this->errors = array();
    }

    public function apply()
    {
        $success = true;
        foreach ($this->paths as $path) {
            if (false === file_exists($path)) {
                if (false === @mkdir($path)) {
                    $this->errors[$path] = error_get_last();
                    $success = false;
                } else {
                    $this->errors[$path] = null;
                    $old = umask(0);
                    chmod($path, $this->dirmode);
                    umask($old);
                }
            }
        }

        return $success;
    }

    public function getSummary()
    {
        $pageHtml = '';
        $pageTitle = t('Directory Creation');
        $createMsg = t('The setup will create the following directories:');
        $existsMsg = t('The setup does not need to create the following already existing directories:');

        $toBeCreated = array_filter($this->paths, function ($p) { return false === file_exists($p); });
        if (false === empty($toBeCreated)) {
            $pageHtml .= '<p>' . $createMsg . '</p>';

            $pageHtml .= '<ul>';
            foreach ($toBeCreated as $path) {
                $pageHtml .= '<li>' . $path . '</li>';
            }
            $pageHtml .= '</ul>';
        }

        $existing = array_diff($this->paths, $toBeCreated);
        if (false === empty($existing)) {
            $pageHtml .= '<p>' . $existsMsg . '</p>';

            $pageHtml .= '<ul>';
            foreach ($existing as $path) {
                $pageHtml .= '<li>' . $path . '</li>';
            }
            $pageHtml .= '</ul>';
        }

        return '<h2>' . $pageTitle . '</h2>' . $pageHtml;
    }

    public function getReport()
    {
        $okMessage = t('Directory "%s" in "%s" has been successfully created.');
        $existsMessage = t('Directory "%s" does already exist in "%s". Nothing to do.');
        $failMessage = t('Unable to create directory "%s" in "%s". An error occured:');

        $report = '';
        foreach ($this->paths as $path) {
            if (array_key_exists($path, $this->errors)) {
                if (is_array($this->errors[$path])) {
                    $report .= '<p class="error">' . sprintf($failMessage, basename($path), dirname($path)) . '</p>'
                        . '<p>' . $this->errors[$path]['message'] . '</p>';
                } else {
                    $report .= '<p>' . sprintf($okMessage, basename($path), dirname($path)) . '</p>';
                }
            } else {
                $report .= '<p>' . sprintf($existsMessage, basename($path), dirname($path)) . '</p>';
            }
        }

        return $report;
    }
}
