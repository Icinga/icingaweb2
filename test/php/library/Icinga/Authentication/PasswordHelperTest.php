<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Authentication\User;

use Icinga\Authentication\PasswordHelper;
use Icinga\Test\BaseTestCase;

class PasswordHelperTest extends BaseTestCase
{
    const TEST_PASSWORD = 'icinga';
    const TEST_PASSWORD_LONG = 'icashd89as9tgd897asztd78asztd87astd87astda8s7tda8s7tdas0duasdasdaasdua8sdz8a9szd97gjml';

    const TEST_PASSWORD_HASHED_BLOWFISH_1 = '$2y$15$iYB4TlPDcZWRyZZ/OhQc/uJRF2ElEDdvYwx3o8Lo3HMyGmeRWVYZu';
    const TEST_PASSWORD_HASHED_BLOWFISH_2 = '$2y$10$/avFxk1nhflzp1SjQAyz5OGkRj3XdTvlvEfsFS3jnK.RiFoXV12xW';
    const TEST_PASSWORD_HASHED_BLOWFISH_LONG = '$2y$10$TKeUw2FFmhxhG4ed7Fy4CuPMY5h3wi6igKgs3j6XOHwP6Tupe4qbu';

    // @codingStandardsIgnoreLine
    const TEST_PASSWORD_HASHED_SHA256 = '$6$rounds=5000$ca15a2843471ce6a$pZobBdfC0AhF4sT5FPBH6WnPYHEkXB/d4ihXuSmETqLGMV.PMVLMuTZHO4wTU8BL48onyfmT5zHC.fOenOZmH1';

    /**
     * Test hash from the old Icinga Web 2 MD5 hash type
     *
     * Stored for hex2bin() / pack('H*', xx)
     *
     * @var string
     */
    const TEST_PASSWORD_OLD_MD5 = '243124DBEC64CECBB8E0932434525A51424B744D313634757A543445483839496130';

    public function testGenerateSalt()
    {
        $this->assertRegExp(
            '~^[a-f0-9]{16}$~i',
            PasswordHelper::generateSalt(),
            'A hex based salt with 16 chars must be returned'
        );
    }

    public function testHash()
    {
        foreach (array(self::TEST_PASSWORD, self::TEST_PASSWORD_LONG) as $pw) {
            $hashed = PasswordHelper::hash($pw);

            $this->assertRegExp(
                '~^\$\d\w*\$(?:rounds=\d+\$)?~',
                $hashed,
                'Hash output must look like a hash: ' . $hashed
            );

            $this->assertEquals(
                crypt($pw, $hashed),
                $hashed,
                'New hashed password must validate via crypt: ' . $hashed
            );
        }
    }

    public function testHashFallback()
    {
        $hashed = PasswordHelper::hash(self::TEST_PASSWORD, PasswordHelper::PASSWORD_ALGO_FALLBACK);

        $this->assertRegExp(
            '~^\$6\$rounds=\d+\$?~',
            $hashed,
            'Hash output must look like a SHA-512 hash: ' . $hashed
        );

        $this->assertEquals(
            crypt(self::TEST_PASSWORD, $hashed),
            $hashed,
            'New hashed password must validate via crypt: ' . $hashed
        );
    }

    public function testVerify()
    {
        $pws = array(
            self::TEST_PASSWORD_HASHED_BLOWFISH_1    => self::TEST_PASSWORD,
            self::TEST_PASSWORD_HASHED_BLOWFISH_2    => self::TEST_PASSWORD,
            self::TEST_PASSWORD_HASHED_BLOWFISH_LONG => self::TEST_PASSWORD_LONG,
            self::TEST_PASSWORD_HASHED_SHA256        => self::TEST_PASSWORD,
            pack('H*', self::TEST_PASSWORD_OLD_MD5)  => self::TEST_PASSWORD,
        );

        foreach ($pws as $hash => $pw) {
            $this->assertTrue(
                PasswordHelper::verify($pw, $hash),
                'Password must be validated against its hash'
            );
        }
    }
}
