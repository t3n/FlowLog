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

You can also set the ServiceContext that is used by StackDriver:
```yaml
t3n:
  FlowLog:
    serviceContext:
      service: 'flow-app'
      version: 'master'

```

## BigQueryLogger



To enable the BigQueryLogger you have to define the dataset, table and googleKey.json to authenticate. The settings should look like this:

```yaml
t3n:
  FlowLog:
    bigQuery:
      dataset: ''t3n_flowlog'
      table: 'application_xy'
      keyFilePath: '/path/to/google/key.json'

```

Important: the BigQueryLogger expects that your `serviceContext` is set (service and version).

After that you can add as much logger as you like like this:

```yaml
Neos:
  Flow:
    log:
      psr3:
        'Neos\Flow\Log\PsrLoggerFactory':
          bigQueryLogger:
            default:
              class: 't3n\FlowLog\Backend\BigQueryLogger'
              options:
                loggerName: 'applicationXyImportLogger'
```

As you can see you have to define the option `loggerName` for each BigQueryLogger, which will end in the BigQuery table where you can query your different loggers.
