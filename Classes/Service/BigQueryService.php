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
        if (empty($this->settings['keyFilePath']) || empty($this->settings['dataset']) || empty($this->settings['table'])) {
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

        $options = [
            'schema' => $this->settings['tableSchema'],
            'timePartitioning' => [
                'type' => 'DAY',
                'field' => 'date'
            ]
        ];

        if (!empty($this->settings['expirationMs'])) {
            $options['timePartitioning']['expirationMs'] = $this->settings['expirationMs'];
        }

        $table = $dataset->createTable($tableId, $options);

        if (! $table instanceof Table) {
            throw new Exception('BigQuery Table could not be created.');
        }

        return $table;
    }

    protected function getDataset(): Dataset
    {
        return $this->getClient()->dataset($this->settings['dataset']);
    }
}
