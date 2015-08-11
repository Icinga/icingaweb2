<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Search;

use UnexpectedValueException;
use Icinga\Application\Icinga;
use Icinga\Web\View;

/**
 * A doc search match
 */
class DocSearchMatch
{
    /**
     * CSS class for highlighting matches
     *
     * @var string
     */
    const HIGHLIGHT_CSS_CLASS = 'search-highlight';

    /**
     * Header match
     *
     * @var int
     */
    const MATCH_HEADER = 1;

    /**
     * Content match
     *
     * @var int
     */
    const MATCH_CONTENT = 2;

    /**
     * Line
     *
     * @var string
     */
    protected $line;

    /**
     * Line number
     *
     * @var int
     */
    protected $lineno;

    /**
     * Type of the match
     *
     * @var int
     */
    protected $matchType;

    /**
     * Matches
     *
     * @var array
     */
    protected $matches = array();

    /**
     * View
     *
     * @var View|null
     */
    protected $view;

    /**
     * Set the line
     *
     * @param   string  $line
     *
     * @return  $this
     */
    public function setLine($line)
    {
        $this->line = (string) $line;
        return $this;
    }

    /**
     * Get the line
     *
     * @return string
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Set the line number
     *
     * @param   int $lineno
     *
     * @return  $this
     */
    public function setLineno($lineno)
    {
        $this->lineno = (int) $lineno;
        return $this;
    }

    /**
     * Set the match type
     *
     * @param   int $matchType
     *
     * @return  $this
     */
    public function setMatchType($matchType)
    {
        $matchType = (int) $matchType;
        if ($matchType !== static::MATCH_HEADER && $matchType !== static::MATCH_CONTENT) {
            throw new UnexpectedValueException();
        }
        $this->matchType = $matchType;
        return $this;
    }

    /**
     * Get the match type
     *
     * @return int
     */
    public function getMatchType()
    {
        return $this->matchType;
    }

    /**
     * Append a match
     *
     * @param   string  $match
     * @param   int     $position
     *
     * @return  $this
     */
    public function appendMatch($match, $position)
    {
        $this->matches[(int) $position] = (string) $match;
        return $this;
    }

    /**
     * Get the matches
     *
     * @return array
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     * Set the view
     *
     * @param   View    $view
     *
     * @return  $this
     */
    public function setView(View $view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Get the view
     *
     * @return View
     */
    public function getView()
    {
        if ($this->view === null) {
            $this->view = Icinga::app()->getViewRenderer()->view;
        }
        return $this->view;
    }

    /**
     * Get the line having matches highlighted
     *
     * @return string
     */
    public function highlight()
    {
        $highlighted = '';
        $offset = 0;
        $matches = $this->getMatches();
        ksort($matches);
        foreach ($matches as $position => $match) {
            $highlighted .= $this->getView()->escape(substr($this->line, $offset, $position - $offset))
                . '<span class="' . static::HIGHLIGHT_CSS_CLASS .'">'
                . $this->getView()->escape($match)
                . '</span>';
            $offset = $position + strlen($match);
        }
        $highlighted .= $this->getView()->escape(substr($this->line, $offset));
        return $highlighted;
    }

    /**
     * Whether the match is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->matches);
    }
}
