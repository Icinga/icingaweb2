<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup;

use ArrayIterator;
use IteratorAggregate;
use Icinga\Exception\InstallException;

/**
 * Container for multiple installation steps
 */
class Installer implements IteratorAggregate
{
    protected $steps;

    protected $state;

    public function __construct()
    {
        $this->steps = array();
    }

    public function getIterator()
    {
        return new ArrayIterator($this->getSteps());
    }

    public function addStep(Step $step)
    {
        $this->steps[] = $step;
    }

    public function addSteps(array $steps)
    {
        foreach ($steps as $step) {
            $this->addStep($step);
        }
    }

    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Run the installation and return whether it succeeded
     *
     * @return  bool
     */
    public function run()
    {
        $this->state = true;

        try {
            foreach ($this->steps as $step) {
                $this->state &= $step->apply();
            }
        } catch (InstallException $e) {
            $this->state = false;
        }

        return $this->state;
    }

    /**
     * Return a summary of all actions designated to run
     *
     * @return  array       An array of HTML strings
     */
    public function getSummary()
    {
        $summaries = array();
        foreach ($this->steps as $step) {
            $summaries[] = $step->getSummary();
        }

        return $summaries;
    }

    /**
     * Return a report of all actions that were run
     *
     * @return  array       An array of HTML strings
     */
    public function getReport()
    {
        $reports = array();
        foreach ($this->steps as $step) {
            $reports[] = $step->getReport();
        }

        return $reports;
    }
}
