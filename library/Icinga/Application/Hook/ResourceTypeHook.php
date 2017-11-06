<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\ClassLoader;
use Icinga\Application\Hook;
use Icinga\Application\Icinga;
use Icinga\Data\ConfigObject;
use Icinga\Web\Form;

/**
 * Base class for custom resource type hooks
 *
 * Extend this class if you want your module to provide custom resource types.
 */
abstract class ResourceTypeHook
{
    /**
     * Cache for {@link getAll()}
     *
     * @var static[]
     */
    protected static $allProviders;

    /**
     * Get all custom resource type providers by unique type name
     *
     * @return static[]
     */
    final public static function getAll()
    {
        if (static::$allProviders === null) {
            $allProviders = array();
            foreach (Hook::all('ResourceType') as $resourceTypeProvider) {
                /** @var static $resourceTypeProvider */

                $allProviders[$resourceTypeProvider->getId()] = $resourceTypeProvider;
            }

            static::$allProviders = $allProviders;
        }

        return static::$allProviders;
    }

    /**
     * Constructor
     *
     * @see {@link init()} for hook initialization.
     */
    final public function __construct()
    {
        $this->init();
    }

    /**
     * Overwrite this function for hook initialization, e.g. loading the hook's config
     */
    protected function init()
    {
    }

    /**
     * Return a unique, short, but descriptive identifier of the provided resource type
     *
     * @return string
     */
    final public function getId()
    {
        return 'module/' . ClassLoader::extractModuleName(get_class($this)) . '/' . $this->getIdPerModule();
    }

    /**
     * Return a unique (per module), short, but descriptive identifier of the provided resource type (e.g. 'icinga2api')
     *
     * @return string
     */
    abstract protected function getIdPerModule();

    /**
     * Return a translated, unique, short, but descriptive title of the provided resource type
     *
     * @return string
     */
    final public function getTitle()
    {
        return sprintf(
            t('%s – %s', 'custom resource type title'),
            Icinga::app()->getModuleManager()->getModule(ClassLoader::extractModuleName(get_class($this)))->getTitle(),
            $this->getTitlePerModule()
        );
    }

    /**
     * Return a translated, unique (per module), short, but descriptive title of the provided resource type (e.g. 'Icinga 2 API')
     *
     * @return string
     */
    abstract protected function getTitlePerModule();

    /**
     * Return the ID of the icon to use for the provided resource type
     *
     * @return string
     */
    public function getIconId()
    {
        return 'database';
    }

    /**
     * Create and return a new form for configuring the resource
     *
     * @return Form
     */
    abstract public function createConfigForm();

    /**
     * Create and return a resource of the provided type based on the given configuration
     *
     * @param   ConfigObject    $config
     *
     * @return mixed
     */
    abstract public function createResource(ConfigObject $config);

    /**
     * Is called immediately before a new resource is added to the configuration
     *
     * If an exception is thrown, the addition is cancelled
     *
     * @param   Form    $configForm     The new resource's configuration form
     *
     * @throws  \Exception
     */
    public function onAdd(Form $configForm)
    {
    }

    /**
     * Is called immediately before a new resource's configuration is edited
     *
     * If an exception is thrown, the change is cancelled
     *
     * @param   ConfigObject    $oldConfig      The resource's old configuration
     * @param   ConfigObject    $newConfig      The resource's new configuration (may be changed)
     * @param   Form            $configForm     The resource's configuration form
     *
     * @throws  \Exception
     */
    public function onEdit(ConfigObject $oldConfig, ConfigObject $newConfig, Form $configForm)
    {
        $this->onRemove($oldConfig);
        $this->onAdd($configForm);
    }

    /**
     * Is called immediately before a resource is removed from the configuration
     *
     * If an exception is thrown, the removal is cancelled
     *
     * @param   ConfigObject    $config     The resource's old configuration
     *
     * @throws  \Exception
     */
    public function onRemove(ConfigObject $config)
    {
    }
}
