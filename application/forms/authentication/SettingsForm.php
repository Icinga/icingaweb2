<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form;

use Icinga\Web\Form;
use Icinga\Web\Session;
use Icinga\Web\Notification;
use Icinga\Application\Config;

/**
 * Class SettingsForm
 * @package Icinga\Web\Form
 */
class SettingsForm extends Form
{
    /**
     *
     */
    public function onSuccess()
    {
        $session = Session::getInstance();
        $values = $this->getValues();
        $session->language = $values['language'];
        $session->backend = $values['backend'];
        $session->show_benchmark = (bool)$values['show_benchmark'];
    }

    /**
     * @return array
     */
    public function elements()
    {

        $all_backends = Config::getInstance()->listAll('backends');
        $language = \Icinga\Web\Session::getInstance()->language;
        if (!$language) {
            $language = 'en_US';
        }
        return array(
            'backend' => array(
                'select',
                array(
                    'label' => 'Backend',
                    'required' => true,
                    'value' => Session::getInstance()->backend,
                    'multiOptions' => array_combine($all_backends, $all_backends)
                )
            ),
            'language' => array(
                'select',
                array(
                    'label' => 'Language',
                    'required' => true,
                    'value' => $language,
                    'multiOptions' => array(
                        'de_DE' => 'Deutsch',
                        'en_US' => 'Englisch'
                    )
                )
            ),
            'show_benchmark' => array(
                'checkbox',
                array(
                    'label' => 'Show Benchmarks'
                )
            ),
            'submit' => array(
                'submit',
                array(
                    'label' => t('Apply')
                )
            )

        );
    }
}
