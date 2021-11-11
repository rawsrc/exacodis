<?php

declare(strict_types=1);

include_once 'Pilot.php';

use Exacodis\{
    Pilot, Report, Runner
};

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

//region dynamic assert
$pilot->assert(
    test: fn() => count($pilot->getResource('dummy_array_data')) === 3,
    test_name: 'Dynamic assertion using manual count',
    expected: 3
);
//endregion

//region private/protected methods
class Foo
{
    const BAR = 'bar';

    private function abc(): string
    {
        return 'abc';
    }

    private function def(int $p): int
    {
        return 2*$p;
    }

    private function hij(int $p, int $q): int
    {
        return 2*$p+4*$q;
    }

    protected function klm(): string
    {
        return 'klm';
    }

    protected function nop(int $p): int
    {
        return 2*$p;
    }

    protected function qrs(int $p, int $q): int
    {
        return 2*$p+4*$q;
    }

    private static function tuv(): string
    {
        return 'tuv';
    }
}

$foo = new Foo();
$pilot->runClassMethod(
    id: '008',
    description: 'private method unit test using directly an instance of Foo',
    class: $foo,
    method: 'abc',
);
$pilot->assertIsString();
$pilot->assertEqual('abc');

$pilot->runClassMethod(
    id: '009',
    description: 'private method unit test using string notation for the class Foo',
    class: 'Foo',
    method: 'abc',
);
$pilot->assertIsString();
$pilot->assertEqual('abc');

$pilot->runClassMethod(
    id: '010',
    description: 'private method unit test using short string notation for the class Foo and the method abc',
    class: 'Foo::abc',
);
$pilot->assertIsString();
$pilot->assertEqual('abc');

$pilot->runClassMethod(
    id: '011',
    description: 'private method unit test with one parameter',
    class: 'Foo',
    method: 'def',
    params: [25]
);
$pilot->assertIsInt();
$pilot->assertEqual(50);

$pilot->runClassMethod(
    id: '012',
    description: 'private method unit test with two parameters',
    class: 'Foo',
    method: 'hij',
    params: ['p' => 25, 'q' => 50]
);
$pilot->assertIsInt();
$pilot->assertEqual(250);


$pilot->runClassMethod(
    id: '013',
    description: 'protected method unit test using directly an instance of Foo',
    class: $foo,
    method: 'klm',
);
$pilot->assertIsString();
$pilot->assertEqual('klm');

$pilot->runClassMethod(
    id: '014',
    description: 'protected method unit test using string notation for the class Foo',
    class: 'Foo',
    method: 'klm',
);
$pilot->assertIsString();
$pilot->assertEqual('klm');

$pilot->runClassMethod(
    id: '015',
    description: 'protected method unit test using short string notation for the class Foo and the method abc',
    class: 'Foo::klm',
);
$pilot->assertIsString();
$pilot->assertEqual('klm');

$pilot->runClassMethod(
    id: '016',
    description: 'protected method unit test with one parameter',
    class: 'Foo',
    method: 'nop',
    params: [25]
);
$pilot->assertIsInt();
$pilot->assertEqual(50);

$pilot->runClassMethod(
    id: '017',
    description: 'protected method unit test with two parameters',
    class: 'Foo',
    method: 'qrs',
    params: [25, 50]
);
$pilot->assertIsInt();
$pilot->assertEqual(250);

$pilot->runClassMethod(
    id: '018',
    description: 'private static method unit test',
    class: 'Foo::tuv',
);
$pilot->assertIsString();
$pilot->assertEqual('tuv');
//endregion

// manual test
$stats = $pilot->getStats();
unset($stats['milliseconds'], $stats['hms']);
$pilot->run(
    id: '100',
    description: 'check the count',
    test: fn() => $stats
);
$pilot->assertIsArray();
$pilot->assertEqual([
    'nb_runs' => 18,
    'passed_runs' => 18,
    'failed_runs' => 0,
    'passed_runs_percent' => 100.0,
    'failed_runs_percent' => 0.0,
    'nb_assertions' => 44,
    'passed_assertions' => 44,
    'failed_assertions' => 0,
    'passed_assertions_percent' => 100.0,
    'failed_assertions_percent' => 0.0
]);

$pilot->createReport();