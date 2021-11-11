# **Exacodis**

`2021-11-11` `PHP 8.0+` `v.1.2.0`

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
override a test run nor a result nor a resource.<br>
If you do, then the code will fail with an `Exception` until you fix the code. 

**CHANGELOG**
1. Add the possibility to test any protected/private method from a class
2. Does not break the compatibility with the previous version

**HOW TO USE**

I will directly use `Exacodis` to test itself as a learning support. 

As `Exacodis` is a very lightweight engine, it's your responsibility to set up 
a test environment. Clearly, it's as simple as : 

```php
<?php

declare(strict_types=1);

include 'Pilot.php';

use Exacodis\Pilot;
```
For projects with many classes, you must tell PHP how to load your classes either 
by including them or by defining an autoloader. 

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
returns a value :
```php
$pilot->run(
    id: '001',
    description: 'Resource extractor (integer) - assertIsInt assertEqual assertNotEqual assertIn assertInStrict',
    test: fn() => $pilot->getResource('year')
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
Then the assertions will apply to this one (see below: NESTED RUNS)

- DYNAMIC ASSERTION

Here's the way to write a dynamic test:
```php
$pilot->assert(
    test: fn() => count($pilot->getRunner('select_001')->getResult()) === 2,
    test_name: 'Count the records',
    expected: 2,    
);
```

- COMPLEX TEST CODE

You can write your test code as raw code, especially for complex test code.
```php
// manual test
$stats = $pilot->getStats();
unset($stats['milliseconds'], $stats['hms']);
// we encapsulate the result in a closure to use it for testing purpose
$pilot->run(
    id: '007',
    description: 'check the count',
    test: fn() => $stats;
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
- TESTING PROTECTED/PRIVATE METHODS IN CLASSES

To be able to test any protected or private method, you must use `$pilot->runClassMethod(...)` 
instead of `$pilot->run(...)`.
The signature of the method is:
```php
public function runClassMethod(
    int|string|null $id,
    object|string $class,
    string $description = '',
    ?string $method = null,
    array $params = [],
)
```
Please note:
- if the class has a complex constructor with required arguments, then you must
provide a clean instance to the var `$class`
- in other cases, `$class` can be a string like `Foo` or even with the method 
included: `Foo::method`
- The array `$params` must have all the required parameters for the invocation 
of the method. It's also compatible with named parameters.

All the rest is similar to the method `$pilot->run()`.

Let's have an example from the php test file:
Here all tests are equivalent:
```php
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
```
Have a look at the call of a private method with two parameters
```php
$pilot->runClassMethod(
    id: '012',
    description: 'private method unit test with two parameters',
    class: 'Foo',
    method: 'hij',
    params: ['p' => 25, 'q' => 50]
);
$pilot->assertIsInt();
$pilot->assertEqual(250);
```
The named parameters must follow the order of the defined parameters.

- REPORT

The engine compute internally the data and, you can ask for a HTML report, as
simply as:
```php
$pilot->createReport();
```
You will find in the repository two reports: one totally passed and another one failed.
You'll exactly see how the engine follows the runs and what kind of data is kept. 

- HELPERS

You can create your own helpers to validate any result using à simple `Closure`.
Have a look at:
```php
//region equal
$equal = function(mixed $to): void {
    /** @var Pilot $this */
    if ($this->current_runner->getResult() === $to) {
        $this->addSuccess('equal');
    } else {
        $this->addFailure(expected: 'Equal to: '.print_r($to, true));
    }
};
$helpers['assertEqual'] = $equal;
//endregion
```
This assertion is one of the standard library and is injected right after the 
start of a new project.
It is also possible to define a helper on the fly using 
`$pilot->addHelper($name, $closure);`.

- NESTED RUNS

For really complex tests, you can also define nested runs.
```php
$pilot->run(
    id: 'abc',
    description: 'complex nested tests',
    test: function() use ($pilot) {
        // nested run
        $pilot->run(
            id: 'def',
            description: 'nested run',
            test: function() {
                // ...
                return $foo;
            }
        )
        // careful: this applies to the (latest) run which is here 'def'
        $pilot->assertIsArray();
        
        return []; // a run MUST always return a value
    }
);
// careful, if you continue the assertions here, by default they will apply to the
// (latest) run which is still 'def'; you must change the current runner to work with the previous one
$pilot->setCurrentRunnerTo('abc');
$pilot->assertIsArray(); // now it applies to the run 'abc'
```
This is the only tricky point of `Exacodis`. This keeps the code more readable as there's 
no need to have tons of parameters for each function call.   

Enjoy!

**rawsrc**