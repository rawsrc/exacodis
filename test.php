<?php

declare(strict_types=1);

include 'Runner.php';
include 'Pilot.php';
include 'Report.php';

use Exacodis\Pilot;

$pilot = new Pilot('Exacodis - A PHP minimalist test framework');
$pilot->injectStandardHelpers();

//region resources
$pilot->addResource('year', 2021);
$pilot->addResource('years', [2020, 2021]);
$pilot->addResource('is_leap', false);
$pilot->addResource('current_month', 'september');

$pilot->addResource('dummy_array_data', [
    $pilot->getResource('year'), $pilot->getResource('is_leap'), $pilot->getResource('current_month')
]);
//endregion

$pilot->run(
    id: '001',
    description: 'Resource extractor (integer) - assertIsInt assertEqual assertNotEqual assertIn assertInStrict',
    test: function() use ($pilot) {
        return $pilot->getResource('year');
    }
);
$pilot->assertIsInt();
$pilot->assertEqual(2021);
$pilot->assertNotEqual(2000);
$pilot->assertIn(['2021']);
$pilot->assertInStrict([2021]);

$pilot->run(
    id: '002',
    description: 'Resource extractor (array) - assertIsArray + assertEqual',
    test: function() use ($pilot) {
        return $pilot->getResource('years');
    }
);
$pilot->assertIsArray();
$pilot->assertEqual([2020, 2021]);

$pilot->run(
    id: '003',
    description: 'Resource extractor (composed resource from other already defined resources) - assertIsArray + assertEqual',
    test: function() use ($pilot) {
        return $pilot->getResource('dummy_array_data');
    }
);
$pilot->assertIsArray();
$pilot->assertEqual([2021, false, 'september']);

$pilot->run(
    id: '004',
    description: 'Resource extractor (string) - Dereference array - assertIsString + assertEqual',
    test: function() use ($pilot) {
        return $pilot->getResource('dummy_array_data')[2];
    }
);
$pilot->assertIsString();
$pilot->assertEqual('september');

$pilot->run(
    id: '005',
    description: 'assertIsBool + assertEqual + assertNotEqual',
    test: fn() => true
);
$pilot->assertIsBool();
$pilot->assertEqual(true);
$pilot->assertNotEqual(false);

$pilot->run(
    id: '006',
    description: 'Exception interceptor - assertException (object) + assertException (string) + assertException (specific InvalidArgumentException)',
    test: function() use ($pilot) {
        throw new InvalidArgumentException();
    }
);
$pilot->assertException(new Exception());
$pilot->assertException(Exception::class);
$pilot->assertException(InvalidArgumentException::class);
$pilot->assertEqual(0);

// manual test
$stats = $pilot->getStats();
unset($stats['milliseconds'], $stats['hms']);
$pilot->run(
    id: '007',
    description: 'check the count',
    test: function() use ($stats) {
        return $stats;
    }
);
$pilot->assertIsArray();
$pilot->assertEqual([
    'nb_runs' => 6,
    'passed_runs' => 5,
    'failed_runs' => 1,
    'passed_runs_percent' => round(5/6*100, 2),
    'failed_runs_percent' => 100-round(5/6*100, 2),
    'nb_assertions' => 18,
    'passed_assertions' => 17,
    'failed_assertions' => 1,
    'passed_assertions_percent' => round(17/18*100, 2),
    'failed_assertions_percent' => 100-round(17/18*100, 2)
]);

$pilot->createReport();