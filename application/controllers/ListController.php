<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Controller;
use Icinga\Web\Hook;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Web\Url;
use Icinga\Data\ResourceFactory;

/**
 * Class ListController
 *
 * Application wide controller for various listing actions
 */
class ListController extends Controller
{
    /**
     * Add title tab
     *
     * @param string $action
     */
    protected function addTitleTab($action)
    {
        $this->getTabs()->add($action, array(
            'title' => ucfirst($action),
            'url' => Url::fromPath(
                    'list/'
                    . str_replace(' ', '', $action)
                )
        ))->activate($action);
    }

    /**
     * Display the application log
     */
    public function applicationlogAction()
    {
        $this->addTitleTab('application log');
        $config_ini = IcingaConfig::app()->toArray();
        if (!in_array('logging', $config_ini) || (
                in_array('type', $config_ini['logging']) &&
                    $config_ini['logging']['type'] === 'file' &&
                in_array('target', $config_ini['logging']) &&
                    file_exists($config_ini['logging']['target'])
            )
        ) {
            $config = ResourceFactory::getResourceConfig('logfile');
            $resource = ResourceFactory::createResource($config);
            $this->view->logData = $resource->select()->order('DESC')->paginate();
        } else {
            $this->view->logData = null;
        }
    }
}
