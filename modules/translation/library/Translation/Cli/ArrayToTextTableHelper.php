<?php

namespace Icinga\Module\Translation\Cli;

/**
 * Array to Text Table Generation Class
 *
 * @author    Tony Landis <tony@tonylandis.com>
 * @link      http://www.tonylandis.com/
 * @copyright Copyright (C) 2006-2009 Tony Landis
 * @license   http://www.opensource.org/licenses/bsd-license.php
 */
class ArrayToTextTableHelper
{
    /**
     * @var array The array for processing
     */
    protected $rows;

    /**
     * @var int The column width settings
     */
    protected $cs = array();

    /**
     * @var int The Row lines settings
     */
    protected $rs = array();

    /**
     * @var int The Column index of keys
     */
    protected $keys = array();

    /**
     * @var int Max Column Height (returns)
     */
    protected $mH = 2;

    /**
     * @var int Max Row Width (chars)
     */
    protected $mW = 30;

    protected $head = false;
    protected $pcen = "+";
    protected $prow = "-";
    protected $pcol = "|";


    /**
     * Prepare array into textual format
     *
     * @param array $rows      The input array
     * @param bool  $head      Show heading
     * @param int   $maxWidth  Max Column Height (returns)
     * @param int   $maxHeight Max Row Width (chars)
     */
    public function __construct($rows)
    {
        $this->rows =& $rows;
        $this->cs = array();
        $this->rs = array();

        if (! $xc = count($this->rows)) {
            return false;
        }

        $this->keys = array_keys($this->rows[0]);
        $columns = count($this->keys);

        for ($x = 0; $x < $xc; $x++) {
            for ($y = 0; $y < $columns; $y++) {
                $this->setMax($x, $y, $this->rows[$x][$this->keys[$y]]);
            }
        }

        return $this;
    }

    /**
     * Show the headers using the key values of the array for the titles
     *
     * @param bool $bool
     */
    public function showHeaders($bool)
    {
        if ($bool) {
            $this->setHeading();
        }
    }

    /**
     * Set the maximum width (number of characters) per column before truncating
     *
     * @param int $maxWidth
     */
    public function setMaxWidth($maxWidth)
    {
        $this->mW = (int) $maxWidth;
    }

    /**
     * Set the maximum height (number of lines) per row before truncating
     *
     * @param int $maxHeight
     */
    public function setMaxHeight($maxHeight)
    {
        $this->mH = (int) $maxHeight;
    }

    /**
     * Prints the data to a text table
     *
     * @param bool $return Set to 'true' to return text rather than printing
     *
     * @return mixed
     */
    public function render($return = false)
    {
        if ($return) {
            ob_start(null, 0, true);
        }

        $this->printLine();
        $this->printHeading();

        $rc = count($this->rows);
        for ($i = 0; $i < $rc;
             $i++) {
            $this->printRow($i);
        }

        $this->printLine(false);

        if ($return) {
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }
        return null;
    }

    protected function setHeading()
    {
        $data = array();
        foreach ($this->keys as $colKey => $value) {
            $this->setMax(false, $colKey, $value);
            $data[$colKey] = strtoupper($value);
        }
        if (! is_array($data)) {
            return false;
        }
        $this->head = $data;

        return $this;
    }

    protected function printLine($nl = true)
    {
        print $this->pcen;
        foreach ($this->cs as $key => $val) {
            print $this->prow .
                str_pad('', $val, $this->prow, STR_PAD_RIGHT) .
                $this->prow .
                $this->pcen;
        }
        if ($nl) {
            print "\n";
        }
    }

    protected function printHeading()
    {
        if (! is_array($this->head)) {
            return false;
        }

        print $this->pcol;
        foreach ($this->cs as $key => $val) {
            print ' ' .
                str_pad($this->head[$key], $val, ' ', STR_PAD_BOTH) .
                ' ' .
                $this->pcol;
        }

        print "\n";
        $this->printLine();

        return $this;
    }

    protected function printRow($rowKey)
    {
        // loop through each line
        for ($line = 1; $line <= $this->rs[$rowKey]; $line++) {
            print $this->pcol;
            for ($colKey = 0; $colKey < count($this->keys); $colKey++) {
                print " ";
                print str_pad(
                    substr($this->rows[$rowKey][$this->keys[$colKey]], ($this->mW * ($line - 1)), $this->mW),
                    $this->cs[$colKey],
                    ' ',
                    STR_PAD_RIGHT
                );
                print " " . $this->pcol;
            }
            print  "\n";
        }
    }

    protected function setMax($rowKey, $colKey, &$colVal)
    {
        $w = mb_strlen($colVal);
        $h = 1;
        if ($w > $this->mW) {
            $h = ceil($w % $this->mW);
            if ($h > $this->mH) {
                $h = $this->mH;
            }
            $w = $this->mW;
        }

        if (! isset($this->cs[$colKey]) || $this->cs[$colKey] < $w) {
            $this->cs[$colKey] = $w;
        }

        if ($rowKey !== false && (! isset($this->rs[$rowKey]) || $this->rs[$rowKey] < $h)) {
            $this->rs[$rowKey] = $h;
        }
    }
}
