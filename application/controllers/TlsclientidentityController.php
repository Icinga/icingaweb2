<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Exception;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\Forms\Config\Tls\ClientIdentity\CreateForm;
use Icinga\Forms\Config\Tls\ClientIdentity\EditForm;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Web\Controller;
use Icinga\Web\Notification;

/**
 * Manage TLS client identities
 */
class TlsclientidentityController extends Controller
{
    public function init()
    {
        $this->assertPermission('config/application/tlscert');

        parent::init();
    }

    public function createAction()
    {
        $this->view->form = $form = new CreateForm();
        $form->setRedirectUrl('tlsclientidentity/edit')
            ->handleRequest();

        $this->addTitleTab(
            $this->translate('New Client Identity'),
            $this->translate('Create A New TLS Client Identity')
        );

        $this->render('form');
    }

    public function editAction()
    {
        $this->view->editForm = $editForm = new EditForm();
        $name = $this->params->getRequired('name');
        $editForm->setOldName($name)
            ->setRedirectUrl('tlsclientidentity/edit')
            ->handleRequest();

        $rawIdentity = LocalFileStorage::common('tls/clientidentities')->read(bin2hex($name) . '.pem');

        preg_match(
            '/-+BEGIN CERTIFICATE-+.+?-+END CERTIFICATE-+/s',
            $rawIdentity,
            $cert
        );

        $this->view->cert = array(
            'info'      => openssl_x509_parse($cert[0]),
            'sha1'      => openssl_x509_fingerprint($cert[0], 'sha1'),
            'sha256'    => openssl_x509_fingerprint($cert[0], 'sha256'),
        );

        preg_match(
            '/-+BEGIN PRIVATE KEY-+.+?-+END PRIVATE KEY-+/s',
            $rawIdentity,
            $key
        );

        $keyDetails = openssl_pkey_get_details(openssl_pkey_get_private($key[0]));
        $pubKey = base64_decode(preg_replace('/-+(?:BEGIN|END) PUBLIC KEY-+/', '', $keyDetails['key']));
        $this->view->pubKey = array(
            'sha1'      => hash('sha1', $pubKey),
            'sha256'    => hash('sha256', $pubKey)
        );

        $this->addTitleTab(
            $this->translate('Edit Client Identity'),
            sprintf($this->translate('Edit TLS Client Identity "%s"'), $name)
        );
    }

    public function removeAction()
    {
        $clientIdentities = LocalFileStorage::common('tls/clientidentities');

        $name = $this->params->getRequired('name');
        $fileName = bin2hex($name) . '.pem';
        $clientIdentities->resolvePath($fileName, true);

        $this->view->form = $form = new ConfirmRemovalForm();
        $form->setOnSuccess(function (ConfirmRemovalForm $form) use ($name, $fileName, $clientIdentities) {
                try {
                    $clientIdentities->delete($fileName);
                } catch (Exception $e) {
                    $form->error($e->getMessage());
                    return false;
                }

                Notification::success(
                    sprintf(t('TLS client identity "%s" successfully removed'), $name)
                );
                return true;
            })
            ->setRedirectUrl('config/tls')
            ->handleRequest();

        $this->addTitleTab(
            $this->translate('Remove Client Identity'),
            sprintf($this->translate('Remove TLS Client Identity "%s"'), $name)
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
