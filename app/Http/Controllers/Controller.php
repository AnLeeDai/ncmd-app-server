<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    // success response helper
    protected function successResponse($data, $message = 'Success', $code = 200): JsonResponse
    {
        $response = [
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ];



        return response()->json($response, $code);
    }

    // error response helper
    protected function errorResponse($message = 'Error', $code = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => null,
            'timestamp' => now()->toDateTimeString(),
        ], $code);
    }

    // pagination response helper
    protected function paginationResponse($data, $message = 'Success', $code = 200, array $extra = []): JsonResponse
    {
        $response = [
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
            'timestamp' => now()->toDateTimeString(),
        ];

        if (! empty($extra)) {
            $response['meta'] = $extra;
        }

        return response()->json($response, $code);
    }
}
