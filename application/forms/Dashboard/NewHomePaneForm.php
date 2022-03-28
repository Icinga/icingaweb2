<?php

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Notification;
use ipl\Web\Url;

class NewHomePaneForm extends BaseDashboardForm
{
    public function __construct(Dashboard $dashboard)
    {
        parent::__construct($dashboard);

        $requestUrl = Url::fromRequest();
        if ($requestUrl->hasParam('home')) {
            $this->populate(['home' => $requestUrl->getParam('home')]);
        }
    }

    protected function assemble()
    {
        $populatedHome = Url::fromRequest()->getParam('home');
        $this->addElement('text', 'pane', [
            'required'    => true,
            'label'       => t('Title'),
            'placeholder' => t('Create new Dashboard'),
            'description' => t('Add new dashboard to this home.')
        ]);

        $homes = array_merge([self::CREATE_NEW_HOME => self::CREATE_NEW_HOME], $this->dashboard->getEntryKeyTitleArr());
        $this->addElement('select', 'home', [
            'required'     => true,
            'class'        => 'autosubmit',
            'value'        => $populatedHome,
            'multiOptions' => $homes,
            'label'        => t('Assign to Home'),
            'description'  => t('A dashboard home you want to assign the new dashboard to.'),
        ]);

        if ($this->getPopulatedValue('home') === self::CREATE_NEW_HOME) {
            $this->addElement('text', 'new_home', [
                'required'    => true,
                'label'       => t('Dashboard Home'),
                'placeholder' => t('Enter dashboard home title'),
                'description' => t('Enter a title for the new dashboard home.'),
            ]);
        }

        $submitButton = $this->createElement('submit', 'submit', [
            'class' => 'btn-primary',
            'label' => t('Add Dashboard')
        ]);
        $this->registerElement($submitButton);

        $formControls = $this->createFormControls();
        $formControls->add([
            $this->registerSubmitButton(t('Add Dashboard')),
            $this->createCancelButton()
        ]);

        $this->addHtml($formControls);
    }

    protected function onSuccess()
    {
        $requestUrl = Url::fromRequest();
        $conn = Dashboard::getConn();

        $selectedHome = $this->getPopulatedValue('home');
        if (! $selectedHome || $selectedHome === self::CREATE_NEW_HOME) {
            $selectedHome = $this->getPopulatedValue('new_home');
        }

        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/new-pane') {
            $currentHome = new DashboardHome($selectedHome);
            if ($this->dashboard->hasEntry($currentHome->getName())) {
                $currentHome = clone $this->dashboard->getEntry($currentHome->getName());
                if ($currentHome->getName() !== $this->dashboard->getActiveHome()->getName()) {
                    $currentHome->setActive()->loadDashboardEntries();
                }
            }

            $pane = new Pane($this->getPopulatedValue('pane'));
            $conn->beginTransaction();

            try {
                $this->dashboard->manageEntry($currentHome);
                $currentHome->manageEntry($pane);

                $conn->commitTransaction();
            } catch (\Exception $err) {
                $conn->rollBackTransaction();
                throw $err;
            }

            Notification::success('Added dashboard successfully');
        }
    }
}
