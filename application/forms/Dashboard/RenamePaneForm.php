<?php

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Notification;
use Icinga\Web\Widget\Dashboard;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class RenamePaneForm extends CompatForm
{
    /** @var Dashboard */
    private $dashboard;

    /**
     * RenamePaneForm constructor.
     *
     * @param $dashboard
     *
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function __construct($dashboard)
    {
        $this->dashboard = $dashboard;

        $pane = $this->dashboard->getPane(Url::fromRequest()->getParam('pane'));
        $this->populate([
            'name'  => $pane->getName(),
            'title' => $pane->getTitle()
        ]);
    }

    public function assemble()
    {
        $this->addElement(
            'text',
            'name',
            [
                'required'  => true,
                'label'     => t('Name')
            ]
        );
        $this->addElement(
            'text',
            'title',
            [
                'required'  => true,
                'label'     => t('Title')
            ]
        );

        $this->addElement('submit', 'submit', ['label' => t('Update Pane')]);
    }

    public function onSuccess()
    {
        $paneName = Url::fromRequest()->getParam('pane');
        $newName  = $this->getValue('name');
        $newTitle = $this->getValue('title');

        $pane = $this->dashboard->getPane($paneName);
        $pane->setName($newName);
        $pane->setTitle($newTitle);

        $this->dashboard->getConn()->update('dashboard', [
            'name'  => $pane->getName(),
        ], ['dashboard.id=?' => $pane->getPaneId()]);

        Notification::success(
            sprintf(t('Pane "%s" successfully renamed to "%s"'), $paneName, $newName)
        );
    }
}
