<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimesCommandForm;

/**
 * Monitoring API
 *
 * @method \Icinga\Web\Request getRequest() {
 *     {@inheritdoc}
 *     @return  \Icinga\Web\Request
 * }
 */
class Monitoring_ActionsController extends Controller
{
    /**
     * Remove host downtimes
     */
    public function removeHostDowntimeAction()
    {
        // @TODO(el): Require a filter?
        $downtimes = $this->backend
            ->select()
            ->from('downtime', array('host_name', 'id' => 'downtime_internal_id'))
            ->where('object_type', 'host')
            ->applyFilter($this->getRestriction('monitoring/filter/objects'))
            ->handleRequest($this->getRequest())
            ->fetchAll();
        if (empty($downtimes)) {
            // @TODO(el): Use ApiResponse class for unified response handling.
            $this->getRequest()->sendJson(array(
                'status'    => 'error',
                'message'   => 'No downtimes found matching the given filter'
            ));
        }
        $form = new DeleteDowntimesCommandForm();
        $form
            ->setIsApiTarget(true)
            ->setDowntimes($downtimes)
            ->handleRequest($this->getRequest());
        // @TODO(el): Respond w/ the downtimes deleted instead of the notifiaction added by
        // DeleteDowntimesCommandForm::onSuccess().
    }
}
