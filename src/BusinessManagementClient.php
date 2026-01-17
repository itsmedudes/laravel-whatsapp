<?php

namespace Itsmedudes\LaravelWhatsapp;

class BusinessManagementClient extends MetaClient
{
    public function getBusinessDetails(string $businessId, array $fields = []): array
    {
        $query = $this->buildFieldsQuery($fields);

        return $this->request('get', $businessId, $query);
    }

    public function listWabaPhoneNumbers(string $wabaId, array $fields = []): array
    {
        $query = $this->buildFieldsQuery($fields);

        return $this->request('get', $wabaId . '/phone_numbers', $query);
    }

    public function createMessageTemplate(string $wabaId, array $payload): array
    {
        return $this->request('post', $wabaId . '/message_templates', [], $payload);
    }

    protected function buildFieldsQuery(array $fields): array
    {
        if (empty($fields)) {
            return [];
        }

        return ['fields' => implode(',', $fields)];
    }
}
