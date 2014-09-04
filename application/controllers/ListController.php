<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Controller;
use Icinga\Web\Hook;
use Icinga\Web\Url;
use Icinga\Data\ResourceFactory;
use Icinga\Logger\Logger;
use Icinga\Logger\Writer\FileWriter;
use Icinga\Protocol\File\FileReader;

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

        $loggerWriter = Logger::getInstance()->getWriter();
        if ($loggerWriter instanceof FileWriter) {
            $resource = new FileReader(new Zend_Config(array(
                'filename'  => $loggerWriter->getPath(),
                'fields'    => '/^(?<datetime>[0-9]{4}(-[0-9]{2}){2}'           // date
                    . 'T[0-9]{2}(:[0-9]{2}){2}([\\+\\-][0-9]{2}:[0-9]{2})?)'    // time
                    . ' - (?<loglevel>[A-Za-z]+)'                               // loglevel
                    . ' - (?<message>.*)$/'                                     // message
            )));
            $this->view->logData = $resource->select()->order('DESC')->paginate();
        } else {
            $this->view->logData = null;
        }
    }
}
