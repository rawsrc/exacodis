<?php

declare(strict_types=1);

namespace Exacodis;

use Closure;
use Exception;

use function count;
use function hrtime;

/**
 * Project    Exacodis Test PHP Framework
 * @author    Martin LACROIX - rawsrc@gmail.com
 * @license   MIT
 * @copyright 2021+
 */
class Runner
{
    /**
     * @var int|string|null
     */
    private int|string|null $id = null;
    /**
     * @var string
     */
    private string $description = '';
    /**
     * @var mixed|Exception
     */
    private mixed $result = null;
    /**
     * @var int
     */
    private int $milliseconds;
    /**
     * @var array
     */
    private array $assert_results = [];

    /**
     * @param Closure $closure
     * @param string $description
     */
    public function __construct(Closure $closure, string $description = '')
    {
        $this->description = $description;
        $start = $end = 0;
        try {
            $start = hrtime(true);
            $this->result = $closure();
            $end = hrtime(true);
        } catch (Exception $e) {
            $this->result = $e;
            $end = hrtime(true);
        }
        $this->milliseconds = (int)(($end - $start) / 1e+6); // convert nanoseconds to milliseconds
    }

    /**
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * @return int
     */
    public function getMilliseconds(): int
    {
        return $this->milliseconds;
    }

    /**
     * Once defined not updatable
     *
     * @param int|string $id
     * @throws Exception
     */
    public function setId(int|string $id)
    {
        if (isset($this->id)) {
            throw new Exception("Test id is already defined and locked, not updatable with '{$id}'");
        } else {
            $this->id = $id;
        }
    }

    /**
     * @return int|string|null
     */
    public function getId(): int|string|null
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param bool $result
     * @param array|int|float|string|null $expected
     * @param string|null $test_name
     */
    public function addAssertResult(bool $result, array|int|float|string|null $expected = null, string|null $test_name = null): void
    {
        $this->assert_results[] = ['result' => $result, 'expected' => $expected, 'test_name' => $test_name];
    }

    /**
     * @return array [[result => bool, expected => string|null, test_name => string|null]]
     */
    public function getAssertResults(): array
    {
        return $this->assert_results;
    }

    /**
     * @return int
     */
    public function getNbAssertions(): int
    {
        return count($this->assert_results);
    }

    /**
     * @return int
     */
    public function getNbAssertionsPassed(): int
    {
        $nb = 0;
        foreach ($this->assert_results as $v) {
            if ($v['result']) {
                ++$nb;
            }
        }

        return $nb;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        if (empty($this->assert_results)) {
            return false;
        } else {
            foreach ($this->assert_results as $v) {
                if ($v['result'] === false) {
                    return false;
                }
            }

            return true;
        }
    }

    /**
     * @return bool
     */
    public function hasPassed(): bool
    {
        return $this->getStatus() === true;
    }

    /**
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->getStatus() === false;
    }
}