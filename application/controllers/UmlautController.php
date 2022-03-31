<?php

namespace Icinga\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Data\Db\DbConnection;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use ipl\Html\Form;
use ipl\Web\Compat\CompatController;

class UmlautController extends CompatController
{
    public function umlautsAction()
    {
        $form = new Form();
        $form->addElement('textarea', 'input', ['label' => 'Input']);
        $form->addElement('submit', 'submit', ['label' => 'Submit']);

        $form->on(Form::ON_SUCCESS, function ($form) {
            $backend = MonitoringBackend::instance();
            $db = $backend->getResource();
            /** @var DbConnection $db */

            $db->update('icinga_comments', ['comment_data' => $form->getValue('input')]);

            $this->getResponse()->setHeader('X-Icinga-Extra-Updates', '#col2');
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);
    }
}
