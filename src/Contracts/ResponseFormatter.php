<?php

namespace Rbac\Contracts;

interface ResponseFormatter
{
    public function success($data = null, string $message = 'success', int $code = 200);

    public function failure(string $message = 'failure', int $code = 400, $data = null);
}