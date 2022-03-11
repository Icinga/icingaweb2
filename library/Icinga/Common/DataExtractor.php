<?php

namespace Icinga\Common;

trait DataExtractor
{
    /**
     * Extract data from array to this class's properties
     *
     * Unknown properties (no matching setter) are ignored
     *
     * @param array $data
     *
     * @return $this
     */
    public function fromArray(array $data)
    {
        foreach ($data as $name => $value) {
            $func = 'set'. ucfirst($name);
            if (method_exists($this, $func)) {
                $this->$func($value);
            }
        }

        return $this;
    }

    /**
     * Get this class's structure as array
     *
     * @return array
     */
    public function toArray()
    {
        return [];
    }
}
