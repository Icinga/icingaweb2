<?php

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Notification;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard;
use ipl\Html\Html;
use ipl\Web\Compat\CompatForm;

class RemovalForm extends CompatForm
{
    /** @var Dashboard  */
    private $dashboard;

    /** @var string $pane name of the pane to be deleted */
    private $pane;

    /**
     * RemovalForm constructor.
     *
     * @param Dashboard $dashboard
     *
     * @param $pane
     */
    public function __construct(Dashboard $dashboard, $pane)
    {
        $this->dashboard = $dashboard;
        $this->pane = $pane;
    }

    protected function assemble()
    {
        $formTitle = Html::sprintf(t('Please confirm removal of dashboard \'%s\''), $this->pane);
        if (Url::fromRequest()->getPath() === 'dashboard/remove-dashlet') {
            $dashlet = $this->dashboard->getPane($this->pane)->getDashlet(
                Url::fromRequest()->getParam('dashlet')
            )->getName();
            $formTitle = Html::sprintf(t('Please confirm removal of dashlet \'%s\''), $dashlet);
        }
        $this->add(Html::tag('h1', null, $formTitle));

        $this->addElement('submit', 'submit', [
            'label' => 'Confirm Removal'
        ]);
    }

    public function removePaneAction()
    {
        $pane = $this->dashboard->getPane($this->pane);
        $this->dashboard->removePane($pane->getName());

        try {
            $db = $this->dashboard->getConn();
            $db->delete('dashlet', ['dashboard_id=?' => $pane->getPaneId()]);
            $db->delete('dashboard', [
                'home_id=?' => $pane->getParentId(),
                'id=?'      => $pane->getPaneId(),
                'name=?'    => $this->pane
            ]);

            Notification::success(t('Dashboard has been removed') . ': ' . $pane->getTitle());
        } catch (\PDOException $e) {
            $this->addMessage($e);
            return;
        }
    }

    public function removeDashletAction()
    {
        $dashlet = Url::fromRequest()->getParam('dashlet');
        $pane = $this->dashboard->getPane($this->pane);
        try {
            $this->dashboard->getConn()->delete('dashlet', ['id=?' => $pane->getDashlet($dashlet)->getDashletId()]);
            $pane->removeDashlet($dashlet);
        } catch (\PDOException $err) {
            $this->addMessage($err);
            return;
        }

        Notification::success(t('Dashlet has been removed from') . ' ' . $pane->getTitle());
    }

    public function onSuccess()
    {
        if (Url::fromRequest()->getPath() === 'dashboard/remove-pane') {
            $this->removePaneAction();
        } else {
            $this->removeDashletAction();
        }
    }
}
