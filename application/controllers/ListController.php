<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

use Icinga\Module\Monitoring\Controller;
use Icinga\Web\Url;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Protocol\File\FileReader;
use \Zend_Controller_Action_Exception as ActionError;

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
        if (! Logger::writesToFile()) {
            throw new ActionError('Site not found', 404);
        }

        $this->addTitleTab('application log');
        $pattern = '/^(?<datetime>[0-9]{4}(-[0-9]{2}){2}'                 // date
                 . 'T[0-9]{2}(:[0-9]{2}){2}([\\+\\-][0-9]{2}:[0-9]{2})?)' // time
                 . ' - (?<loglevel>[A-Za-z]+)'                            // loglevel
                 . ' - (?<message>.*)$/';                                 // message

        $loggerWriter = Logger::getInstance()->getWriter();
        $resource = new FileReader(new ConfigObject(array(
            'filename'  => $loggerWriter->getPath(),
            'fields'    => $pattern
        )));
        $this->view->logData = $resource->select()->order('DESC')->paginate();
    }
}
