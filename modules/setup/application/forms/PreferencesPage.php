<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Application\Platform;

/**
 * Wizard page to choose a preference backend
 */
class PreferencesPage extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_preferences_type');
    }

    /**
     * Pre-select "db" as preference backend and add a hint to the select element
     *
     * @return  self
     */
    public function showDatabaseNote()
    {
        $this->getElement('type')
            ->setValue('db')
            ->setDescription(
                mt(
                    'setup',
                    'Note that choosing "Database" causes Icinga Web 2 to use the same database as for authentication.'
                )
            );
        return $this;
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => mt('setup', 'Preferences', 'setup.page.title'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );
        $this->addElement(
            'note',
            'description',
            array(
                'value' => mt('setup', 'Please choose how Icinga Web 2 should store user preferences.')
            )
        );

        $storageTypes = array();
        $storageTypes['ini'] = t('File System (INI Files)');
        if (Platform::hasMysqlSupport() || Platform::hasPostgresqlSupport()) {
            $storageTypes['db'] = t('Database');
        }
        $storageTypes['null'] = t('Don\'t Store Preferences');

        $this->addElement(
            'select',
            'type',
            array(
                'required'      => true,
                'label'         => t('User Preference Storage Type'),
                'multiOptions'  => $storageTypes
            )
        );
    }
}
