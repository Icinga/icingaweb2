<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Hook;

use ErrorException;
use Exception;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;

/**
 * Base class for ticket hooks
 *
 * Extend this class if you want to integrate your ticketing solution Icinga Web 2
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
     * Get the pattern
     *
     * @return string
     */
    abstract public function getPattern();

    /**
     * Create a link for each matched element in the subject text
     *
     * @param   array $match    Array of matched elements according to {@link getPattern()}
     *
     * @return  string          Replacement string
     */
    abstract public function createLink($match);

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

        return $text;
    }
}
