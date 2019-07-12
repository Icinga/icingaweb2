<?php
/* Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Forms\ConfigForm;

/**
 * Base class for config form event hooks
 */
abstract class ConfigFormEventsHook
{
    /** @var array Array of errors found while processing the form event hooks */
    private static $lastErrors = [];

    /**
     * isValid event hook
     *
     * Implement this method in order to run code after the form has been validated successfully.
     * Throw an exception here if either the form is not valid or you want interrupt the form handling.
     * The exception's message will be automatically added as form error message so that it will be
     * displayed in the frontend.
     *
     * @param ConfigForm $form
     *
     * @throws \Exception If either the form is not valid or to interrupt the form handling
     */
    public function isValid(ConfigForm $form)
    {
    }

    /**
     * onSuccess event hook
     *
     * Implement this method in order to run code after the configuration form has been stored successfully.
     * You can't interrupt the form handling here. Any exception will be caught, logged and notified.
     *
     * @param ConfigForm $form
     */
    public function onSuccess(ConfigForm $form)
    {
    }

    /**
     * Get an array of errors found while processing the form event hooks
     *
     * @return array
     */
    final public static function getLastErrors()
    {
        return static::$lastErrors;
    }

    /**
     * Run all isValid hooks
     *
     * @param \Icinga\Forms\ConfigForm $form
     *
     * @return bool Returns false if any hook threw an exception
     */
    final public static function runIsValid(ConfigForm $form)
    {
        static::$lastErrors = [];

        if (! Hook::has('ConfigFormEvents')) {
            return true;
        }

        $valid = true;

        foreach (Hook::all('ConfigFormEvents') as $hook) {
            /** @var self $hook */
            try {
                $hook->isValid($form);
            } catch (\Exception $e) {
                static::$lastErrors[] = $e->getMessage();

                Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));

                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Run all onSuccess hooks
     *
     * @param \Icinga\Forms\ConfigForm $form
     *
     * @return bool Returns false if any hook threw an exception
     */
    final public static function runOnSuccess(ConfigForm $form)
    {
        static::$lastErrors = [];

        if (! Hook::has('ConfigFormEvents')) {
            return true;
        }

        $success = true;

        foreach (Hook::all('ConfigFormEvents') as $hook) {
            /** @var self $hook */
            try {
                $hook->onSuccess($form);
            } catch (\Exception $e) {
                static::$lastErrors[] = $e->getMessage();

                Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));

                $success = false;
            }
        }

        return $success;
    }
}
