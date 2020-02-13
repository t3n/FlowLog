<?php

declare(strict_types=1);

namespace t3n\FlowLog\Service;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Dataset;
use Google\Cloud\BigQuery\Table;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception;

/**
 * @Flow\Scope("singleton")
 */
class BigQueryService
{
    /**
     * @Flow\InjectConfiguration(package="t3n.FlowLog", path="bigQuery")
     *
     * @var mixed[]
     */
    protected $settings;

    /**
     * @var BigQueryClient
     */
    protected $bigQueryClient;

    protected function getClient(): BigQueryClient
    {
        if ($this->settings['keyFilePath'] === null || $this->settings['dataset'] === null || $this->settings['table'] === null) {
            throw new Exception('BigQueryLogger is not correctly configured. Make sure to set keyFilePath, dataset and table.');
        }

        if (! $this->bigQueryClient instanceof BigQueryClient) {
            $this->bigQueryClient = new BigQueryClient(['keyFilePath' => $this->settings['keyFilePath']]);
        }

        return $this->bigQueryClient;
    }

    public function getTable(): Table
    {
        $dataset = $this->getDataset();
        $tableId = $this->settings['table'];

        if ($dataset->table($tableId)->exists()) {
            return $dataset->table($tableId);
        }

        return $dataset->createTable($tableId, [
            'schema' => $this->getTableSchema(),
            'timePartitioning' => [
                'type' => 'DAY',
                'expirationMS' => '7776000000',
                'field' => 'date'
            ],
        ]);
    }

    protected function getDataset(): Dataset
    {
        return $this->getClient()->dataset($this->settings['dataset']);
    }

    /**
     * @return mixed[]
     */
    protected function getTableSchema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'loggerName',
                    'type' => 'string',
                    'mode' => 'required',
                ],
                [
                    'name' => 'service',
                    'type' => 'string',
                    'mode' => 'required',
                ],
                [
                    'name' => 'version',
                    'type' => 'string',
                    'mode' => 'required',
                ],
                [
                    'name' => 'severity',
                    'type' => 'string',
                    'mode' => 'required',
                ],
                [
                    'name' => 'message',
                    'type' => 'string',
                    'mode' => 'required',
                ],
                [
                    'name' => 'additionalData',
                    'type' => 'string',
                ],
                [
                    'name' => 'date',
                    'type' => 'date',
                    'mode' => 'required',
                ],
                [
                    'name' => 'datetime',
                    'type' => 'datetime',
                    'mode' => 'required',
                ]
            ],
        ];
    }
}
