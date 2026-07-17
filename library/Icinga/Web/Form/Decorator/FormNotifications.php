<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form\Decorator;

use Icinga\Web\Form;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;
use ipl\Web\Common\CalloutType;
use ipl\Web\Widget\Callout;
use LogicException;
use Zend_Form_Decorator_Abstract;

/**
 * Decorator to add a list of notifications at the top or bottom of a form
 */
class FormNotifications extends Zend_Form_Decorator_Abstract
{
    use Translation;

    /**
     * Render form notifications
     *
     * @param   string      $content    The html rendered so far
     *
     * @return  ?string                  The updated html
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

        $html = '';
        foreach ([Form::NOTIFICATION_ERROR, Form::NOTIFICATION_WARNING, Form::NOTIFICATION_INFO] as $type) {
            if (isset($notifications[$type])) {
                $messages = [];
                foreach ($notifications[$type] as $message) {
                    if (is_array($message)) {
                        [$message, $properties] = $message;
                        $messages[] = HtmlElement::create('li', $properties, $message);
                    } else {
                        $messages[] = HtmlElement::create('li', [], $message);
                    }
                }

                $count = count($messages);
                if ($count > 1) {
                    $document = HtmlElement::create('ul', null, $messages);
                } else {
                    $document = new HtmlDocument();
                    $document->add($messages[0]->getContent());
                }

                $html .= (new Callout($this->getCalloutType($type), $document, $this->getCalloutTitle($type, $count)))
                    ->addAttributes(new Attributes(['class' => 'form-notification']))
                    ->render();
            }
        }

        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $html;
            case self::PREPEND:
                return $html . $content;
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
     * Return the callout type for the given notification type
     *
     * @param int $type Form notification type constants
     *
     * @return CalloutType
     *
     * @throws LogicException In case the given type is invalid
     */
    protected function getCalloutType(int $type): CalloutType
    {
        return match ($type) {
            Form::NOTIFICATION_ERROR => CalloutType::Error,
            Form::NOTIFICATION_WARNING => CalloutType::Warning,
            Form::NOTIFICATION_INFO => CalloutType::Info,
            default => throw new LogicException(sprintf('Invalid notification type "%s" provided', $type)),
        };
    }

    /**
     * Return the title for the given notification type
     *
     * @param int $type Form notification type constants
     * @param int $count Number of notifications of the given type
     *
     * @return string
     * @throws LogicException In case the given type is invalid
     */
    protected function getCalloutTitle(int $type, int $count): string
    {
        return match ($type) {
            Form::NOTIFICATION_ERROR => $this->translatePlural('Error', 'Errors', $count),
            Form::NOTIFICATION_WARNING => $this->translatePlural('Warning', 'Warnings', $count),
            Form::NOTIFICATION_INFO => $this->translatePlural('Info', 'Infos', $count),
            default => throw new LogicException(sprintf('Invalid notification type "%s" provided', $type)),
        };
    }
}
