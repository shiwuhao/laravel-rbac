<?php

namespace Rbac\Support;

use Illuminate\Http\JsonResponse;
use Rbac\Contracts\ResponseFormatter as ResponseFormatterContract;

class ResponseFormatter implements ResponseFormatterContract
{
    public function success($data = null, string $message = 'ok', int $code = 200): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function failure(string $message = 'failure', int $code = 400, $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
