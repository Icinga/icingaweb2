<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
        $this->setRequiredCue(null);
        $this->setName('setup_preferences_type');
        $this->setTitle($this->translate('Preferences', 'setup.page.title'));
        $this->addDescription($this->translate('Please choose how Icinga Web 2 should store user preferences.'));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
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
