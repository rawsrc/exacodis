<?php

declare(strict_types=1);

namespace Exacodis;

use Exacodis\Runner;
use Exacodis\Report;

use Closure;
use Exception;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function count;
use function is_file;

use function is_string;
use function round;

use function str_contains;

use const DIRECTORY_SEPARATOR;

/**
 * @method assertCount(int $nb)
 * @method assertEqual(mixed $to)
 * @method assertException(object|string $class = 'Exception')
 * @method assertIn(array $values)
 * @method assertInStrict(array $values)
 * @method assertIsArray()
 * @method assertIsBool()
 * @method assertIsFloat()
 * @method assertIsInt()
 * @method assertIsInstanceOf(object|string $class)
 * @method assertIsNotInstanceOf(object|string $class)
 * @method assertIsObject()
 * @method assertIsResource()
 * @method assertIsScalar()
 * @method assertIsString()
 * @method assertNotEqual(mixed $to)
 * @method assertNotException(object|string $class = 'Exception')
 * @method assertNotIn(array $values)
 * @method assertNotInStrict(array $values)
 */
class Pilot
{
    /**
     * @var int
     */
    private static int $counter = -1;
    /**
     * @var string
     */
    private string $project_title;
    /**
     * @var array
     */
    private array $helpers = [];
    /**
     * @var array [resource's name => mixed]
     */
    private array $resources = [];
    /**
     * @var array [Runner]
     */
    private array $runners = [];
    /**
     * @var Runner|null
     */
    private Runner|null $current_runner = null;
    /**
     * @var Report HTML Report Builder
     */
    private Report $report;
    /**
     * @var array
     */
    private array $stats = ['passed_assertions' => 0, 'failed_assertions' => 0];
    /**
     * @var int
     */
    private int $milliseconds = 0;

    /**
     * @param string $project_title
     * @param Report|null $report
     * @throws Exception
     */
    public function __construct(string $project_title, ?Report $report = null)
    {
        $this->project_title = $project_title;
        $this->report = $report ?? new Report($this);
    }

    /**
     * @param string $name
     * @param mixed $args
     * @return mixed
     * @throws Exception
     */
    public function __call(string $name, mixed $args): mixed
    {
        if (isset($this->helpers[$name])) {
            $helper = $this->helpers[$name];

            return $helper(...$args);
        } else {
            throw new Exception("Unknown helper: {$name}");
        }
    }

    //region helpers
    /**
     * @param string $name
     * @param Closure $helper
     */
    public function addHelper(string $name, Closure $helper): void
    {
        $helper->bindTo($this, $this);
        $this->helpers[$name] = $helper;
    }

    /**
     * @param array $helpers [name => Closure]
     */
    public function addHelpers(array $helpers): void
    {
        foreach ($helpers as $name => $closure) {
            if ($closure instanceof Closure) {
                $this->addHelper($name, $closure);
            }
        }
    }

    /**
     * By default, the standard helper library is 'stdPilotHelpers.php'
     * localised in the same directory as this file
     * @param string|null $full_path
     * @throws Exception
     */
    public function injectStandardHelpers(?string $full_path = null): void
    {
        $filename = $full_path ?? __DIR__.DIRECTORY_SEPARATOR.'stdExacodisHelpers.php';
        $this->injectHelpers($filename);
    }

    /**
     * Inject the helpers from a file
     * @param string $path
     * @throws Exception
     */
    public function injectHelpers(string $path): void
    {
        if (is_file($path)) {
            $this->addHelpers(include $path);
        } else {
            throw new Exception("Helper file not found: {$path}");
        }
    }
    //endregion

    //region Resource
    /**
     * @param string $name
     * @param mixed $resource
     * @throws Exception
     */
    public function addResource(string $name, mixed $resource): void
    {
        if (isset($this->resources[$name])) {
            throw new Exception("Resource's name: {$name} is already defined");
        } else {
            $this->resources[$name] = $resource;
        }
    }

