<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
        $this->getElement('store')
            ->setValue('db')
            ->setDescription(
                $this->translate(
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
                'value'         => $this->translate('Preferences', 'setup.page.title'),
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
                'value' => $this->translate('Please choose how Icinga Web 2 should store user preferences.')
            )
        );

        $storageTypes = array();
        $storageTypes['ini'] = $this->translate('File System (INI Files)');
        if (Platform::hasMysqlSupport() || Platform::hasPostgresqlSupport()) {
            $storageTypes['db'] = $this->translate('Database');
        }
        $storageTypes['none'] = $this->translate('Don\'t Store Preferences');

        $this->addElement(
            'select',
            'store',
            array(
                'required'      => true,
                'label'         => $this->translate('User Preference Storage Type'),
                'multiOptions'  => $storageTypes
            )
        );
    }
}
