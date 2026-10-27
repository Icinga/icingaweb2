<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Security\Csp\Loader;

use Icinga\Application\Hook;
use Icinga\Application\Hook\CspHook;
use Icinga\Security\Csp\AttributedCsp;
use Icinga\Security\Csp\Loader\ModuleCspLoader;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use ipl\Web\Common\Csp;

class TileServerCspHook extends CspHook
{
    public function getCspForAllUsers(): Csp
    {
        return (new Csp())->add('img-src', 'https://tiles.example.com');
    }
}

class PreferenceCspHook extends CspHook
{
    public function getCspForAllUsers(): Csp
    {
        return (new Csp())->add('img-src', 'https://cdn.example.com');
    }

    public function getCspForUser(User $user): Csp
    {
        $csp = new Csp();
        if ($user->getUsername() === 'jdoe') {
            $csp->add('img-src', 'https://jdoe.example.com');
        }

        return $csp;
    }
}

class ModuleCspLoaderTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Hook::clean();
    }

    public function tearDown(): void
    {
        Hook::clean();

        parent::tearDown();
    }

    public function testLoadForUserIncludesDirectivesForAllUsers(): void
    {
        TileServerCspHook::register();

        $this->assertSame(
            ['https://tiles.example.com'],
            $this->imgSrc((new ModuleCspLoader())->loadForUser(new User('jdoe'))),
        );
    }

    public function testLoadForUserAddsDirectivesOnlyForThatUser(): void
    {
        PreferenceCspHook::register();
        $loader = new ModuleCspLoader();

        $this->assertSame(
            ['https://cdn.example.com', 'https://jdoe.example.com'],
            $this->imgSrc($loader->loadForUser(new User('jdoe'))),
        );
        $this->assertSame(
            ['https://cdn.example.com'],
            $this->imgSrc($loader->loadForUser(new User('asmith'))),
        );
    }

    public function testLoadForAllUsersOmitsUserSpecificDirectives(): void
    {
        PreferenceCspHook::register();

        $this->assertSame(
            ['https://cdn.example.com'],
            $this->imgSrc((new ModuleCspLoader())->loadForAllUsers()),
        );
    }

    /**
     * Get the img-src values of the given CSPs combined
     *
     * @param AttributedCsp[] $attributedCsps
     *
     * @return list<string>
     */
    protected function imgSrc(array $attributedCsps): array
    {
        $csp = new Csp();
        foreach ($attributedCsps as $attributedCsp) {
            $csp->merge($attributedCsp->csp);
        }

        return $csp->getDirective('img-src');
    }
}
