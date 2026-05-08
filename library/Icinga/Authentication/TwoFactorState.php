<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Icinga\User;
use Icinga\Web\Session\Session;
use Icinga\Web\Session\SessionNamespace;

/**
 * In-session state for a pending two-factor authentication challenge
 */
class TwoFactorState
{
    /** @var string Session namespace name to isolate all 2FA state */
    protected const SESSION_NAMESPACE = 'twofactor';

    /** @var string Session key storing the challenged User */
    protected const SESSION_CHALLENGED_USER = 'challenged_user';

    /** @var string Session key storing the raw remember-me cookie */
    protected const SESSION_REMEMBER_ME_COOKIE_DATA = 'remember_me_cookie_data';

    /** @var SessionNamespace Session namespace scoping the challenge state */
    protected SessionNamespace $session;

    /**
     * Create a new TwoFactorState
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session->getNamespace(static::SESSION_NAMESPACE);
    }

    /**
     * Store the user for whom two-factor verification was challenged
     *
     * @param User $user
     *
     * @return void
     */
    public function challenge(User $user): void
    {
        $this->session->set(static::SESSION_CHALLENGED_USER, $user);
    }

    /**
     * Clear all challenge state from the session
     *
     * Call after successful verification.
     *
     * @return void
     */
    public function completeChallenge(): void
    {
        $this->session->delete(static::SESSION_CHALLENGED_USER);
        $this->session->delete(static::SESSION_REMEMBER_ME_COOKIE_DATA);
    }

    /**
     * Check whether a two-factor challenge is pending
     *
     * @return bool
     */
    public function isChallenged(): bool
    {
        return $this->getChallengedUser() !== null;
    }

    /**
     * Set the remember-me cookie value to issue after a successful challenge
     *
     * @param string $cookieData
     *
     * @return void
     */
    public function setRememberMeCookieData(string $cookieData): void
    {
        $this->session->set(static::SESSION_REMEMBER_ME_COOKIE_DATA, $cookieData);
    }

    /**
     * Get the stored remember-me cookie value
     *
     * @return ?string null if no value was set
     */
    public function getRememberMeCookieData(): ?string
    {
        return $this->session->get(static::SESSION_REMEMBER_ME_COOKIE_DATA);
    }

    /**
     * Get the user for whom two-factor verification was challenged
     *
     * @return ?User null if no challenge is active
     */
    public function getChallengedUser(): ?User
    {
        return $this->session->get(static::SESSION_CHALLENGED_USER);
    }
}
