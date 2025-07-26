<?php

namespace Icinga\controllers;

use Icinga\Authentication\Totp ;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatController;


class TryoutController extends CompatController
{

    public function indexAction()
    {
        $this->addContent(
            HtmlElement::create(
                'h1',
                null,
                $this->translate('Tryout Section')
            )
        );


//        $clock = new PsrClock();
//        $otp = TOTP::generate($clock);

//        $otp->getQrCodeUri()
//        $secret = $otp->getSecret();
//        $secret = '73P442OENPZ5ZUSIWR6VGHPD4XKANATHJYFCD7SVXR2KXBOS3PJY3FHCPBM3NLAB4NMOCUP7ZC53KEQJWLUCTKQXHTIGFZOVQC77M2Y';
//        $otp = TOTP::createFromSecret($secret, $clock);

        $totp = new Totp('icingaadmi');
//        if ($totp->userHasSecret()) {
            $secret = $totp->getSecret();
        $tmpSecret = $totp->generateSecret()->getTemporarySecret();
        $tmpSecret2 = $totp->generateSecret()->getTemporarySecret();

            $this->addContent(
                HtmlElement::create(
                    'div',
                    null,
                    [
                        HtmlElement::create('p', null, sprintf('The OTP secret is: %s', $secret)),
                        HtmlElement::create('p', null, sprintf('Temp OTP secret is: %s', $tmpSecret)),
                        HtmlElement::create('p', null, sprintf('Temp2 OTP secret is: %s', $tmpSecret2)),
                        HtmlElement::create('p', null, sprintf('The current OTP is: %s', $totp->getCurrentCode()))
                    ]
                )
            );
//        } else {
//            $this->addContent(
//                HtmlElement::create(
//                    'div',
//                    null,
//                    HtmlElement::create('p', null, 'No TOTP secret found for user icingaadmi')
//                )
//            );
//        }
    }
}
