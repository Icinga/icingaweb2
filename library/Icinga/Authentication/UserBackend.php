<?php

namespace Icinga\Authentication;

interface UserBackend
{
    public function __construct($config);

    public function hasUsername(Credentials $credentials);

    public function authenticate(Credentials $credentials);
}
