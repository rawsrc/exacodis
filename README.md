# **Exacodis**

`2021-09-05` `PHP 8.0+` `v.1.0.0`

## **A PHP TEST ENGINE**

`Exacodis` is a very minimalist test engine for PHP. This engine is really far from 
others tools as it is very simple to use. No complex architecture, not even a huge 
test engine, just the basic (and a litle bit more) to help you to validate your PHP code.

Just 3 classes : 
1. One to pilot the tests (called `Pilot`),
2. One to encapsulate and execute the test code (called `Runner`)
3. One to produce the report (called `Report`)

And many helpers to check the returned values against the expected types and/or values.
The helpers are not exhaustive, you'll be able to create easily yours.

**IMPORTANT**

Please note that your source code for tests must be perfectly clean: you can't
override a test run or a result nor a resource.<br>
If you do, then the code will fail with an `Exception` until you fix the code. 

**HOW TO USE**

I will directly use `Exacodis` to test itself as a learning support. 

As `Exacodis` is a very lightweight engine, it's your responsibility to set up 
a test environment. Clearly, it's as simple as : 

```php
<?php

declare(strict_types=1);

include 'Runner.php';
include 'Pilot.php';
include 'Report.php';

use Exacodis\Pilot;
```
For projects with many classes, you must tell PHP how to load your classes by 
defining an autoloader or use an existing one. 

That's enough to start to test your code.

*CONCEPT*

- The `Pilot` class does absolutely everything. You do not have to use the 2 
other classes; `Runner` and `Report`.
- All the test code must be encapsulated in a `Closure` that **MUST** return a value.
- The helpers you can create are used for the `assert` part of the engine. 
You are free to create as many assertions as you want. There are already many 
helpers included in the standard library.

*LET'S START*
```php
// create new project
$pilot = new Pilot('Exacodis - A PHP minimalist test framework');
$pilot->injectStandardHelpers();
```
- RESOURCES

To use everything you need to test your code, the engine is able to store and 
retrieve any resource you want (objects, arrays, scalar values...).
Each resource must have a unique name, and you can't override it by error.
```php
//region resources
$pilot->addResource('year', 2021);
$pilot->addResource('years', [2020, 2021]);
$pilot->addResource('is_leap', false);
$pilot->addResource('current_month', 'september');

$pilot->addResource('dummy_array_data', [
    $pilot->getResource('year'), 
    $pilot->getResource('is_leap'), 
    $pilot->getResource('current_month')
]);
//endregion
```
- TEST

As written, a test is a simple snippet of code encapsulated in a `Closure` that
return a value :
```php
$pilot->run(
    id: '001',
    description: 'Resource extractor (integer) - assertIsInt assertEqual assertNotEqual assertIn assertInStrict',
    test: function() use ($pilot) {
        return $pilot->getResource('year');
    }
);
```
- ASSERTIONS

Assertions use the standard helpers and of course yours.
```php
$pilot->assertIsInt();
$pilot->assertEqual(2021);
$pilot->assertNotEqual(2000);
$pilot->assertIn(['2021']);
$pilot->assertInStrict([2021]);
```
You must know that assertions (`->assertXXX`) always apply to the latest run.
If you want to change the current runner, then you can ask for it:
```php
$pilot->setCurrentRunnerTo('001');
```
Then the assertions will apply to this one.
  
- COMPLEX TEST CODE

You can write your test code as raw code, especially for complex test code.
```php
// manual test
$stats = $pilot->getStats();
unset($stats['milliseconds'], $stats['hms']);
// we encapsulate the code in a closure to use it as a test
$pilot->run(
    id: '007',
    description: 'check the count',
    test: function() use ($stats) {
        return $stats;
    }
);
// then we lead our assertions
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
```
- REPORT

The engine compute internally the data and, you can ask for a HTML report, as
simply as:
```php
$pilot->createReport();
```

Enjoy!

**rawsrc**