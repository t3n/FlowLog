<?php

declare(strict_types=1);

namespace t3n\FlowLog\Backend;

use Google\Cloud\BigQuery\Date;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Backend\ConsoleBackend;

class JSONConsoleLogger extends ConsoleBackend
{
    /**
     * @Flow\InjectConfiguration(package="t3n.FlowLog.serviceContext")
     *
     * @var mixed[]
     */
    protected $serviceContext;

    public function open(): void
    {
        parent::open();
        $this->severityLabels = [
            LOG_EMERG => 'emergency',
            LOG_ALERT => 'alert',
            LOG_CRIT => 'critical',
            LOG_ERR => 'error',
            LOG_WARNING => 'warning  ',
            LOG_NOTICE => 'notice',
            LOG_INFO => 'info',
            LOG_DEBUG => 'debug',
        ];
    }

    /**
     * @param mixed $additionalData A variable containing more information about the event to be logged
     */
    public function append(string $message, string $packageKey, string $className, string $methodName, int $severity = LOG_INFO, $additionalData = null): void
    {
        if ($severity > $this->severityThreshold) {
            return;
        }

        $additionalData = $additionalData ?? [];

        if ($packageKey !== null) {
            $additionalData['packageKey'] = $packageKey;
        }

        if ($className !== null) {
            $additionalData['className'] = $className;
        }

        if ($methodName !== null) {
            $additionalData['methodName'] = $methodName;
        }

        $severityLabel = $this->severityLabels[$severity] ?? 'unknown';
        try {
            $data = [
                'severity' => $severityLabel,
                'service' => $this->serviceContext['service'],
                'version' => $this->serviceContext['version'],
                'message' => $message,
                'additionalData' => json_encode($additionalData, JSON_THROW_ON_ERROR),
                'date' => new Date(new \DateTime('now')),
                'datetime' => new \DateTime('now')
            ];
            $output = json_encode($data);
        } catch (Exception $e) {
            $data = [
                'severity' => $this->severityLabels[LOG_WARNING],
                'service' => $this->serviceContext['service'],
                'version' => $this->serviceContext['version'],
                'message' => 'Could not decode additional data of log message.',
                'additionalData' => [
                    'previousLog' => [
                        'message' => $message,
                        'severity' => $severityLabel,
                    ],
                    'stackTrace' => $e->getTraceAsString()
                ],
                'date' => new Date(new \DateTime('now')),
                'datetime' => new \DateTime('now')
            ];
            $output = json_encode($data);
        } finally {
            if (is_resource($this->streamHandle)) {
                fwrite($this->streamHandle, $output . PHP_EOL);
            }
        }
    }
}
