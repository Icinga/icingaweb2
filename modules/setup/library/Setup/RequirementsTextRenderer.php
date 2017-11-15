<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use RecursiveIteratorIterator;
use Traversable;

class RequirementsTextRenderer extends RecursiveIteratorIterator
{
    protected $lines = array();

    protected $isTTY;

    /** @var RequirementSet */
    protected $root;

    const FG_BLACK = '30';

    const BG_LIGHT_GRAY = '47';
    const BG_LIGHT_GREEN = '102';
    const BG_LIGHT_YELLOW = '103';
    const BG_LIGHT_RED = '101';

    public function __construct(Traversable $iterator, $mode = self::LEAVES_ONLY, $flags = 0)
    {
        $this->root = $iterator;
        parent::__construct($iterator, $mode, $flags);
    }

    public function render()
    {
        foreach ($this as $requirement) {
            $this->addLine($this->bold('[ ' . $requirement->getTitle() . ' ]'));
            $descriptions = $requirement->getDescriptions();
            if ($descriptions !== null) {
                foreach ($descriptions as $d) {
                    $this->addLineWrapped($d);
                }
            }


            $this->addLine();
            $this->addLine($this->getStateBadge($requirement) . ' ' . $requirement->getStateText());
            $this->addLine(str_repeat('=', 80));
        }

        $this->addLine('Overall: %s', $this->getStateBadge($this->root));
        return implode("\n", $this->lines) . "\n";
    }

    protected function addLine()
    {
        $args = func_get_args();
        $format = array_shift($args);
        if ($format === null) {
            $this->lines[] = '';
        } else {
            $this->lines[] = vsprintf($format, $args);
        }
    }

    protected function addLineWrapped()
    {
        $args = func_get_args();
        $args[0] = wordwrap($args[0], 80);
        call_user_func_array(array($this, 'addLine'), $args);
    }

    protected function bold($t)
    {
        if ($this->isTTY()) {
            return sprintf("\033[1m%s\033[0m", $t);
        } else {
            return $t;
        }
    }

    protected function color($t, $fg = self::FG_BLACK, $bg = self::BG_LIGHT_GRAY)
    {
        if ($this->isTTY()) {
            return sprintf("\e[%sm\e[%sm%s\e[39m\e[49m", $fg, $bg, $t);
        } else {
            return $t;
        }
    }

    /**
     * @param RequirementSet|Requirement $requirement
     *
     * @return string
     */
    protected function getStateBadge($requirement)
    {
        $fg = self::FG_BLACK;
        if ($requirement instanceof RequirementSet && $requirement->fulfilled()) {
            $stateText = 'OK';
            $bg = self::BG_LIGHT_GREEN;
        } elseif ($requirement instanceof Requirement && $requirement->getState()) {
            $stateText = 'OK';
            $bg = self::BG_LIGHT_GREEN;
        } elseif ($requirement->isOptional()) {
            $stateText = 'WARN';
            $bg = self::BG_LIGHT_YELLOW;
        } else {
            $stateText = 'ERROR';
            $bg = self::BG_LIGHT_RED;
        }

        return $this->color($this->bold('[ ' . $stateText . ' ]'), $fg, $bg);
    }

    protected function isTTY()
    {
        if ($this->isTTY === null) {
            if (function_exists('posix_isatty')) {
                $this->isTTY = posix_isatty(0);
            } else {
                $this->isTTY = false;
            }
        }

        return $this->isTTY;
    }

    public function __toString()
    {
        return $this->render();
    }
}
