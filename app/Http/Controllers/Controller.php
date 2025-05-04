<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function sendResponse(string $message, $data = null, $extra = [], $code = 200)
    {
        $response = [
            'success' => $code >= 200 && $code < 300,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }

        return response()->json($response, $code);

    }
    protected function sendError(string $message, $errors = [], $code = 422)
{
    $response = [
        'success' => false,
        'message' => $message,
    ];

    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    return response()->json($response, $code);
}

}
