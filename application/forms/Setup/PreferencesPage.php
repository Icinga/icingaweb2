<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Setup;

use Icinga\Web\Form;
use Icinga\Application\Platform;
use Icinga\Web\Form\Element\Note;

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
                t('Note that choosing "Database" causes Icinga Web 2 to use the same database as for authentication.')
            );
        return $this;
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            new Note(
                'description',
                array(
                    'value' => t('Please choose how Icinga Web 2 should store user preferences.')
                )
            )
        );

        $storageTypes = array();
        $storageTypes['ini'] = t('File System (INI Files)');
        if (Platform::extensionLoaded('pdo') && (Platform::zendClassExists('Zend_Db_Adapter_Pdo_Mysql')
            || Platform::zendClassExists('Zend_Db_Adapter_Pdo_Pgsql')))
        {
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
