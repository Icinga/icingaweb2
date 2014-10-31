<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\File\Ini;

use Zend_Config;
use Zend_Config_Ini;
use Zend_Config_Writer_FileAbstract;

/**
 * A INI file adapter that respects the file structure and the comments of already existing ini files
 */
class IniWriter extends Zend_Config_Writer_FileAbstract
{
    /**
     * Stores the options
     *
     * @var array
     */
    protected $options;

    /**
     * Create a new INI writer
     *
     * @param array $options   Supports all options of Zend_Config_Writer and additional
     *                         options for the internal IniEditor:
     *                          * valueIndentation:     The indentation level of the values
     *                          * commentIndentation:   The indentation level of the comments
     *                          * sectionSeparators:    The amount of newlines between sections
     *
     * @link http://framework.zend.com/apidoc/1.12/files/Config.Writer.html#\Zend_Config_Writer
     */
    public function __construct(array $options = null)
    {
        $this->options = $options;
        parent::__construct($options);
    }

    /**
     * Find all keys containing dots and convert it to a nested configuration
     *
     * Ensure that configurations with the same ini representation the have
     * similarly nested Zend_Config objects. The configuration may be altered
     * during that process.
     *
     * @param   Zend_Config $config   The configuration to normalize
     * @return  Zend_Config           The normalized config
     */
    private function normalizeKeys(Zend_Config $config)
    {
        foreach ($config as $key => $value) {
            if (preg_match('/\./', $key) > 0) {
                // remove old key
                unset ($config->$key);

                // insert new key
                $nests = explode('.', $key);
                $current = $config;
                $i = 0;
                for (; $i < count($nests) - 1; $i++) {
                    if (! isset($current->{$nests[$i]})) {
                        // configuration key doesn't exist, create a new nesting level
                        $current->{$nests[$i]} = new Zend_Config (array(), true);
                    }
                    // move to next nesting level
                    $current = $current->{$nests[$i]};
                }
                // reached last nesting level, insert value
                $current->{$nests[$i]} = $value;
            }
            if ($value instanceof Zend_Config) {
                $config->$key = $this->normalizeKeys ($value);
            }
        }
        return $config;
    }

    /**
     * Render the Zend_Config into a config file string
     *
     * @return  string
     */
    public function render()
    {
        if (file_exists($this->_filename)) {
            $oldconfig = new Zend_Config_Ini($this->_filename);
        } else {
            $oldconfig = new Zend_Config(array());
        }

        // create an internal copy of the given configuration, since the user of this class
        // won't expect that a configuration will ever be altered during
        // the rendering process.
        $extends = $this->_config->getExtends();
        $this->_config = new Zend_Config ($this->_config->toArray(), true);
        foreach ($extends as $extending => $extended) {
           $this->_config->setExtend($extending, $extended);
        }
        $this->_config = $this->normalizeKeys($this->_config);

        $newconfig = $this->_config;
        $editor = new IniEditor(file_get_contents($this->_filename), $this->options);
        $this->diffConfigs($oldconfig, $newconfig, $editor);
        $this->updateSectionOrder($newconfig, $editor);
        return $editor->getText();
    }

    /**
     * Create a property diff and apply the changes to the editor
     *
     * @param   Zend_Config     $oldconfig      The config representing the state before the change
     * @param   Zend_Config     $newconfig      The config representing the state after the change
     * @param   IniEditor       $editor         The editor that should be used to edit the old config file
     * @param   array           $parents        The parent keys that should be respected when editing the config
     */
    protected function diffConfigs(
        Zend_Config $oldconfig,
        Zend_Config $newconfig,
        IniEditor $editor,
        array $parents = array()
    ) {
        $this->diffPropertyUpdates($oldconfig, $newconfig, $editor, $parents);
        $this->diffPropertyDeletions($oldconfig, $newconfig, $editor, $parents);
    }

