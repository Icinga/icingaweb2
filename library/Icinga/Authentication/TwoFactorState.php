<?php

namespace Icinga\Authentication;

use Icinga\User;
use Icinga\Web\RememberMe;
use Icinga\Web\Session;
use Icinga\Web\Session\SessionNamespace;

class TwoFactorState
{
    public const SESSION_NAMESPACE = 'twofactor';

    public const SESSION_CHALLENGED_USER = 'challenged_user';

    public const SESSION_REMEMBER_ME_COOKIE = 'remember_me_cookie';

    protected SessionNamespace $session;

    public function __construct()
    {
        $this->session = Session::getSession()->getNamespace(static::SESSION_NAMESPACE);
    }

    public function challenge(User $user): void
    {
        $this->session->set(static::SESSION_CHALLENGED_USER, $user);
    }

    public function completeChallenge(): void
    {
        $this->session->delete(static::SESSION_CHALLENGED_USER);
        $this->session->delete(static::SESSION_REMEMBER_ME_COOKIE);
    }

    public function isChallenged(): bool
    {
        return $this->getChallengedUser() !== null;
    }

    public function setRememberMeCookie(RememberMe $cookie): void
    {
        $this->session->set(static::SESSION_REMEMBER_ME_COOKIE, $cookie);
    }

    public function getRememberMeCookie(): ?RememberMe
    {
        return $this->session->get(static::SESSION_REMEMBER_ME_COOKIE);
    }

    public function getChallengedUser(): ?User
    {
        return $this->session->get(static::SESSION_CHALLENGED_USER);
    }
}
