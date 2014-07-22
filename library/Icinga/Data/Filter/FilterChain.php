<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Filter;

use Icinga\Exception\ProgrammingError;

/**
 * FilterChain
 *
 * A FilterChain contains a list ...
 */
abstract class FilterChain extends Filter
{
    protected $filters = array();

    protected $operatorName;

    protected $operatorSymbol;

    public function hasId($id)
    {
        foreach ($this->filters() as $filter) {
            if ($filter->hasId($id)) {
                return true;
            }
        }
        return parent::hasId($id);
    }

    public function getById($id)
    {
        foreach ($this->filters() as $filter) {
            if ($filter->hasId($id)) {
                return $filter->getById($id);
            }
        }
        return parent::getById($id);
    }

    public function removeId($id)
    {
        if ($id === $this->getId()) {
            $this->filters = array();
            return $this;
        }
        $remove = null;
        foreach ($this->filters as $key => $filter) {
            if ($filter->getId() === $id) {
                $remove = $key;
            } elseif ($filter instanceof FilterChain) {
                $filter->removeId($id);
            }
        }
        if ($remove !== null) {
            unset($this->filters[$remove]);
            $this->filters = array_values($this->filters);
        }
        $this->refreshChildIds();
        return $this;
    }

    public function replaceById($id, $filter)
    {
        $found = false;
        foreach ($this->filters as $k => $child) {
            if ($child->getId() == $id) {
                $this->filters[$k] = $filter;
                $found = true;
                break;
            }
            if ($child->hasId($id)) {
                $child->replaceById($id, $filter);
                $found = true;
                break;
            }
        }
        if (! $found) {
            throw new ProgrammingError('You tried to replace an unexistant child filter');
        }
        $this->refreshChildIds();
        return $this;
    }

    protected function refreshChildIds()
    {
        $i = 0;
        $id = $this->getId();
        foreach ($this->filters as $filter) {
            $i++;
            $filter->setId($id . '-' . $i);
        }
        return $this;
    }

    public function setId($id)
    {
        return parent::setId($id)->refreshChildIds();
    }

    public function getOperatorName()
    {
        return $this->operatorName;
    }

    public function setOperatorName($name)
    {
        if ($name !== $this->operatorName) {
            return Filter::chain($name, $this->filters);
        }
        return $this;
    }

    public function getOperatorSymbol()
    {
        return $this->operatorSymbol;
    }

    public function toQueryString()
    {
        $parts = array();
        if (empty($this->filters)) {
            return '';
        }
        foreach ($this->filters() as $filter) {
            $parts[] = $filter->toQueryString();
        }

        // TODO: getLevel??
        if (strpos($this->getId(), '-')) {
            return '(' . implode($this->getOperatorSymbol(), $parts) . ')';
        } else {
            return implode($this->getOperatorSymbol(), $parts);
        }
    }

    /**
     * Get simple string representation
     *
     * Useful for debugging only
     *
     * @return string 
     */
    public function __toString()
    {
        if (empty($this->filters)) {
            return '';
        }
        $parts = array();
        foreach ($this->filters as $filter) {
            if ($filter instanceof FilterChain) {
                $parts[] = '(' . $filter . ')';
            } else {
                $parts[] = (string) $filter;
            }
        }
        $op = ' '  . $this->getOperatorSymbol() . ' ';
        return implode($op, $parts);
    }

    public function __construct($filters = array())
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }
    }

    public function isEmpty()
    {
        return empty($this->filters);
    }

    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
        $filter->setId($this->getId() . '-' . (count($this->filters)));
    }

    public function &filters()
    {
        return $this->filters;
    }

    public function __clone()
    {
        foreach ($this->filters as & $filter) {
            $filter = clone $filter;
        }
    } 
}