    /**
     * Update the order of the sections in the ini file to match the order of the new config
     */
    protected function updateSectionOrder(Zend_Config $newconfig, IniEditor $editor)
    {
        $order = array();
        foreach ($newconfig as $key => $value) {
            if ($value instanceof Zend_Config) {
                array_push($order, $key);
            }
        }
        $editor->refreshSectionOrder($order);
    }

    /**
     * Search for created and updated properties and use the editor to create or update these entries
     *
     * @param Zend_Config   $oldconfig  The config representing the state before the change
     * @param Zend_Config   $newconfig  The config representing the state after the change
     * @param IniEditor     $editor     The editor that should be used to edit the old config file
     * @param array         $parents    The parent keys that should be respected when editing the config
     */
    protected function diffPropertyUpdates(
        Zend_Config $oldconfig,
        Zend_Config $newconfig,
        IniEditor $editor,
        array $parents = array()
    ) {
        // The current section. This value is null when processing the section-less root element
        $section = empty($parents) ? null : $parents[0];
        // Iterate over all properties in the new configuration file and search for changes
        foreach ($newconfig as $key => $value) {
            $oldvalue = $oldconfig->get($key);
            $nextParents = array_merge($parents, array($key));
            $keyIdentifier = empty($parents) ? array($key) : array_slice($nextParents, 1, null, true);
            if ($value instanceof Zend_Config) {
                // The value is a nested Zend_Config, handle it recursively
                if ($section === null) {
                    // Update the section declaration
                    $extends = $newconfig->getExtends();
                    $extend = array_key_exists($key, $extends) ? $extends[$key] : null;
                    $editor->setSection($key, $extend);
                }
                if ($oldvalue === null) {
                    $oldvalue = new Zend_Config(array());
                }
                $this->diffConfigs($oldvalue, $value, $editor, $nextParents);
            } else {
                // The value is a plain value, use the editor to set it
                if (is_numeric($key)) {
                    $editor->setArrayElement($keyIdentifier, $value, $section);
                } else {
                    $editor->set($keyIdentifier, $value, $section);
                }
            }
        }
    }

    /**
     * Search for deleted properties and use the editor to delete these entries
     *
     * @param Zend_Config   $oldconfig  The config representing the state before the change
     * @param Zend_Config   $newconfig  The config representing the state after the change
     * @param IniEditor     $editor     The editor that should be used to edit the old config file
     * @param array         $parents    The parent keys that should be respected when editing the config
     */
    protected function diffPropertyDeletions(
        Zend_Config $oldconfig,
        Zend_Config $newconfig,
        IniEditor $editor,
        array $parents = array()
    ) {
        // The current section. This value is null when processing the section-less root element
        $section = empty($parents) ? null : $parents[0];

        // Iterate over all properties in the old configuration file and search for deleted properties
        foreach ($oldconfig as $key => $value) {
            if ($newconfig->get($key) === null) {
                $nextParents = array_merge($parents, array($key));
                $keyIdentifier = empty($parents) ? array($key) : array_slice($nextParents, 1, null, true);
                foreach ($this->getPropertyIdentifiers($value, $keyIdentifier) as $propertyIdentifier) {
                    $editor->reset($propertyIdentifier, $section);
                }
            }
        }
    }

    /**
     * Return all possible combinations of property identifiers for the given value
     *
     * @param   mixed   $value  The value to return all combinations for
     * @param   array   $key    The root property identifier, if any
     *
     * @return  array           All property combinations that are possible
     *
     * @todo                    Cannot handle array properties yet (e.g. a.b[]='c')
     */
    protected function getPropertyIdentifiers($value, array $key = null)
    {
        $combinations = array();
        $rootProperty = $key !== null ? $key : array();

        if ($value instanceof Zend_Config) {
            foreach ($value as $subProperty => $subValue) {
                $combinations = array_merge(
                    $combinations,
                    $this->getPropertyIdentifiers($subValue, array_merge($rootProperty, array($subProperty)))
                );
            }
        } elseif (is_string($value)) {
            $combinations[] = $rootProperty;
        }

        return $combinations;
    }
}
