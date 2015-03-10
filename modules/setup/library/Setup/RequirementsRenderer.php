<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use RecursiveIteratorIterator;

class RequirementsRenderer extends RecursiveIteratorIterator
{
    public function beginIteration()
    {
        $this->tags[] = '<table class="requirements">';
        $this->tags[] = '<tbody>';
    }

    public function endIteration()
    {
        $this->tags[] = '</tbody>';
        $this->tags[] = '</table>';
    }

    public function beginChildren()
    {
        $this->tags[] = '<tr>';
        $currentSet = $this->getSubIterator();
        $state = $currentSet->getState() ? 'fulfilled' : (
            $currentSet->isOptional() ? 'not-available' : 'missing'
        );
        $colSpanRequired = $this->hasSingleRequirements($this->getSubIterator($this->getDepth() - 1));
        $this->tags[] = '<td class="set-state ' . $state . '"' . ($colSpanRequired ? ' colspan=3' : '') . '>';
        $this->beginIteration();
    }

    public function endChildren()
    {
        $this->endIteration();
        $this->tags[] = '</td>';
        $this->tags[] = '</tr>';
    }

    protected function hasSingleRequirements(RequirementSet $requirements)
    {
        $set = $requirements->getAll();
        foreach ($set as $entry) {
            if ($entry instanceof Requirement) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        foreach ($this as $requirement) {
            $this->tags[] = '<tr>';
            $this->tags[] = '<td class="title"><h2>' . $requirement->getTitle() . '</h2></td>';
            $this->tags[] = '<td class="desc">';
            $descriptions = $requirement->getDescriptions();
            if (count($descriptions) > 1) {
                $this->tags[] = '<ul>';
                foreach ($descriptions as $d) {
                    $this->tags[] = '<li>' . $d . '</li>';
                }
                $this->tags[] = '</ul>';
            } elseif (! empty($descriptions)) {
                $this->tags[] = $descriptions[0];
            }
            $this->tags[] = '</td>';
            $this->tags[] = '<td class="state ' . ($requirement->getState() ? 'fulfilled' : (
                $requirement->isOptional() ? 'not-available' : 'missing'
            )) . '">' . $requirement->getStateText() . '</td>';
            $this->tags[] = '</tr>';
        }

        return implode("\n", $this->tags);
    }

    public function __toString()
    {
        return $this->render();
    }
}