    /**
     * @param string $name
     * @param mixed $resource
     * @throws Exception
     */
    public function overrideResource(string $name, mixed $resource): void
    {
        if (isset($this->resources[$name])) {
            $this->resources[$name] = $resource;
        } else {
            throw new Exception("Resource's name: {$name} is not defined");
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getResource(string $name): mixed
    {
        if (isset($this->resources[$name])) {
            return $this->resources[$name];
        } else {
            throw new Exception("Resource: {$name} does not exist");
        }
    }

    /**
     * @param string $name
     * @throws Exception
     */
    public function removeResource(string $name): void
    {
        if (isset($this->resources[$name])) {
            unset($this->resources[$name]);
        } else {
            throw new Exception("Resource: {$name} does not exist");
        }
    }
    //endregion

    /**
     * @param int|string|null $id
     * @param Closure $test
     * @param string|null $description
     * @return int|string Test id
     * @throws Exception("Runner's id: {$id} is already defined and locked")
     * @throws Exception
     */
    public function run(int|string|null $id, Closure $test, string $description = ''): int|string
    {
        $id = $this->getRunnerId($id);
        $runner = new Runner($test, $description);
        $runner->setId($id);
        $this->current_runner = $runner;
        $this->runners[$id] = $runner;
        $this->milliseconds += $runner->getMilliseconds();

        return $id;
    }

    /**
     * For testing purpose of protected or private methods in a class instance
     *
     * @param int|string|null $id
     * @param object|string $class
     * @param string $description
     * @param string|null $method
     * @param array $params
     * @return int|string
     * @throws ReflectionException
     * @throws Exception('The class cannot be a string, it must be an object')
     * @throws Exception('The method must not be empty')
     */
    public function runClassMethod(
        int|string|null $id,
        object|string $class,
        string $description = '',
        ?string $method = null,
        array $params = [],
    ) {
        $id = $this->getRunnerId($id);

        if (is_string($class)) {
            // intercept the short notation class::method
            if (str_contains($class, '::')) {
                [$class, $method] = explode('::', $class);
            }
            // the class constructor must not have any required parameters
            // otherwise the given class must be already built (object and not a string)
            $reflection_class = new ReflectionClass($class);
            $constructor = $reflection_class->getConstructor();
            if (($constructor !== null) && ($constructor->getNumberOfRequiredParameters()) > 0) {
                throw new Exception('The class cannot be a string, it must be an object');
            }
            $class = new $class;
        }

        if (empty($method)) {
            throw new Exception('The method must not be empty');
        }

        $reflection_method = new ReflectionMethod($class, $method);
        $reflection_method->setAccessible(true);

        if ($reflection_method->isStatic()) {
            $class = null;
        }

        $runner = new Runner(fn() => $reflection_method->invoke($class, ...$params), $description);
        $runner->setId($id);
        $this->current_runner = $runner;
        $this->runners[$id] = $runner;
        $this->milliseconds += $runner->getMilliseconds();

        return $id;
    }

    /**
     * @param int|string|null $id
     * @return int|string
     * @throws Exception("Runner's id: {$id} is already defined and locked")
     */
    private function getRunnerId(int|string|null $id): int|string
    {
        if ($id === null) {
            return ++self::$counter;
        } elseif (isset($this->runners[$id])) {
            throw new Exception("Runner's id: {$id} is already defined and locked");
        } else {
            return $id;
        }
    }

    /**
     * @param int $max_str_length
     * @throws Exception
     */
    public function createReport(int $max_str_length = 500): void
    {
        $this->report->create($max_str_length);
    }

    /**
     * @return string
     */
    public function getProjectTitle(): string
    {
        return $this->project_title;
    }

    /**
     * @param int|string|null $id If null then extract the latest run
     * @return Runner
     * @throws Exception
     */
    public function getRunner(int|string|null $id = null): Runner
    {
        return $this->runners[$this->getValidRunnerId($id)];
    }

    /**
     * @return array [Runner]
     */
    public function getAllRunners(): array
    {
        return $this->runners;
    }

    /**
     * Extract an existing test runner and set it as the current one
     *
     * @param int|string $id
     * @throws Exception("No test ran")
     * @throws Exception("Unknown runner's id: '{$id}'")
     */
    public function setCurrentRunnerTo(int|string $id): void
    {
        $this->current_runner = $this->runners[$this->getValidRunnerId($id)];
    }

    /**
     * @param int|string|null $id If null then apply to the last run
     * @return int|string
     * @throws Exception("No test ran")
     * @throws Exception("Unknown runner's id: '{$id}'")
     */
    private function getValidRunnerId(int|string|null $id = null): int|string
    {
        if (isset($id)) {
            if (isset($this->runners[$id])) {
                return $this->runners[$id]->getId();
            } else {
                throw new Exception("Unknown runner's id: '{$id}'");
            }
        } elseif (isset($this->current_runner)) {
            return $this->current_runner->getId();
        } else {
            throw new Exception("No test ran");
        }
    }

    /**
     * @param Closure $test
     * @param string|null $test_name
     * @param mixed $expected
     */
    public function assert(Closure $test, ?string $test_name = null, mixed $expected = null)
    {
        $runner_id = $this->getValidRunnerId();
        if ($test() === true) {
            $this->runners[$runner_id]->addAssertResult(
                result: true,
                test_name: $test_name
            );
            $this->stats['passed_assertions'] += 1;
        } else {
            $this->runners[$runner_id]->addAssertResult(
                result: false,
                test_name: $test_name,
                expected: $expected
            );
            $this->stats['failed_assertions'] += 1;
        }
    }

    /**
     * @param string|null $test_name
     * @throws Exception
     */
    public function addSuccess(string|null $test_name = null): void
    {
        $runner_id = $this->getValidRunnerId();
        $this->runners[$runner_id]->addAssertResult(result: true, test_name: $test_name);
        $this->stats['passed_assertions'] += 1;
    }

    /**
     * @param array|int|float|string $expected
     * @param string|null $test_name
     * @throws Exception
     */
    public function addFailure(array|int|float|string $expected, string|null $test_name = null): void
    {
        $runner_id = $this->getValidRunnerId();
        $this->runners[$runner_id]->addAssertResult(result: false, expected: $expected, test_name: $test_name);
        $this->stats['failed_assertions'] += 1;
    }

    /**
     * @return array
     */
    public function getStats(): array
    {
        // runs
        $nb_runs = count($this->runners);
        $passed_runs = 0;
        /** @var Runner $runner */
        foreach ($this->runners as $runner) {
            if ($runner->hasPassed()) {
                ++$passed_runs;
            }
        }
        $failed_runs = $nb_runs - $passed_runs;
        if ($nb_runs) {
            $passed_runs_percent = round($passed_runs / $nb_runs * 100, 2);
            $failed_runs_percent = (100 - $passed_runs_percent);
        } else {
            $passed_runs_percent = 0;
            $failed_runs_percent = 0;
        }

        // assertions
        ['passed_assertions' => $passed_assertions, 'failed_assertions' => $failed_assertions] = $this->stats;
        $nb_assertions = $passed_assertions + $failed_assertions;

        if ($nb_assertions) {
            $passed_assertions_percent = round($passed_assertions / $nb_assertions * 100, 2);
            $failed_assertions_percent = (100 - $passed_assertions_percent);
        } else {
            $passed_assertions_percent = 0;
            $failed_assertions_percent = 0;
        }

        // convert seconds to hms
        $converter =  function(int $seconds): string {
            $base_60 = fn ($p) => ($p >= 60) ? [$p % 60, intdiv($p, 60)] : [$p, 0];
            $sec = $base_60($seconds);
            $min = $base_60($sec[1]);
            $hours = $min[1];

            return sprintf("%02d:%02d:%02d", $hours, $min[0], $sec[0]);
        };

        return [
            'nb_runs' => $nb_runs,
            'passed_runs' => $passed_runs,
            'failed_runs' => $failed_runs,
            'passed_runs_percent' => $passed_runs_percent,
            'failed_runs_percent' => $failed_runs_percent,
            'nb_assertions' => $nb_assertions,
            'passed_assertions' => $passed_assertions,
            'failed_assertions' => $failed_assertions,
            'passed_assertions_percent' => $passed_assertions_percent,
            'failed_assertions_percent' => $failed_assertions_percent,
            'milliseconds' => $this->milliseconds,
            'hms' => $converter(intdiv($this->milliseconds, 1000)),
        ];
    }
}