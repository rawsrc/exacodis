<?php

declare(strict_types=1);

use Exacodis\Pilot;

$helpers = [];

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

//region not equal
$not_equal = function(mixed $to): void {
    /** @var Pilot $this */
    if ($this->current_runner->getResult() !== $to) {
        $this->addSuccess('notEqual');
    } else {
        $this->addFailure(expected: 'Not equal to: '.print_r($to, true));
    }
};
$helpers['assertNotEqual'] = $not_equal;
//endregion

//region is bool
$is_bool = function(): void {
    /** @var Pilot $this */
    if (is_bool($this->current_runner->getResult())) {
        $this->addSuccess('isBoolean');
    } else {
        $this->addFailure(expected: 'Boolean');
    }
};
$helpers['assertIsBool'] = $is_bool;
//endregion

//region is int
$is_int = function(): void {
    /** @var Pilot $this */
    if (is_int($this->current_runner->getResult())) {
        $this->addSuccess('isInteger');
    } else {
        $this->addFailure(expected: 'Integer');
    }
};
$helpers['assertIsInt'] = $is_int;
//endregion

//region is float
$is_float = function(): void {
    /** @var Pilot $this */
    if (is_float($this->current_runner->getResult())) {
        $this->addSuccess('isFloat');
    } else {
        $this->addFailure(expected: 'Float');
    }
};
$helpers['assertIsFloat'] = $is_float;
//endregion

//region is array
$is_array = function(): void {
    /** @var Pilot $this */
    if (is_array($this->current_runner->getResult())) {
        $this->addSuccess('isArray');
    } else {
        $this->addFailure(expected: 'Array');
    }
};
$helpers['assertIsArray'] = $is_array;
//endregion

//region is string
$is_string = function(): void {
    /** @var Pilot $this */
    if (is_string($this->current_runner->getResult())) {
        $this->addSuccess('isString');
    } else {
        $this->addFailure(expected: 'String');
    }
};
$helpers['assertIsString'] = $is_string;
//endregion

//region is scalar
$is_scalar = function(): void {
    /** @var Pilot $this */
    if (is_scalar($this->current_runner->getResult())) {
        $this->addSuccess('isScalar');
    } else {
        $this->addFailure(expected: 'Scalar');
    }
};
$helpers['assertIsScalar'] = $is_scalar;
//endregion

//region is object
$is_object = function(): void {
    /** @var Pilot $this */
    if (is_object($this->current_runner->getResult())) {
        $this->addSuccess('isObject');
    } else {
        $this->addFailure(expected: 'object');
    }
};
$helpers['assertIsObject'] = $is_object;
//endregion

//region is resource
$is_resource = function(): void {
    /** @var Pilot $this */
    if (is_resource($this->current_runner->getResult())) {
        $this->addSuccess('isResource');
    } else {
        $this->addFailure(expected: 'Resource');
    }
};
$helpers['assertIsResource'] = $is_resource;
//endregion

//region is instance of
$iof = function(object|string $class, string $test_name = 'instanceOf') {
    /** @var Pilot $this */
    if (is_string($class)) {
        if (is_a($this->current_runner->getResult(), $class)) {
            $this->addSuccess($test_name);
        } else {
            $this->addFailure(expected: $test_name.': '.$class);
        }
    } elseif ($this->current_runner->getResult() instanceof $class) {
        $this->addSuccess($test_name);
    } else {
        $this->addFailure(expected: $test_name.': '.$class::class);
    }
};
$helpers['assertIsInstanceOf'] = $iof;
//endregion

//region exception
$exception = function(object|string $class = 'Exception') use ($iof): void {
    $iof($class, 'exception');
};
$helpers['assertException'] = $exception;
//endregion

//region not is instance of
$not_iof = function(object|string $class, string $test_name = 'notInstanceOf') {
    /** @var Pilot $this */
    if (is_string($class)) {
        if (is_a($this->current_runner->getResult(), $class)) {
            $this->addFailure(expected: $test_name.': '.$class);
        } else {
            $this->addSuccess($test_name);
        }
    } elseif ($this->current_runner->getResult() instanceof $class) {
        $this->addFailure(expected: $test_name.': '.$class::class);
    } else {
        $this->addSuccess($test_name);
    }
};
$helpers['assertIsNotInstanceOf'] = $not_iof;
//endregion

//region not an exception
$not_an_exception = function(object|string $class = 'Exception') use ($not_iof) {
    $not_iof($class, 'notException');
};
$helpers['assertNotException'] = $not_an_exception;
//endregion

//region in
$in = function(array $values) {
    /** @var Pilot $this */
    if (in_array($this->current_runner->getResult(), $values)) {
        $this->addSuccess('inArray');
    } else {
        $this->addFailure(expected: 'To be weakly one of: '.print_r($values, true));
    }
};
$helpers['assertIn'] = $in;
//endregion

//region in strict
$in_strict = function(array $values) {
    /** @var Pilot $this */
    if (in_array($this->current_runner->getResult(), $values, true)) {
        $this->addSuccess('inArrayStrict');
    } else {
        $this->addFailure(expected: 'To be strictly one of: '.print_r($values, true));
    }
};
$helpers['assertInStrict'] = $in_strict;
//endregion

//region not in
$not_in = function(array $values) {
    /** @var Pilot $this */
    if ( ! in_array($this->current_runner->getResult(), $values)) {
        $this->addSuccess('notInArray');
    } else {
        $this->addFailure(expected: 'Not to be weakly one of: '.print_r($values, true));
    }
};
$helpers['assertNotIn'] = $not_in;
//endregion

//region not in strict
$not_in_strict = function(array $values) {
    /** @var Pilot $this */
    if ( ! in_array($this->current_runner->getResult(), $values, true)) {
        $this->addSuccess('notInArrayStrict');
    } else {
        $this->addFailure(expected: 'Not to be strictly one of: '.print_r($values, true));
    }
};
$helpers['assertNotInStrict'] = $not_in_strict;
//endregion

//region count
$count = function(int $nb) {
    /** @var Pilot $this */
    $result = $this->current_runner->getResult();
    if (is_countable($result) && (count($result) === $nb)) {
        $this->addSuccess('count');
    } else {
        $this->addFailure(expected: "Countable and number of elements: {$nb}");
    }
};
$helpers['assertCount'] = $count;
//endregion

return $helpers;