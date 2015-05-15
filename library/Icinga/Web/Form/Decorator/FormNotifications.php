<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Icinga\Web\Form as Form;

/**
 * Decorator to add a list of notifications at the top of a form
 */
class FormNotifications extends Zend_Form_Decorator_Abstract
{
    /**
     * Render form descriptions
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

        asort($notifications);
        foreach ($notifications as $message => $type) {
            $html .= '<li class="'.self::getNotificationTypeName($type).'">' . $view->escape($message) . '</li>';
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
     * @param   Form    $form       The form to recurse
     *
     * @return array
     */
    protected function recurseForm(Form $form)
    {
        $notifications = $form->getNotifications();

        foreach ($form->getSubForms() as $subForm) {
            $notifications = $notifications + $this->recurseForm($subForm);
        }

        return $notifications;
    }

    /**
     * Get the readable type name of the notification
     *
     * @param   $type       Type of the message
     *
     * @return  string
     */
    public static function getNotificationTypeName($type)
    {
        switch ($type) {
            case Form::NOTIFICATION_ERROR:
                return 'error';
                break;
            case Form::NOTIFICATION_WARNING:
                return 'warning';
                break;
            case Form::NOTIFICATION_INFO:
                return 'info';
                break;
            default:
                return 'unknown';
        }
    }
}
