<?php

namespace Icinga\Common;

trait DataExtractor
{
    /**
     * Extract data from array to this class's properties Unknown properties (no matching setter) are ignored
     *
     * @param array $data
     *
     * @return $this
     */
    public function fromArray(array $data)
    {
        foreach ($data as $name => $value) {
            $func = 'set' . ucfirst($name);
            if (method_exists($this, $func)) {
                $this->$func($value);
            }
        }

        return $this;
    }

    /**
     * Get this class's structure as array
     *
     * Stringifies the attrs or set to null if it doesn't have a value, when $stringify is true
     *
     * @param bool $stringify Whether, the attributes should be returned unmodified
     *
     * @return array
     */
    public function toArray($stringify = true)
    {
        return [];
    }
}
