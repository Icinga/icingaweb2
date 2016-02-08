<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Form;

/**
 * Decorator to add a list of notifications at the top or bottom of a form
 */
class FormNotifications extends Zend_Form_Decorator_Abstract
{
    /**
     * Render form notifications
     *
     * @param   string      $content    The html rendered so far
     *
     * @return  string                  The updated html
     */
    public function render($content = '')
    {
        $form = $this->getElement();
        if (! $form instanceof Form) {
            return $content;
        }

        $view = $form->getView();
        if ($view === null) {
            return $content;
        }

        $notifications = $this->recurseForm($form);
        if (empty($notifications)) {
            return $content;
        }

        $html = '<ul class="form-notifications">';
        foreach (array(Form::NOTIFICATION_ERROR, Form::NOTIFICATION_WARNING, Form::NOTIFICATION_INFO) as $type) {
            if (isset($notifications[$type])) {
                $html .= '<li><ul class="notification-' . $this->getNotificationTypeName($type) . '">';
                foreach ($notifications[$type] as $message) {
                    if (is_array($message)) {
                        list($message, $properties) = $message;
                        $html .= '<li' . $view->propertiesToString($properties) . '>'
                            . $view->escape($message)
                            . '</li>';
                    } else {
                        $html .= '<li>' . $view->escape($message) . '</li>';
                    }
                }

                $html .= '</ul></li>';
            }
        }

        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $html . '</ul>';
            case self::PREPEND:
                return $html . '</ul>' . $content;
        }
    }

    /**
     * Recurse the given form and return the notifications for it and all of its subforms
     *
     * @param   Form    $form   The form to recurse
     *
     * @return  array
     */
    protected function recurseForm(Form $form)
    {
        $notifications = $form->getNotifications();
        foreach ($form->getSubForms() as $subForm) {
            foreach ($this->recurseForm($subForm) as $type => $messages) {
                foreach ($messages as $message) {
                    $notifications[$type][] = $message;
                }
            }
        }

        return $notifications;
    }

    /**
     * Return the name for the given notification type
     *
     * @param   int     $type
     *
     * @return  string
     *
     * @throws  ProgrammingError    In case the given type is invalid
     */
    protected function getNotificationTypeName($type)
    {
        switch ($type) {
            case Form::NOTIFICATION_ERROR:
                return 'error';
            case Form::NOTIFICATION_WARNING:
                return 'warning';
            case Form::NOTIFICATION_INFO:
                return 'info';
            default:
                throw new ProgrammingError('Invalid notification type "%s" provided', $type);
        }
    }
}
