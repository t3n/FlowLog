<?php

declare(strict_types=1);

namespace t3n\FlowLog\Backend;

use Google\Cloud\BigQuery\Date;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Backend\AbstractBackend;
use t3n\FlowLog\Service\BigQueryService;

class BigQueryLogger extends AbstractBackend
{
    /**
     * @Flow\Inject
     *
     * @var BigQueryService
     */
    protected $bigQueryService;

    /**
     * @Flow\InjectConfiguration(package="t3n.FlowLog.serviceContext")
     *
     * @var array
     */
    protected $serviceContext;

    /**
     * @var string
     */
    protected $loggerName;

    public function open(): void
    {
        $this->bigQueryService->getTable();
    }

    public function append(string $message, int $severity = LOG_INFO, $additionalData = [], ?string $packageKey = null, ?string $className = null, ?string $methodName = null): void
    {
        if ($packageKey !== null) {
            $additionalData['packageKey'] = $packageKey;
        }

        if ($className !== null) {
            $additionalData['className'] = $className;
        }

        if ($methodName !== null) {
            $additionalData['methodName'] = $methodName;
        }

        $logData = [
            'loggerName' => $this->loggerName,
            'service' => $this->serviceContext['service'],
            'version' => $this->serviceContext['version'],
            'severity' => $severity,
            'message' => $message,
            'additionalData' => json_encode($additionalData),
            'date' => new Date(new \DateTime('now')),
            'datetime' => new \DateTime('now')
        ];

        $this->bigQueryService->getTable()->insertRow($logData);
    }

    public function close(): void
    {
    }

    public function setLoggerName(string $loggerName): void
    {
        $this->loggerName = $loggerName;
    }
}
