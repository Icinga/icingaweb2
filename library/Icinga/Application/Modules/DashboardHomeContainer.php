<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Modules;

use InvalidArgumentException;
use ReflectionClass;

use function ipl\Stdlib\get_php_type;

/**
 * Container for via module provided dashboard homes
 */
class DashboardHomeContainer extends NavigationItemContainer
{
    /**
     * This home's dashboards
     *
     * @var array
     */
    protected $dashboards = [];

    /**
     * Set this home's dashboards
     *
     * @param array $dashboards
     *
     * @return array
     */
    public function setDashboards(array $dashboards)
    {
        $this->dashboards = $dashboards;

        return $this->dashboards;
    }

    /**
     * Get this home's dashboards
     *
     * @return array
     */
    public function getDashboards()
    {
        return $this->dashboards;
    }

    /**
     * Add a new dashboard pane
     *
     * @param string                    $name       Unique name of the given dashboard
     * @param DashboardContainer|array  $dashboard  A dashboard pane
     * @param array                     $properties Properties of this dashboard
     *
     * @return $this
     */
    public function add($name, $dashboard = [], array $properties = [])
    {
        if (! is_array($dashboard) && ! $dashboard instanceof DashboardContainer) {
            throw new InvalidArgumentException(sprintf(
                '%s() expects parameter 2 to be an array or an instance of %s, got %s instead',
                __METHOD__,
                DashboardContainer::class,
                get_php_type($dashboard)
            ));
        }

        // Navigation::addItem() expects parameter #2 to be an array, since these dashboards
        // are actually properties of this home, so we need to extract them into an array
        if (is_object($dashboard)) {
            $dashboard->setProperties(array_merge($dashboard->getProperties(), ['home' => $this->getName()]));
            $dashboard = self::toArray($dashboard);
        }

        $dashboard['properties'] = array_merge($dashboard['properties'], $properties);
        $this->dashboards[$name] = $dashboard;

        return $this;
    }

    /**
     * Extract the given object to array
     *
     * @param object $object
     *
     * @return array
     */
    public static function toArray($object)
    {
        $dashboards = [];
        $reflectionClass = new ReflectionClass(get_class($object));
        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $dashboards[$property->getName()] = $property->getValue($object);
            $property->setAccessible(false);
        }

        return $dashboards;
    }
}
