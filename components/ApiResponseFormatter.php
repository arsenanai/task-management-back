<?php

namespace app\components;

use yii\base\Component;
use yii\data\ActiveDataProvider;
use yii\web\Response;

class ApiResponseFormatter extends Component
{
    /**
     * Formats the response data.
     * @param Response $response
     * @return array
     */
    public function format(Response $response): array
    {
        if ($response->isSuccessful) {
            $formatted = [
                'success' => true,
                'data' => $response->data,
            ];

            // Check if the data is from an ActiveDataProvider with pagination
            if ($response->data instanceof ActiveDataProvider && $response->data->getPagination()) {
                $pagination = $response->data->getPagination();
                $formatted['data'] = $response->data->getModels(); // Replace data provider with models array
                $formatted['_meta'] = [
                    'totalCount' => $pagination->totalCount,
                    'pageCount' => $pagination->getPageCount(),
                    'currentPage' => $pagination->getPage() + 1,
                    'perPage' => $pagination->getPageSize(),
                ];
            }
        } else {
            $formatted = [
                'success' => false,
                'error' => [
                    'name' => $response->data['name'] ?? 'Error',
                    'message' => $response->data['message'] ?? 'An unknown error occurred.',
                    'code' => $response->data['code'] ?? 0,
                    'status' => $response->data['status'] ?? $response->statusCode,
                ],
            ];
            if (YII_DEBUG && isset($response->data['file'])) {
                $formatted['error']['debug'] = $response->data;
            }
        }

        return $formatted;
    }
}
