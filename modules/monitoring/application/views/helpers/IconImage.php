<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Object\Macro;

/**
 * Generate icons to describe a given hosts state
 */
class Zend_View_Helper_IconImage extends Zend_View_Helper_Abstract
{
    /**
     * Create dispatch instance
     *
     * @return \Zend_View_Helper_IconImage
     */
    public function iconImage()
    {
        return $this;
    }

    /**
     * Display the image_icon of a MonitoredObject
     *
     * @param   MonitoredObject|stdClass   $object  The host or service
     * @return  string
     */
    public function host($object)
    {
        if ($object->host_icon_image && ! preg_match('/[\'"]/', $object->host_icon_image)) {
            return $this->view->icon(
                Macro::resolveMacros($object->host_icon_image, $object),
                null,
                array(
                    'alt'                   => $object->host_icon_image_alt,
                    'data-tooltip-delay'    => 0,
                    'class'                 => 'host-icon-image',
                    'title'                 => $object->host_icon_image_alt
                )
            );
        }
        return '';
    }

    /**
     * Display the image_icon of a MonitoredObject
     *
     * @param   MonitoredObject|stdClass   $object  The host or service
     * @return  string
     */
    public function service($object)
    {
        if ($object->service_icon_image && ! preg_match('/[\'"]/', $object->service_icon_image)) {
            return $this->view->icon(
                Macro::resolveMacros($object->service_icon_image, $object),
                null,
                array(
                    'alt'                   => $object->service_icon_image_alt,
                    'class'                 => 'service-icon-image',
                    'data-tooltip-delay'    => 0,
                    'title'                 => $object->service_icon_image_alt
                )
            );
        }
        return '';
    }
}
