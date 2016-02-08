<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use RecursiveIteratorIterator;

class RequirementsRenderer extends RecursiveIteratorIterator
{
    public function beginIteration()
    {
        $this->tags[] = '<ul class="requirements">';
    }

    public function endIteration()
    {
        $this->tags[] = '</ul>';
    }

    public function beginChildren()
    {
        $this->tags[] = '<li>';
        $currentSet = $this->getSubIterator();
        $state = $currentSet->getState() ? 'fulfilled' : ($currentSet->isOptional() ? 'not-available' : 'missing');
        $this->tags[] = '<ul class="set-state ' . $state . '">';
    }

    public function endChildren()
    {
        $this->tags[] = '</ul>';
        $this->tags[] = '</li>';
    }

    public function render()
    {
        foreach ($this as $requirement) {
            $this->tags[] = '<li class="clearfix">';
            $this->tags[] = '<div class="title"><h2>' . $requirement->getTitle() . '</h2></div>';
            $this->tags[] = '<div class="description">';
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
            $this->tags[] = '</div>';
            $this->tags[] = '<div class="state ' . ($requirement->getState() ? 'fulfilled' : (
                $requirement->isOptional() ? 'not-available' : 'missing'
            )) . '">' . $requirement->getStateText() . '</div>';
            $this->tags[] = '</li>';
        }

        return implode("\n", $this->tags);
    }

    public function __toString()
    {
        return $this->render();
    }
}
