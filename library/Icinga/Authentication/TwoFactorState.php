<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Icinga\User;
use Icinga\Web\RememberMe;
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

    /** @var string Session key storing the temporary {@see RememberMe} instance */
    protected const SESSION_REMEMBER_ME_COOKIE = 'remember_me_cookie';

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
        $this->session->delete(static::SESSION_REMEMBER_ME_COOKIE);
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
     * Set the remember-me instance to issue after a successful challenge
     *
     * @param RememberMe $cookie Temporary remember-me instance
     *
     * @return void
     */
    public function setRememberMeCookie(RememberMe $cookie): void
    {
        $this->session->set(static::SESSION_REMEMBER_ME_COOKIE, $cookie);
    }

    /**
     * Get the stored remember-me instance
     *
     * @return ?RememberMe
     */
    public function getRememberMeCookie(): ?RememberMe
    {
        return $this->session->get(static::SESSION_REMEMBER_ME_COOKIE);
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
