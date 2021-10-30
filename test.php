<?php

declare(strict_types=1);

include 'Pilot.php';

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
    description: 'Resource extractor (integer) - assertIsInt assertEqual assertNotEqual assertIn assertInStrict assertNotIn assertNotInStrict',
    test: fn() => $pilot->getResource('year')
);
$pilot->assertIsInt();
$pilot->assertEqual(2021);
$pilot->assertNotEqual(2000);
$pilot->assertIn(['2021']);
$pilot->assertInStrict([2021]);
$pilot->assertNotIn(['2025']);
$pilot->assertNotInStrict(['2021']);
//$pilot->assertNotInStrict([2021]); // as the result is an integer 2021, this assertion should fail (and it does)

$pilot->run(
    id: '002',
    description: 'Resource extractor (array) - assertIsArray + assertEqual',
    test: fn() => $pilot->getResource('years')
);
$pilot->assertIsArray();
$pilot->assertEqual([2020, 2021]);

$pilot->run(
    id: '003',
    description: 'Resource extractor (composed resource from other already defined resources) - assertIsArray + assertEqual',
    test: fn() => $pilot->getResource('dummy_array_data')
);
$pilot->assertIsArray();
$pilot->assertEqual([2021, false, 'september']);

$pilot->run(
    id: '004',
    description: 'Resource extractor (string) - Dereference array - assertIsString + assertEqual',
    test: fn() => $pilot->getResource('dummy_array_data')[2]
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
    test: fn() => throw new InvalidArgumentException()
);
$pilot->assertException(new Exception());
$pilot->assertException(Exception::class);
$pilot->assertException(InvalidArgumentException::class);
//$pilot->assertEqual(0); // as the result is an exception, this assertion should fail (and it does)

$pilot->run(
    id: '007',
    description: 'Count the values: assertCount()',
    test: fn() => $pilot->getResource('dummy_array_data')
);
$pilot->assertIsArray();
$pilot->assertCount(3);

//region dyanmic assert
$pilot->assert(
    test: fn() => count($pilot->getResource('dummy_array_data')) === 3,
    test_name: 'Dynamic assertion using manual count',
    expected: 3
);
//endregion

// manual test
$stats = $pilot->getStats();
unset($stats['milliseconds'], $stats['hms']);
$pilot->run(
    id: '008',
    description: 'check the count',
    test: fn() => $stats
);
$pilot->assertIsArray();
$pilot->assertEqual([
    'nb_runs' => 7,
    'passed_runs' => 7,
    'failed_runs' => 0,
    'passed_runs_percent' => 100.0,
    'failed_runs_percent' => 0.0,
    'nb_assertions' => 22,
    'passed_assertions' => 22,
    'failed_assertions' => 0,
    'passed_assertions_percent' => 100.0,
    'failed_assertions_percent' => 0.0
]);

$pilot->createReport();