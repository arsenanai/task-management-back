<?php

namespace app\components;

use yii\web\Response;
use yii\web\JsonResponseFormatter;

class ApiResponseFormatter extends JsonResponseFormatter
{
    /**
     * @param Response $response
     */
    protected function formatJson($response)
    {
        if ($response->data !== null) {
            $response->data = [
                'success' => $response->isSuccessful,
                'data'    => $response->isSuccessful ? $response->data : $this->formatErrorData($response->data),
            ];
        }

        parent::formatJson($response);
    }

    private function formatErrorData($data)
    {
        return [
            'name'    => $data['name'] ?? 'Error',
            'message' => $data['message'] ?? 'An internal error occurred.',
            'code'    => $data['code'] ?? 0,
            'status'  => $data['status'] ?? 500,
        ];
    }
}
