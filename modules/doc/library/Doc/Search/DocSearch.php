<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Search;

/**
 * Search documentation for a given search string
 */
class DocSearch
{
    /**
     * Search string
     *
     * @var string
     */
    protected $input;

    /**
     * Search criteria
     *
     * @var array
     */
    protected $search;

    /**
     * Create a new doc search from the given search string
     *
     * @param string $search
     */
    public function __construct($search)
    {
        $this->input = $search = (string) $search;
        $criteria = array();
        if (preg_match_all('/"(?P<search>[^"]*)"/', $search, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $unquoted = array();
            $offset = 0;
            foreach ($matches as $match) {
                $fullMatch = $match[0];
                $searchMatch = $match['search'];
                $unquoted[] = substr($search, $offset, $fullMatch[1] - $offset);
                $offset = $fullMatch[1] + strlen($fullMatch[0]);
                if (strlen($searchMatch[0]) > 0) {
                    $criteria[] = $searchMatch[0];
                }
            }
            $unquoted[] = substr($search, $offset);
            $search = implode(' ', $unquoted);
        }
        $this->search = array_map(
            'strtolower',
            array_unique(array_merge($criteria, array_filter(explode(' ', trim($search)))))
        );
    }

    /**
     * Get the search criteria
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->search;
    }

    /**
     * Get the search string
     *
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Search in the given line
     *
     * @param   string  $line
     *
     * @return  DocSearchMatch|null
     */
    public function search($line)
    {
        $match = new DocSearchMatch();
        $match->setLine($line);
        foreach ($this->search as $criteria) {
            $offset = 0;
            while (($position = stripos($line, $criteria, $offset)) !== false) {
                $match->appendMatch(substr($line, $position, strlen($criteria)), $position);
                $offset = $position + 1;
            }
        }
        return $match->isEmpty() ? null : $match;
    }
}
