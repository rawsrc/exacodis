<?php

declare(strict_types=1);

namespace Exacodis;

use Closure;
use Exception;

use function array_key_last;
use function count;
use function is_file;

use function round;

use const DIRECTORY_SEPARATOR;

/**
 * @method assertEqual(mixed $to)
 * @method assertIn(array $values)
 * @method assertInStrict(array $values)
 * @method assertIsArray()
 * @method assertIsBool()
 * @method assertIsFloat()
 * @method assertIsInt()
 * @method assertIsObject()
 * @method assertIsResource()
 * @method assertIsScalar()
 * @method assertIsString()
 * @method assertInstanceOf(object|string $class)
 * @method assertException(object|string $class = 'Exception')
 * @method assertNotEqual(mixed $to)
 * @method assertNotIn(array $values)
 * @method assertNotInStrict(array $values)
 */
class Pilot
{
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
     * @throws Exception
     */
    public function run(int|string|null $id, Closure $test, string $description = ''): int|string
    {
        static $i = -1;

        if (isset($id, $this->runners[$id])) {
            throw new Exception("Runner's id: {$id} is already defined and locked");
        }
        $id ??= ++$i;
        $runner = new Runner($test, $description);
        $runner->setId($id);
        $this->current_runner = $runner;
        $this->runners[$id] = $runner;
        $this->milliseconds += $runner->getMilliseconds();

        return $id;
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
     * @param int|string|null $id If null then apply to the latest run
     * @return Runner
     * @throws Exception
     */
    public function getRunner(int|string|null $id): Runner
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
     * @throws Exception
     */
    public function setCurrentRunnerTo(int|string $id): void
    {
        $this->current_runner = $this->runners[$this->getValidRunnerId($id)];
    }

    /**
     * @param int|string|null $id If null then apply to the last run
     * @return int|string
     * @throws Exception
     */
    private function getValidRunnerId(int|string|null $id = null): int|string
    {
        if (isset($id)) {
            if (isset($this->runners[$id])) {
                return $this->runners[$id];
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
     * @param array|string $expected
     * @throws Exception
     */
    public function addFailure(array|string $expected): void
    {
        $runner_id = $this->getValidRunnerId();
        $this->runners[$runner_id]->addAssertResult(result: false, expected: $expected);
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