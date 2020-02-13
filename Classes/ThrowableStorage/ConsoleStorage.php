<?php

declare(strict_types=1);

namespace t3n\FlowLog\ThrowableStorage;

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Log\Exception\CouldNotOpenResourceException;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

class ConsoleStorage implements ThrowableStorageInterface
{

    /**
     * @var false|resource
     */
    protected $streamHandle;

    /**
     * @param mixed[] $options
     */
    public static function createWithOptions(array $options): ThrowableStorageInterface
    {
        if (! array_key_exists('streamName', $options)) {
            throw new \Exception('A stream name must be set');
        }

        $streamName = $options['streamName'];

        if (strpos($streamName, 'php://') === 0) {
            $streamName = substr($streamName, 6);
        }
        return new static('php://' . $streamName);
    }

    public function __construct(string $streamName)
    {
        $this->streamHandle = fopen($streamName, 'w');

        if (! is_resource($this->streamHandle)) {
            throw new CouldNotOpenResourceException('Could not open stream "' . $streamName . '" for write access.', 1310986609);
        }
    }

    /**
     * @param mixed[] $additionalData
     */
    public function logThrowable(\Throwable $throwable, array $additionalData = []): string
    {
        $bootstrap = Bootstrap::$staticObjectManager->get(Bootstrap::class);
        /** @var ConfigurationManager $configurationManager */
        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);

        $serviceContext = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 't3n.FlowLog.serviceContext');

        $data = [
            'eventTime' => (new \DateTime('now'))->format(DATE_RFC3339),
            'serviceContext' => $serviceContext,
            'message' => sprintf('PHP Warning: %s' . PHP_EOL . 'Stack trace:' . PHP_EOL . '%s', $throwable->getMessage(), $throwable->getTraceAsString()),
            'context' => [
                'httpRequest' => $this->getHttpRequestContext(),
                'reportLocation' => [
                    'filePath' => $throwable->getFile(),
                    'lineNumber' => $throwable->getLine(),
                    'functionName' => self::getFunctionNameForTrace($throwable->getTrace()),
                ],
            ]
        ];

        $output = json_encode($data);

        if (is_resource($this->streamHandle)) {
            fwrite($this->streamHandle, $output . PHP_EOL);
        }

        return $output;
    }

    /**
     * @param mixed[]|null $trace
     */
    private static function getFunctionNameForTrace(?array $trace = null): string
    {
        if ($trace === null) {
            return '<unknown function>';
        }
        if (empty($trace[0]['function'])) {
            return '<none>';
        }
        $functionName = [$trace[0]['function']];
        if (isset($trace[0]['type'])) {
            $functionName[] = $trace[0]['type'];
        }
        if (isset($trace[0]['class'])) {
            $functionName[] = $trace[0]['class'];
        }
        return implode('', array_reverse($functionName));
    }

    /**
     * @return mixed[]
     */
    public function getHttpRequestContext(): array
    {
        if (! (Bootstrap::$staticObjectManager instanceof ObjectManagerInterface)) {
            return [];
        }

        $bootstrap = Bootstrap::$staticObjectManager->get(Bootstrap::class);
        /** @var Bootstrap $bootstrap */
        $requestHandler = $bootstrap->getActiveRequestHandler();
        if (! $requestHandler instanceof HttpRequestHandlerInterface) {
            return [];
        }
        $request = $requestHandler->getHttpRequest();
        $response = $requestHandler->getHttpResponse();

        return [
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'userAgent' => $request->getHeader('User-Agent')[0],
            'responseStatusCode' => $response->getStatusCode(),
        ];
    }

    public function setBacktraceRenderer(\Closure $backtraceRenderer): void
    {
        // We don't need the backtrace Renderer at all
    }

    public function setRequestInformationRenderer(\Closure $requestInformationRenderer): void
    {
        // We don't need the backtrace Renderer at all
    }
}
