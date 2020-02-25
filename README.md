# t3n.FlowLog

This package adds a few helper to log Flow Messages.

🔧 Still Work in Progress 🔧

## ConsoleStorage

If you'd like to log your Exceptions to the console, for instance stderr or stdout, he ConsoleStorage is for you.
It will log all throwables directly to the console as JSON. The JSON is formatted to be [parsed by google Stackdriver](https://cloud.google.com/error-reporting/reference/rest/v1beta1/projects.events/report#ReportedErrorEvent).

To enable the ConsoleStorage you need to adjust your Settings.yaml like this:

```yaml
Neos:
  Flow:
    log:
      throwables:
        storageClass: t3n\FlowLog\ThrowableStorage\ConsoleStorage
        optionsByImplementation:
          't3n\FlowLog\ThrowableStorage\ConsoleStorage':
            streamName: 'stderr'
```

The StreamName could either be `stderr` or `stdout`.

## BigQueryLogger 

The BigQueryLogger is an `AbstractBackend` so that you can use it like an normal Logger like `FileBackend`. If you want to log into BigQuery, here is your 3 Step manual:

- configure BigQuery (dataset, table, keyFile)
- create a `BigQueryLogger` via `PsrLoggerFactory`
- map your custom `BigQueryLogger` via `Objects.yaml` to actually use it

### 1) Configure BigQueryLogger


```yaml
t3n:
  FlowLog:
    bigQuery:
      dataset: 't3n_flowlog'
      table: 'application_log'
      expirationMs: '7776000000' # 90 days
      keyFilePath: '/path/to/google/key.json'

```

ℹ️ All logs will be written into a partitioned table in BigQuery. This means that you have a "overall" table with multiple tables for each day. This day-tables can be deleted automatically via `expirationMs`. If you want to store your logs forever just ignore this Setting and let it blank.

### 2) Create your own Instance of BigQueryLogger

To actually use the BigQueryLogger you have to define your own in your `Settings.yaml`. The important part is `loggerName` ("applicationXyImportLogger") and the internal Name `bigQueryLogger`.

`bigQueryLogger` will be used in Step 3, `loggerName` is the actual name for the BigQueryTable (inserted for each row).

```yaml
Neos:
  Flow:
    log:
      psr3:
        'Neos\Flow\Log\PsrLoggerFactory':
          bigQueryLogger:
            class: 't3n\FlowLog\Backend\BigQueryLogger'
            options:
              loggerName: 'applicationXyImportLogger'
```

You can create as many loggers as you want. This is really usefull if you want to "split" your logs in your BigQueryTable. E.g. one Logger for Imports, one for User-Requests, etc.

### 3) Map your BigQueryLogger via Objects.yaml

Last but not least you have to define your Logging-Factory to use your BigQueryLogger.

Therefore edit your `Objects.yaml` like this:

```yaml
t3n\FlowLog\Command\ExampleCommandController: # <- adjust
  properties:
    logger:
      object:
        factoryObjectName: Neos\Flow\Log\PsrLoggerFactoryInterface
        factoryMethodName: get
        arguments:
          1:
            value: bigQueryLogger

```

Now you are able to use it:

```php
/**
 * @var Psr\Log\LoggerInterface
 */
protected $logger;

....

$this->logger->log(LogLevel::INFO, 'First log entry.', ['test' => true]);

```

![First entry in BigQuery - example](docs/bigquery_example.png 'First entry in BigQuery - example.')


## ServiceContext

ℹ️ Important if you want to log multiple applications/environments (e.g.) in BigQuery.

You should also set the ServiceContext that is used by StackDriver and BigQueryLogger:
```yaml
t3n:
  FlowLog:
    serviceContext:
      service: 'flow-app'
      version: 'master'

```
