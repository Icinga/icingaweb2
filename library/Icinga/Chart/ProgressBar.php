<?php

namespace Icinga\Chart;

use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

class BubblePosition
{
    const START = '0%';
    const MIDDLE = '50%';
    const END = '84%';
}

/**
 * Can render an SVG progress bar, made up of sections. The sections are calculated to always
 * add up to 100%.
 *
 *  Progress bar with 3 sections
 * ```
 *                  ---
 *  ---------------/###\---------------
 *  |          |  |#####|  |          |
 *  ---------------\###/---------------
 *      / \         ---
 *       |          / \
 *    Section        |
 *                 Bubble
 * ```
 *
 * Sections can be selected and marked as complete or visited. More documentation can be found
 * on the relative methods.
 *
 * Progress bars can also have a bubble, most commonly used in the center, but the position can be changed.
 * The bubble is overlaid over the progress bar, when too many sections are used, the bubble might conceal sections.
 */
class ProgressBar extends HtmlElement
{
    protected $tag = 'svg';

    /** @var int The height of the SVG */
    protected $height;

    /** @var bool Whether bubble (circle) element should be added */
    protected $withBubble = false;

    /** @var string The position of the bubble in % */
    protected $bubblePosition;

    /** @var int The number of section in the progress bar */
    protected $sections = 1;

    /** @var int The last visited section. All Previous sections will also be colored as visited, if not complete */
    protected $visitedSections = 0;

    /** @var int The last complete section. All Previous sections will also be colored as complete */
    protected $completedSections = 0;

    /** @var int The selected session. Used for `self::markComplete()` and `self::markVisited()`, if not overwrite is specified */
    protected $selectedSection = 1;

    public function __construct(array $options = null, int $height = 20)
    {
        parent::__construct($this->tag, Attributes::wantAttributes($options));

        $this->height = $height;
    }

    /**
     * Sets option on the `svg` element
     *
     * @param array $options
     * @param int $height
     *
     * @return static
     */
    public static function withOptions(array $options, int $height = 20): self
    {
        return new static($options, $height);
    }

    /**
     * Adds a section
     *
     * @return $this
     */
    public function addSection(): self
    {
        $this->sections += 1;

        return $this;
    }

    /**
     * Set the selected section, used in `self::markVisited()` and `self::markComplete()`,
     * if no overwrite is specified
     *
     * @param int $section
     *
     * @return $this
     */
    public function selectSection(int $section): self
    {
        $this->selectedSection = $section;

        return $this;
    }

    /**
     * Set the sections to a specific number
     *
     * @param int $sections
     *
     * @return $this
     */
    public function setSections(int $sections): self
    {
        $this->sections = $sections;

        return $this;
    }

    /**
     * Marks the currently selected session or the provided overwrite as the last visited section.
     *
     * @param int|null $sectionOverwrite
     *
     * @return $this
     */
    public function markVisited(int $sectionOverwrite = null): self
    {
        if ($sectionOverwrite === null) {
            $this->visitedSections = $this->selectedSection;
        } else {
            $this->visitedSections = $sectionOverwrite;
        }

        return $this;
    }

    /**
     * Marks the currently selected session or the provided overwrite as the last complete section.
     *
     * @param int|null $sectionOverwrite
     *
     * @return $this
     */
    public function markComplete(int $sectionOverwrite = null): self
    {
        if ($sectionOverwrite === null) {
            $this->completedSections = $this->selectedSection;
        } else {
            $this->completedSections = $sectionOverwrite;
        }

        return $this;
    }

    /**
     * Whether the progress bar should have a circle, and it's position
     *
     * @param bool $useBubble
     * @param string $position
     *
     * @return $this
     */
    public function useBubble(bool $useBubble, string $position = BubblePosition::MIDDLE): self
    {
        $this->withBubble = $useBubble;
        $this->bubblePosition = $position;

        return $this;
    }

    /**
     * Returns the rounded corner radius for the given section
     *
     * @param int $index
     *
     * @return int
     */
    private function getRoundedCorner(int $index): int
    {
        // Return 5 for the first and last section, 0 for the rest
        if ($index === 0 || $index === $this->sections - 1) {
            return 5;
        }

        return 0;
    }

    protected function assemble()
    {
        $okColor = '#44bb77';
        $unvisited = 'white';

        $rectWidth = (1 / $this->sections) * 100 + 0.5;
        $group = Html::tag('g');

        for ($i = 0; $i < $this->sections; $i++) {
            if ($i === $this->sections - 1) {
                // Don't add extra width on the last section, otherwise the rounded corner is cut off.
                $rectWidth -= 0.5;
            }

            $line = Html::tag(
                'rect',
                [
                    'x'      => ($i / $this->sections) * 100 . '%',
                    'y'      => $this->height . '%',
                    'height' => '15%',
                    'width'  => $rectWidth . '%',
                    'fill'   => $i + 1 <= $this->completedSections ? $okColor : $unvisited,
                    'rx'     => $this->getRoundedCorner($i),
                ]
            );

            $group->add($line);
        }

        if ($this->withBubble) {
            $bubble = Html::tag(
                'circle',
                [
                    'cx'   => $this->bubblePosition,
                    'cy'   => $this->height + 8 . '%',
                    'r'    => 8.5,
                    'fill' => 'lightgrey'
                ]
            );
            $group->add($bubble);
        }

        $this->add($group);
    }
}