<?php

namespace App\Exceptions;

use RuntimeException;

class BranchAlreadyHasSuperAdminException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This branch already has a super-admin.');
    }
}
