<?php

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Notification;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class NewHomePaneForm extends CompatForm
{
    /** @var Dashboard */
    protected $dashboard;

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;

        $requestUrl = Url::fromRequest();

        // We need to set this explicitly needed for modals
        $this->setAction((string) $requestUrl);

        if ($requestUrl->hasParam('home')) {
            $this->populate(['home' => $requestUrl->getParam('home')]);
        }
    }

    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent()
            && ($this->getPopulatedValue('btn_cancel')
                || $this->getPopulatedValue('submit'));
    }

    protected function assemble()
    {
        $populatedHome = Url::fromRequest()->getParam('home');
        $this->addElement('text', 'pane', [
            'required'    => true,
            'label'       => t('Title'),
            'description' => t('Add new dashboard to this home.')
        ]);

        $this->addElement('select', 'home', [
            'required'     => true,
            'class'        => 'autosubmit',
            'value'        => $populatedHome,
            'multiOptions' => $this->dashboard->getHomeKeyTitleArr(),
            'label'        => t('Assign to Home'),
            'description'  => t('A dashboard home you want to assign the new dashboard to.'),
        ]);

        $submitButton = $this->createElement('submit', 'submit', [
            'class' => 'autosubmit',
            'label' => t('Add Dashboard'),
        ]);
        $this->registerElement($submitButton)->decorate($submitButton);

        $this->addElement('submit', 'btn_cancel', ['label' => t('Cancel')]);
        $this->getElement('btn_cancel')->setWrapper($submitButton->getWrapper());
    }

    protected function onSuccess()
    {
        $requestUrl = Url::fromRequest();
        $dashboard = $this->dashboard;
        $conn = Dashboard::getConn();

        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/new-pane') {
            $home = $this->getPopulatedValue('home');
            $home = $dashboard->getHome($home);

            $conn->beginTransaction();

            try {
                $pane = new Pane($this->getPopulatedValue('pane'));
                $home->managePanes($pane);

                $conn->commitTransaction();
            } catch (\Exception $err) {
                $conn->rollBackTransaction();
                throw $err;
            }

            Notification::success('Added dashboard successfully');
        } else {

        }
    }
}
