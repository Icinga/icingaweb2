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
        $requestUrl = Url::fromRequest();
        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/new-pane') {
            $placeHolder = t('Create new Dashboard');
            $description = t('Add new dashboard to this home.');
            $btnLabel = t('Add Dashboard');
        } else {
            $placeHolder = t('Create new Dashboard Home');
            $description = t('Add new dashboard home.');
            $btnLabel = t('Add Home');
        }

        $this->addElement('text', 'title', [
            'required'    => true,
            'label'       => t('Title'),
            'placeholder' => $placeHolder,
            'description' => $description
        ]);

        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/new-pane') {
            $homes = array_merge(
                [self::CREATE_NEW_HOME => self::CREATE_NEW_HOME],
                $this->dashboard->getEntryKeyTitleArr()
            );
            $this->addElement('select', 'home', [
                'required'     => true,
                'class'        => 'autosubmit',
                'value'        => $requestUrl->getParam('home', reset($homes)),
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
        }

        $formControls = $this->createFormControls();
        $formControls->add([
            $this->registerSubmitButton($btnLabel),
            $this->createCancelButton()
        ]);

        $this->addHtml($formControls);
    }

    protected function onSuccess()
    {
        $requestUrl = Url::fromRequest();
        $conn = Dashboard::getConn();

        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/new-pane') {
            $selectedHome = $this->getPopulatedValue('home');
            if (! $selectedHome || $selectedHome === self::CREATE_NEW_HOME) {
                $selectedHome = $this->getPopulatedValue('new_home');
            }

            $currentHome = new DashboardHome($selectedHome);
            if ($this->dashboard->hasEntry($currentHome->getName())) {
                $currentHome = clone $this->dashboard->getEntry($currentHome->getName());
                if ($currentHome->getName() !== $this->dashboard->getActiveHome()->getName()) {
                    $currentHome->setActive()->loadDashboardEntries();
                }
            }

            $pane = new Pane($this->getPopulatedValue('title'));
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
        } else { // New home
            $home = new DashboardHome($this->getPopulatedValue('title'));
            if ($this->dashboard->hasEntry($home->getName())) {
                Notification::error(sprintf(t('Dashboard home "%s" already exists'), $home->getName()));
                return;
            }

            $this->dashboard->manageEntry($home);

            Notification::success('Add dashboard home successfully');
        }
    }
}
