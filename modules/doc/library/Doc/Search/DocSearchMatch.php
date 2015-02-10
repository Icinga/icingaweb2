<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Search;

use UnexpectedValueException;

/**
 * A doc search match
 */
class DocSearchMatch
{
    /**
     * Header match
     *
     * @type int
     */
    const MATCH_HEADER = 1;

    /**
     * Content match
     *
     * @type int
     */
    const MATCH_CONTENT = 2;

    /**
     * Line
     *
     * @type string
     */
    protected $line;

    /**
     * Line number
     *
     * @type int
     */
    protected $lineno;

    /**
     * Type of the match
     *
     * @type int
     */
    protected $matchType;

    /**
     * Matches
     *
     * @type array
     */
    protected $matches = array();

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
     * Whether the match is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->matches);
    }
}
