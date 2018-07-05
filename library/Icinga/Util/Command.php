<?php

namespace Icinga\Util;

use Icinga\Exception\IcingaException;

class Command
{
    /**
     * Stream select timeout in microseconds
     *
     * @var int
     */
    const TIMEOUT = 20000;

    /**
     * Arguments
     *
     * @var array
     */
    protected $arguments = array();

    /**
     * Command to execute
     *
     * @var string
     */
    protected $command;

    /**
     * Initial working directory of the command, defaults to the current PHP process' working dir
     *
     * @var string|null
     */
    protected $cwd = null;

    /**
     * Environment variables for the command, defaults to the same environment as the current PHP process
     *
     * @var array|null
     */
    protected $env = null;

    /**
     * Exit code of the command
     *
     * @var int
     */
    protected $exitCode;

    /**
     * Options
     *
     * @var array
     */
    protected $options;

    /**
     * Indexed array of file pointers
     *
     * @var array
     */
    protected $pipes;

    /**
     * Process resource
     *
     * @var resource
     */
    protected $resource;

    /**
     * Create a new command
     *
     * @param   string  $command    The command to execute
     * @param   bool    $escape Whether to escape the command
     */
    public function __construct($command, $escape = true)
    {
        $this->command = (bool) $escape ? escapeshellcmd((string) $command) : (string) $command;
    }

    /**
     * Get the command to execute
     *
     * @return string
     */
    public function getCommand()
    {
        $command = $this->command;
        if (! empty($this->options)) {
            $command .= ' ' . implode(' ', $this->options);
        }
        if (! empty($this->arguments)) {
            $command .= ' ' . implode(' ', $this->arguments);
        }
        return $command;
    }

    /**
     * Get the initial working directory of the command
     *
     * @return string|null
     */
    public function getCwd()
    {
        return $this->cwd;
    }

    /**
     * Set the initial working directory of the command
     *
     * @param   string $cwd
     *
     * @return  $this
     */
    public function setCwd($cwd)
    {
        $this->cwd = (string) $cwd;
        return $this;
    }

    /**
     * Get the environment variables for the command
     *
     * @return array|null
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Set the environment variables for the command
     *
     * @param   array $env
     *
     * @return  $this
     */
    public function setEnv(array $env)
    {
        $this->env = $env;
        return $this;
    }

    /**
     * Get the exit code of the command
     *
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * Get the status of the command
     *
     * @return object
     */
    public function getStatus()
    {
        $status = (object) proc_get_status($this->resource);
        if ($status->running === false
            && $this->exitCode === null
        ) {
            // The exit code is only valid the first time proc_get_status is
            // called in terms of running false, hence we capture it
            $this->exitCode = $status->exitcode;
        }
        return $status;
    }

    /**
     * @param   string $value
     *
     * @return  $this
     */
    public function arg($value)
    {
        $this->arguments[] = escapeshellarg((string) $value);
        return $this;
    }

    /**
     * Close the command
     *
     * @return $this
     */
    public function close()
    {
        if ($this->resource !== null) {
            fclose($this->pipes[1]);
            fclose($this->pipes[2]);
            $exitCode = proc_close($this->resource);
            if ($this->exitCode === null) {
                $this->exitCode = $exitCode;
            }
            $this->resource = null;
        }
        return $this;
    }

    public function execute()
    {
        if ($this->resource !== null) {
            throw new IcingaException('');
        }
        $descriptors = array(
            0   => array('pipe', 'r'),
            1  => array('pipe', 'w'),
            2  => array('pipe', 'w')
        );
        $resource = proc_open(
            $this->getCommand(),
            $descriptors,
            $this->pipes,
            $this->getCwd(),
            $this->getEnv()
        );
        if (! is_resource($resource)) {
            throw new IcingaException('Can\'t fork');
        }
        // Set STDOUT and STDERR to non-blocking
        //stream_set_blocking($this->pipes[1], 0);
        //stream_set_blocking($this->pipes[2], 0);
        $this->resource = $resource;
        return $this;
    }

    /**
     * Listen for output until the command terminates
     *
     * @return array
     */
    public function listen()
    {
        fclose($this->pipes[0]);
        $readBuffer = array($this->pipes[1], $this->pipes[2]);
        $write = null;
        $except = null;
        $stdout = '';
        $stderr = '';
        if (! empty($readBuffer)) {
            if (isset($readBuffer[0])) {
                $stdout .= stream_get_contents($readBuffer[0]);
            }
            if (isset($readBuffer[1])) {
                $stderr .= stream_get_contents($readBuffer[1]);
            }
        }
        return array(
            'stdout' => $stdout,
            'stderr' => $stderr
        );
    }

    /**
     * TBD
     *
     * @param   string $thoughts
     *
     * @return  $this
     */
    public function tell($thoughts)
    {
        fwrite($this->pipes[0], (string) $thoughts);
        return $this;
    }

    /**
     * @param   string  $name
     * @param   string  $value
     * @param   bool    $longOpt
     * @param   string  $separator
     *
     * @return  $this
     */
    public function option($name, $value = '', $longOpt = false, $separator = '')
    {
        $this->options[] = escapeshellarg(
            ((bool) $longOpt ? '--' : '-') . (string) $name . (string) $separator . (string) $value
        );
        return $this;
    }

    /**
     * Destroy the command
     */
    public function __destruct()
    {
        $this->close();
    }
}
