<?php
// Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+

namespace Tests\Icinga\Crypt;

use Icinga\Crypt\RSA;
use Icinga\Test\BaseTestCase;
use InvalidArgumentException;
use UnexpectedValueException;

class RSATest extends BaseTestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    function testLoadKeyThrowsExceptionIfMoreThanTwoKeysGiven()
    {
        (new RSA())->loadKey('one', 'two', 'three');
    }

    /**
     * @expectedException UnexpectedValueException
     */
    function testGetPublicKeyThrowsExceptionIfNoPublicKeySet()
    {
        (new RSA())->getPublicKey();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    function testGetPrivateKeyThrowsExceptionIfNoPrivateKeySet()
    {
        (new RSA())->getPrivateKey();
    }

    function testLoadKeyAutomaticallyDetectsThePublicAndPrivateKey()
    {
        list($privateKey, $publicKey) = RSA::keygen();

        $rsa = (new RSA())->loadKey($publicKey, $privateKey);
        $this->assertSame($privateKey, $rsa->getPrivateKey());
        $this->assertSame($publicKey, $rsa->getPublicKey());

        $rsa = (new RSA())->loadKey($privateKey, $publicKey);
        $this->assertSame($privateKey, $rsa->getPrivateKey());
        $this->assertSame($publicKey, $rsa->getPublicKey());
    }
}
