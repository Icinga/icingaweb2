<?php
/* Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Web\Form;

/**
 * Base class for config form event hooks
 */
abstract class ConfigFormEventsHook
{
    /** @var array Array of errors found while processing the form event hooks */
    private static $lastErrors = [];

    /**
     * Get whether the hook applies to the given config form
     *
     * @param Form $form
     *
     * @return bool
     */
    public function appliesTo(Form $form)
    {
        return false;
    }

    /**
     * isValid event hook
     *
     * Implement this method in order to run code after the form has been validated successfully.
     * Throw an exception here if either the form is not valid or you want interrupt the form handling.
     * The exception's message will be automatically added as form error message so that it will be
     * displayed in the frontend.
     *
     * @param Form $form
     *
     * @throws \Exception If either the form is not valid or to interrupt the form handling
     */
    public function isValid(Form $form)
    {
    }

    /**
     * onSuccess event hook
     *
     * Implement this method in order to run code after the configuration form has been stored successfully.
     * You can't interrupt the form handling here. Any exception will be caught, logged and notified.
     *
     * @param Form $form
     */
    public function onSuccess(Form $form)
    {
    }

    /**
     * Get an array of errors found while processing the form event hooks
     *
     * @return array
     */
    final public static function getLastErrors()
    {
        return self::$lastErrors;
    }

    /**
     * Run all isValid hooks
     *
     * @param Form $form
     *
     * @return bool Returns false if any hook threw an exception
     */
    final public static function runIsValid(Form $form)
    {
        return self::runEventMethod('isValid', $form);
    }

    /**
     * Run all onSuccess hooks
     *
     * @param Form $form
     *
     * @return bool Returns false if any hook threw an exception
     */
    final public static function runOnSuccess(Form $form)
    {
        return self::runEventMethod('onSuccess', $form);
    }

    private static function runEventMethod($eventMethod, Form $form)
    {
        self::$lastErrors = [];

        if (! Hook::has('ConfigFormEvents')) {
            return true;
        }

        $success = true;

        foreach (Hook::all('ConfigFormEvents') as $hook) {
            /** @var self $hook */
            if (! $hook->runAppliesTo($form)) {
                continue;
            }

            try {
                $hook->$eventMethod($form);
            } catch (\Exception $e) {
                self::$lastErrors[] = $e->getMessage();

                Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));

                $success = false;
            }
        }

        return $success;
    }

    private function runAppliesTo(Form $form)
    {
        try {
            $appliesTo = $this->appliesTo($form);
        } catch (\Exception $e) {
            // Don't save exception to last errors because we do not want to disturb the user for messed up
            // appliesTo checks
            Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));

            $appliesTo = false;
        }

        return $appliesTo === true;
    }
}
