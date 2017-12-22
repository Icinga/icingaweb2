<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Security;

use Icinga\Application\Icinga;
use Icinga\Forms\ConfigForm;
use Zend_Form_Element;

class RestrictionsRoleForm extends ConfigForm
{

    /**
     * Provided restrictions by currently loaded modules
     *
     * @var array
     */
    protected $providedRestrictions;


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $helper = new Zend_Form_Element('bogus');
        $this->providedRestrictions = array(
            $helper->filterName('application/share/users') => array(
                'name'          => 'application/share/users',
                'description'   => $this->translate(
                    'Restrict which users this role can share items and information with'
                )
            ),
            $helper->filterName('application/share/groups') => array(
                'name'          => 'application/share/groups',
                'description'   => $this->translate(
                    'Restrict which groups this role can share items and information with'
                )
            )
        );

        $mm = Icinga::app()->getModuleManager();
        foreach ($mm->listInstalledModules() as $moduleName) {
            $module = $mm->getModule($moduleName, false);
            foreach ($module->getProvidedRestrictions() as $restriction) {
                /** @var object $restriction */
                // Zend only permits alphanumerics, the underscore, the circumflex and any ASCII character in range
                // \x7f to \xff (127 to 255)
                $name = $helper->filterName($restriction->name);
                while (isset($this->providedRestrictions[$name])) {
                    // Because Zend_Form_Element::filterName() replaces any not permitted character with the empty
                    // string we may have duplicate names, e.g. 're/striction' and 'restriction'
                    $name .= '_';
                }
                $this->providedRestrictions[$name] = array(
                    'description' => $restriction->description,
                    'name'        => $restriction->name
                );
            }
        }
    }
    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData = array())
    {
        // Role Restrictions
        foreach ($this->providedRestrictions as $name => $spec) {
            $this->addElement(
                'text',
                $name,
                array(
                    'label'         => $spec['name'],
                    'description'   => $spec['description']
                )
            );
        }

        return $this;
    }
}