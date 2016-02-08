<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Web\Form;
use Icinga\Web\StyleSheet;

/**
 * Configuration form for theming options
 *
 * This form is not used directly but as subform for the {@link GeneralConfigForm}.
 */
class ThemingConfigForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_config_general_theming');
    }

    /**
     * {@inheritdoc}
     *
     * @return  $this
     */
    public function createElements(array $formData)
    {
        $themes = Icinga::app()->getThemes();
        $themes[StyleSheet::DEFAULT_THEME] .= ' (' . $this->translate('default') . ')';

        $this->addElement(
            'select',
            'themes_default',
            array(
                'description'   => $this->translate('The default theme', 'Form element description'),
                'disabled'      => count($themes) < 2 ? 'disabled' : null,
                'label'         => $this->translate('Default Theme', 'Form element label'),
                'multiOptions'  => $themes,
                'value'         => StyleSheet::DEFAULT_THEME
            )
        );

        $this->addElement(
            'checkbox',
            'themes_disabled',
            array(
                'description'   => $this->translate(
                    'Check this box for disallowing users to change the theme. If a default theme is set, it will be'
                    . ' used nonetheless',
                    'Form element description'
                ),
                'label'         => $this->translate('Users Can\'t Change Theme', 'Form element label')
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);
        if ($values['themes_default'] === '' || $values['themes_default'] === StyleSheet::DEFAULT_THEME) {
            $values['themes_default'] = null;
        }
        if (! $values['themes_disabled']) {
            $values['themes_disabled'] = null;
        }
        return $values;
    }
}
