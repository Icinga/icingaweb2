<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

use ArrayIterator;
use ErrorException;
use Exception;
use Icinga\Application\Hook\Ticket\TicketPattern;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;

/**
 * Base class for ticket hooks
 *
 * Extend this class if you want to integrate your ticketing solution into Icinga Web 2.
 */
abstract class TicketHook
{
    /**
     * Last error, if any
     *
     * @var string|null
     */
    protected $lastError;

    /**
     * Create a new ticket hook
     *
     * @see init() For hook initialization.
     */
    final public function __construct()
    {
        $this->init();
    }

    /**
     * Overwrite this function for hook initialization, e.g. loading the hook's config
     */
    protected function init()
    {
    }

    /**
     * Create a link for each matched element in the subject text
     *
     * @param   array|TicketPattern $match  Matched element according to {@link getPattern()}
     *
     * @return  string                      Replacement string
     */
    abstract public function createLink($match);

    /**
     * Get the pattern(s) to search for
     *
     * Return an array of TicketPattern instances here to support multiple TTS integrations.
     *
     * @return string|TicketPattern[]
     */
    abstract public function getPattern();

    /**
     * Apply ticket patterns to the given text
     *
     * @param   string          $text
     * @param   TicketPattern[] $ticketPatterns
     *
     * @return  string
     */
    private function applyTicketPatterns($text, array $ticketPatterns)
    {
        $out = '';
        $start = 0;

        $iterator = new ArrayIterator($ticketPatterns);
        $iterator->rewind();

        while ($iterator->valid()) {
            $ticketPattern = $iterator->current();

            try {
                preg_match($ticketPattern->getPattern(), $text, $match, PREG_OFFSET_CAPTURE, $start);
            } catch (ErrorException $e) {
                $this->fail('Can\'t create ticket links: Pattern is invalid: %s', IcingaException::describe($e));
                $iterator->next();
                continue;
            }

            if (empty($match)) {
                $iterator->next();
                continue;
            }

            // Remove preg_offset from match for the ticket pattern
            $carry = array();
            array_walk($match, function ($value, $key) use (&$carry) {
                $carry[$key] = $value[0];
            }, $carry);
            $ticketPattern->setMatch($carry);

            $offsetLeft = $match[0][1];
            $matchLength = strlen($match[0][0]);

            $out .= substr($text, $start, $offsetLeft - $start);

            try {
                $out .= $this->createLink($ticketPattern);
            } catch (Exception $e) {
                $this->fail('Can\'t create ticket links: %s', IcingaException::describe($e));
                return $text;
            }

            $start = $offsetLeft + $matchLength;
        }

        $out .= substr($text, $start);

        return $out;
    }

    /**
     * Helper function to create a TicketPattern instance
     *
     * @param   string  $name           Name of the TTS integration
     * @param   string  $pattern        Ticket pattern
     *
     * @return  TicketPattern
     */
    protected function createTicketPattern($name, $pattern)
    {
        $ticketPattern = new TicketPattern();
        $ticketPattern
            ->setName($name)
            ->setPattern($pattern);
        return $ticketPattern;
    }

    /**
     * Set the hook as failed w/ the given message
     *
     * @param   string  $message    Error message or error format string
     * @param   mixed   ...$arg     Format string argument
     */
    private function fail($message)
    {
        $args = array_slice(func_get_args(), 1);
        $lastError = vsprintf($message, $args);
        Logger::debug($lastError);
        $this->lastError = $lastError;
    }

    /**
     * Get the last error, if any
     *
     * @return string|null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Create links w/ {@link createLink()} in the given text that matches to the subject from {@link getPattern()}
     *
     * In case of errors a debug message is recorded to the log and any subsequent call to {@link createLinks()} will
     * be a no-op.
     *
     * @param   string $text
     *
     * @return  string
     */
    final public function createLinks($text)
    {
        if ($this->lastError !== null) {
            return $text;
        }

        try {
            $pattern = $this->getPattern();
        } catch (Exception $e) {
            $this->fail('Can\'t create ticket links: Retrieving the pattern failed: %s', IcingaException::describe($e));
            return $text;
        }

        if (empty($pattern)) {
            $this->fail('Can\'t create ticket links: Pattern is empty');
            return $text;
        }

        if (is_array($pattern)) {
            $text = $this->applyTicketPatterns($text, $pattern);
        } else {
            try {
                $text = preg_replace_callback(
                    $pattern,
                    array($this, 'createLink'),
                    $text
                );
            } catch (ErrorException $e) {
                $this->fail('Can\'t create ticket links: Pattern is invalid: %s', IcingaException::describe($e));
                return $text;
            } catch (Exception $e) {
                $this->fail('Can\'t create ticket links: %s', IcingaException::describe($e));
                return $text;
            }
        }

        return $text;
    }
}
