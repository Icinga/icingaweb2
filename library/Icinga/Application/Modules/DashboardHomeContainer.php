<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2 */

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
     * @param string                   $name
     * @param DashboardContainer|array $dashboard
     *
     * @return $this
     */
    public function add($name, $dashboard = [], $properties = [])
    {
        if (! is_array($dashboard) && ! $dashboard instanceof DashboardContainer) {
            throw new InvalidArgumentException(sprintf(
                '%s() expects parameter 2 to be an array or an instance of %s, got %s instead',
                __METHOD__,
                DashboardContainer::class,
                get_php_type($dashboard)
            ));
        }

        // If $dashboard is an object, we need to convert it to an array
        if (is_object($dashboard)) {
            $dashboard->setProperties(array_merge($dashboard->getProperties(), ['home' => $this->getName()]));
            $dashboard = self::objectToArray($dashboard);
        }

        $dashboard['properties'] = array_merge($dashboard['properties'], $properties);
        $this->dashboards[$name] = $dashboard;

        return $this;
    }

    /**
     * Converts the given object to Array
     *
     * @param  object $obj
     *
     * @return array
     */
    public static function objectToArray($obj)
    {
        $array = [];
        $reflectionClass = new ReflectionClass(get_class($obj));

        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $array[$property->getName()] = $property->getValue($obj);
            $property->setAccessible(false);
        }

        return $array;
    }
}
