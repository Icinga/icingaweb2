<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Config;

use Icinga\Application\Config;
use Icinga\Web\Form;
use Icinga\Web\Notification;

/**
 * Form for reordering command transports
 */
class TransportReorderForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_reorder_command_transports');
        $this->setViewScript('form/reorder-command-transports.phtml');
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        // This adds just a dummy element to be able to utilize Form::getValue as part of onSuccess()
        $this->addElement(
            'hidden',
            'transport_newpos',
            array(
                'required' => true,
                'validators' => array(
                    array(
                        'validator' => 'regex',
                        'options' => array(
                            'pattern' => '/\A\d+\|/'
                        )
                    )
                )
            )
        );
    }

    /**
     * Update the command transport order and save the configuration
     */
    public function onSuccess()
    {
        list($position, $transportName) = explode('|', $this->getValue('transport_newpos'), 2);
        $config = $this->getConfig();
        if (! $config->hasSection($transportName)) {
            Notification::error(sprintf($this->translate('Command transport "%s" not found'), $transportName));
            return false;
        }

        if ($config->count() > 1) {
            $sections = $config->keys();
            array_splice($sections, array_search($transportName, $sections, true), 1);
            array_splice($sections, $position, 0, array($transportName));

            $sectionsInNewOrder = array();
            foreach ($sections as $section) {
                $sectionsInNewOrder[$section] = $config->getSection($section);
                $config->removeSection($section);
            }
            foreach ($sectionsInNewOrder as $name => $options) {
                $config->setSection($name, $options);
            }

            $config->saveIni();
            Notification::success($this->translate('Command transport order updated'));
        }
    }

    /**
     * Get the command transports config
     *
     * @return Config
     */
    public function getConfig()
    {
        return Config::module('monitoring', 'commandtransports');
    }
}
