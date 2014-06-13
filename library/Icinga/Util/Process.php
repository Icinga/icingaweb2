<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

use RuntimeException;

/**
 * Process
 *
 * A class to easily create child processes and handle in-/output capabilities.
 */
class Process
{
    /**
     * The resource representing the process
     *
     * @var resource
     */
    protected $resource;

    /**
     * The pipes in use by the process
     *
     * @var array
     */
    protected $pipes = array();

    /**
     * The returncode of the process
     *
     * @var int
     */
    protected $returnCode;

    /**
     * Create a new process
     *
     * @see     Process::start()
     */
    protected function __construct($cmd, $cwd = null, $stdout = null, $stderr = null, $stdin = null, $env = array())
    {
        $descriptorSpec = array();
        if ($stdin !== null) {
            $descriptorSpec[0] = $stdin === 'pipe' ? array('pipe', 'r') : (
                is_string($stdin) ? array('file', $stdin, 'r') : $stdin
            );
        }
        if ($stdout !== null) {
            $descriptorSpec[1] = $stdout === 'pipe' ? array('pipe', 'w') : (
                is_string($stdout) ? array('file', $stdout, 'a') : $stdout
            );
        }
        if ($stderr !== null) {
            $descriptorSpec[2] = $stderr === 'pipe' ? array('pipe', 'w') : (
                is_string($stderr) ? array('file', $stderr, 'a') : $stderr
            );
        }

        $this->resource = proc_open(
            $cmd,
            $descriptorSpec,
            $this->pipes,
            $cwd,
            $env
        );

        if (!is_resource($this->resource)) {
            throw new RuntimeException("Cannot start process: $cmd");
        }
    }

    /**
     * Start and return a new process
     *
     * @param   string  $cmd        The command to start the process
     * @param   string  $cwd        The working directory of the new process (Must be an absolute path or null)
     * @param   string  $stdout     A filedescriptor, "pipe" or a filepath
     * @param   string  $stderr     A filedescriptor, "pipe" or a filepath
     * @param   string  $stdin      A filedescriptor, "pipe" or a filepath
     * @param   array   $env        The environment variables (Must be an array or null)
     *
     * @return  Process
     *
     * @throws  RuntimeException    When the process could not be started
     */
    public static function start($cmd, $cwd = null, $stdout = null, $stderr = null, $stdin = null, $env = array())
    {
        return new static($cmd, $cwd, $stdout, $stderr, $stdin, $env);
    }

    /**
     * Interact with process
     *
     * Send data to stdin. Read data from stdout and stderr, until end-of-file is reached.
     * Wait for process to terminate. The optional input argument should be a string to be
     * sent to the child process, or null, if no data should be sent to the child.
     *
     * Note that you need to pass the equivalent pipes to the constructor for this to work.
     *
     * @param   string  $input  Data to send to the child.
     *
     * @return  array           The data from stdout and stderr.
     */
    public function communicate($input = null)
    {
        if (!isset($this->pipes[1]) && !isset($this->pipes[2])) {
            $this->wait();
            return array();
        }

        $read = $write = array();
        if ($input !== null && isset($this->pipes[0])) {
            $write[] = $this->pipes[0];
        }
        if (isset($this->pipes[1])) {
            $read[] = $this->pipes[1];
        }
        if (isset($this->pipes[2])) {
            $read[] = $this->pipes[2];
        }

        $stdout = $stderr = '';
        $readToWatch = $read;
        $writeToWatch = $write;
        $exceptToWatch = array();
        while (stream_select($readToWatch, $writeToWatch, $exceptToWatch, 0, 20000) !== false) {
            if (!empty($writeToWatch) && $input) {
                $input = substr($input, fwrite($writeToWatch[0], $input));
            }
            foreach ($readToWatch as $pipe) {
                if (isset($this->pipes[1]) && $pipe === $this->pipes[1]) {
                    $stdout .= stream_get_contents($pipe);
                } elseif (isset($this->pipes[2]) && $pipe === $this->pipes[2]) {
                    $stderr .= stream_get_contents($pipe);
                }
            }

            $readToWatch = array_filter($read, function ($h) { return !feof($h); });
            $writeToWatch = $input ? $write : array();
            if (empty($readToWatch) && empty($writeToWatch)) {
                break;
            }
        }

        $this->wait(); // To ensure the process is actually stopped when calling cleanUp() we utilize wait()
        return array($stdout, $stderr);
    }

    /**
     * Return whether the process is still alive and set the returncode
     *
     * @return  bool
     */
    public function poll()
    {
        if ($this->resource !== null) {
            $info = @proc_get_status($this->resource);
            if ($info !== false) {
                if ($info['running']) {
                    return true;
                } elseif ($info['exitcode'] !== -1) {
                    $this->returnCode = $info['exitcode'];
                }
            }
        }

        return false;
    }

    /**
     * Wait for process to terminate and return its returncode
     *
     * @return  int
     */
    public function wait()
    {
        if ($this->returnCode === null && $this->resource !== null) {
            while ($this->poll()) {
                usleep(50000);
            }
            $this->cleanUp();
        }

        return $this->returnCode;
    }

    /**
     * Cleanup the process resource and its associated pipes
     */
    protected function cleanUp()
    {
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        proc_close($this->resource);
        $this->resource = null;
    }
}
