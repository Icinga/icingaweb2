<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Forms\Announcement\AcknowledgeAnnouncementForm;
use Icinga\Forms\Announcement\AnnouncementForm;
use Icinga\Web\Announcement\AnnouncementIniRepository;
use Icinga\Web\Controller;
use Icinga\Web\Url;

class AnnouncementsController extends Controller
{
    /**
     * List all announcements
     */
    public function indexAction()
    {
        $this->getTabs()->add(
            'announcements',
            array(
                'active'    => true,
                'label'     => $this->translate('Announcements'),
                'title'     => $this->translate('List All Announcements'),
                'url'       => Url::fromPath('announcements')
            )
        );

        $repo = new AnnouncementIniRepository();
        $this->view->announcements = $repo
            ->select(array('id', 'author', 'message', 'start', 'end'))
            ->order('start');
    }

    /**
     * Create an announcement
     */
    public function newAction()
    {
        $this->assertPermission('admin');

        $form = $this->prepareForm()->add();
        $form->handleRequest();
        $this->renderForm($form, $this->translate('New Announcement'));
    }

    /**
     * Update an announcement
     */
    public function updateAction()
    {
        $this->assertPermission('admin');

        $form = $this->prepareForm()->edit($this->params->getRequired('id'));
        try {
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound($this->translate('Announcement not found'));
        }
        $this->renderForm($form, $this->translate('Update Announcement'));
    }

    /**
     * Remove an announcement
     */
    public function removeAction()
    {
        $this->assertPermission('admin');

        $form = $this->prepareForm()->remove($this->params->getRequired('id'));
        try {
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound($this->translate('Announcement not found'));
        }
        $this->renderForm($form, $this->translate('Remove Announcement'));
    }

    public function acknowledgeAction()
    {
        $this->assertHttpMethod('POST');
        $this->getResponse()->setHeader('X-Icinga-Container', 'ignore', true);
        $form = new AcknowledgeAnnouncementForm();
        $form->handleRequest();
    }

    /**
     * Assert permission admin and return a prepared RepositoryForm
     *
     * @return AnnouncementForm
     */
    protected function prepareForm()
    {
        $form = new AnnouncementForm();
        return $form
            ->setRepository(new AnnouncementIniRepository())
            ->setRedirectUrl(Url::fromPath('announcements'));
    }
}
