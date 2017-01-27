<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\File;

use Icinga\Util\EnumeratingFilterIterator;
use Icinga\Util\File;

/**
 * Class FileIterator
 *
 * Iterate over a file, yielding only fields of non-empty lines which match a PCRE expression
 */
class FileIterator extends EnumeratingFilterIterator
{
    /**
     * A PCRE string with the fields to extract from the file's lines as named subpatterns
     *
     * @var string
     */
    protected $fields;

    /**
     * An associative array of the current line's fields ($field => $value)
     *
     * @var array
     */
    protected $currentData;

    public function __construct($filename, $fields)
    {
        $this->fields = $fields;
        $f = new File($filename);
        $f->setFlags(
            File::DROP_NEW_LINE |
            File::READ_AHEAD |
            File::SKIP_EMPTY
        );
        parent::__construct($f);
    }

    /**
     * Return the current data
     *
     * @return array
     */
    public function current()
    {
        return $this->currentData;
    }

    /**
     * Accept lines matching the given PCRE pattern
     *
     * @return bool
     *
     * @throws FileReaderException  If PHP failed parsing the PCRE pattern
     */
    public function accept()
    {
        $data = array();
        $matched = preg_match(
            $this->fields,
            $this->getInnerIterator()->current(),
            $data
        );

        if ($matched === false) {
            throw new FileReaderException('Failed parsing regular expression!');
        } elseif ($matched === 1) {
            foreach ($data as $key => $value) {
                if (is_int($key)) {
                    unset($data[$key]);
                }
            }
            $this->currentData = $data;
            return true;
        }
        return false;
    }
}
