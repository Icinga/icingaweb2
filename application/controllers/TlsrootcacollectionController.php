<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Exception;
use Icinga\Application\Icinga;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\Forms\Config\Tls\RootCaCollection\CreateForm;
use Icinga\Forms\Config\Tls\RootCaCollection\EditForm;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Web\Controller;
use Icinga\Web\Notification;

/**
 * Manage TLS root CA certificate collections
 */
class TlsrootcacollectionController extends Controller
{
    public function init()
    {
        $this->assertPermission('config/application/tlscert');

        parent::init();
    }

    public function createAction()
    {
        $this->view->form = $form = new CreateForm();
        $form->setRedirectUrl('tlsrootcacollection/edit')
            ->handleRequest();

        $this->addTitleTab(
            $this->translate('New Certificate Collection'),
            $this->translate('Create A New TLS Root CA Certificate Collection')
        );

        $this->render('form');
    }

    public function editAction()
    {
        $this->view->form = $form = new EditForm();
        $name = $this->params->getRequired('name');
        $form->setOldName($name)
            ->setRedirectUrl('tlsrootcacollection/edit')
            ->handleRequest();

        $this->addTitleTab(
            $this->translate('Edit Certificate Collection'),
            sprintf($this->translate('Edit TLS Root CA Certificate Collection "%s"'), $name)
        );

        $this->render('form');
    }

    public function removeAction()
    {
        $rootCaCollections = LocalFileStorage::common('tls/rootcacollections');

        $name = $this->params->getRequired('name');
        $fileName = bin2hex($name) . '.pem';
        $rootCaCollections->resolvePath($fileName, true);

        $this->view->form = $form = new ConfirmRemovalForm();
        $form->setOnSuccess(function (ConfirmRemovalForm $form) use ($name, $fileName, $rootCaCollections) {
                try {
                    $rootCaCollections->delete($fileName);
                } catch (Exception $e) {
                    $form->error($e->getMessage());
                    return false;
                }

                Notification::success(
                    sprintf(t('TLS root CA certificate collection "%s" successfully removed'), $name)
                );
                return true;
            })
            ->setRedirectUrl('config/tls')
            ->handleRequest();

        $this->addTitleTab(
            $this->translate('Remove Certificate Collection'),
            sprintf($this->translate('Remove TLS Root CA Certificate Collection "%s"'), $name)
        );

        $this->render('form');
    }

    /**
     * Add primary tab with the given label and title
     *
     * @param   string  $label
     * @param   string  $title
     */
    protected function addTitleTab($label, $title)
    {
        $url = clone $this->getRequest()->getUrl();

        $this->getTabs()->add(
            preg_replace('~\A.*/~', '', $url->getPath()),
            array(
                'active'    => true,
                'label'     => $label,
                'title'     => $title,
                'url'       => $url
            )
        );
    }
}
