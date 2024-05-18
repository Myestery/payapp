<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function respondWithData(array $data, string $message, int $statusCode = 200): JsonResponse
    {
        return response()->json(
            [
                'message' => $message,
                'data' => $data
            ],
            $statusCode
        );
    }

    protected function respondWithError(string $message, int $statusCode = 400, array $errors = []): JsonResponse
    {
        $responseData = [
            'message' => $message,
        ];

        if (!empty($errors)) {
            $responseData['errors'] = $errors;
        }

        return response()->json($responseData, $statusCode);
    }
}
