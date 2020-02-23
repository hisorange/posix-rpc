<?php
use Kahlan\Filter\Filters;
use Kahlan\Reporter\Coverage\Exporter\Coveralls;

// It overrides some default option values.
// Note that the values passed in command line will overwrite the ones below.
$commandLine = $this->commandLine();
$commandLine->option('ff', 'default', 1);
$commandLine->option('coverage', 'default', 3);
$commandLine->option('coverage-scrutinizer', 'default', 'scrutinizer.xml');
$commandLine->option('coverage-coveralls', 'default', 'coveralls.json');

// Apply the logic to the `'reporting'` entry point.
Filters::apply($this, 'reporting', function ($next) {
    // Get the reporter called `'coverage'` from the list of reporters
    $reporter = $this->reporters()->get('coverage');

    // Abort if no coverage is available.
    if (!$reporter || !$this->commandLine()->exists('coverage-coveralls')) {
        return $next();
    }

    // Use the `Coveralls` class to write the JSON coverage into a file
    Coveralls::write([
        'collector' => $reporter,
        'file' => $this->commandLine()->get('coverage-coveralls'),
        'service_name' => 'travis-ci',
        'service_job_id' => getenv('TRAVIS_JOB_ID') ?: null
    ]);

    // Continue the chain
    return $next();
});
