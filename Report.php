<?php

declare(strict_types=1);

namespace Exacodis;

use DateTime;
use DateTimeZone;
use Exception;

use function htmlspecialchars;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_resource;
use function is_scalar;

use function is_string;
use function mb_strlen;
use function mb_substr;
use function print_r;

use function round;

use const ENT_QUOTES;

class Report
{
    /**
     * @var Pilot
     */
    protected Pilot $pilot;

    /**
     * @param Pilot $pilot
     * @throws Exception
     */
    public function __construct(Pilot $pilot)
    {
        $this->pilot = $pilot;
    }

    /**
     * @param array|int|float|string $p
     * @return array|string
     * @throws Exception
     */
    public function __invoke(array|int|float|string $p): array|string
    {
        $hsc = fn($p) => htmlspecialchars((string)$p, ENT_QUOTES);

        $hsc_array = function(array $part) use (&$hsc_array, $hsc): array {
            $data = [];
            foreach ($part as $k => $v) {
                $sk = $hsc($k);
                if (is_array($v)) {
                    $data[$sk] = $hsc_array($v);
                } elseif (is_scalar($v)) {
                    $data[$sk] = $hsc($v);
                } else {
                    throw new Exception("Unable to generate the report: scalar value expected");
                }
            }

            return $data;
        };

        if (is_array($p)) {
            return $hsc_array($p);
        } else {
            return $hsc($p);
        }
    }

    /**
     * @param int $max_str_length
     * @throws Exception
     */
    public function create(int $max_str_length = 500): void
    {
        $short_string = fn(string $p): string => mb_strlen($p) > $max_str_length ? mb_substr($p, 0, $max_str_length).'...' : $p;

        $date_time = new DateTime(timezone: new DateTimeZone('UTC'));
        $stats = $this->pilot->getStats();
        $global = $stats['failed_runs'] === 0
            ? '<span style="background-color: green; color: white;">&nbsp;PASSED&nbsp;</span>'
            : '<span style="background-color: red; color: white;">&nbsp;FAILED&nbsp;</span>';
        $html = [];
        $html[] = <<<html
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EXACODIS REPORT</title>
<style>
* {
  font-family: "Courier New";
}
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
  padding: 4px;
}
.failed {
  background-color: yellow;
}
</style>
</head>
<body>
  <h2>EXACODIS REPORT<br>{$date_time->format('Y-m-d H:i:s')} GMT/UTC/ZULU
      <br>---
      <br>Project: {$this($this->pilot->getProjectTitle())}
      <br>---
      <br>{$stats['nb_runs']} runs &rArr; {$stats['passed_runs']} passed + {$stats['failed_runs']} failed 
      &hArr; {$stats['passed_runs_percent']} % passed + {$stats['failed_runs_percent']} % failed 
      <br>{$stats['nb_assertions']} assertions &rArr; {$stats['passed_assertions']} passed + {$stats['failed_assertions']} failed 
      &hArr; {$stats['passed_assertions_percent']} % passed + {$stats['failed_assertions_percent']} % failed
      <br>---
      <br>Global test code evaluation: <strong>{$global}</strong><br>in {$stats['milliseconds']} ms &hArr; {$stats['hms']}
  </h2>
  <table>
    <tbody>
html;

        $header = function(Runner $runner): string {
            $nb_assertions = $runner->getNbAssertions();
            $plural = $nb_assertions >= 2 ? 's' : '';

            if ($runner->hasPassed()) {
                return <<<html
<tr><td colspan="4">Run: {$this($runner->getId())} - PASSED - {$runner->getMilliseconds()} ms - 
{$nb_assertions} assertion{$plural}<br>{$this($runner->getDescription())}</td></tr>
html;
            } else {
                if ($nb_assertions) {
                    $nb_passed = $runner->getNbAssertionsPassed();
                    $nb_failed = $nb_assertions - $nb_passed;
                    $nb_passed_percent = round($nb_passed / $nb_assertions * 100, 2);
                    $nb_failed_percent = 100 - $nb_passed_percent;
                } else {
                    $nb_passed = 0;
                    $nb_failed = 0;
                    $nb_passed_percent = 0;
                    $nb_failed_percent = 0;
                }

                return <<<html
<tr class="failed"><td colspan="4">Run: {$this($runner->getId())} - FAILED - {$runner->getMilliseconds()} ms - 
{$nb_assertions} assertion{$plural} &rAarr; {$nb_passed} passed + {$nb_failed} failed &hArr; {$nb_passed_percent} % passed + {$nb_failed_percent} % failed  
<br>{$this($runner->getDescription())}</td></tr>
html;
            }
        };

        foreach ($this->pilot->getAllRunners() as $runner) {
            /** @var Runner $runner */
            if ($runner->hasPassed()) {
                // if all asserts in a run are ok, we only echo the global header and not the detail assert by assert
                $html[] = $header($runner);
            } else {
                // otherwise, we echo the result assert by assert
                $html[] = $header($runner);
                foreach ($runner->getAssertResults() as $v) {
                    ['result' => $result, 'expected' => $expected, 'test_name' => $test_name] = $v;
                    if ($result) {
                        $html[] = <<<html
<tr><td>&nbsp;</td><td colspan="3">PASSED: {$this((string)$test_name)}</td></tr>
html;
                    } else {
                        $html[] = <<<html
<tr>
  <td rowspan="2">&nbsp;</td>
  <td class="failed" rowspan="2">FAILED: {$this((string)$test_name)}</td>
  <td class="failed">Expected</td>
  <td class="failed">{$this($short_string(print_r($expected, true)))}</td>
</tr>
<tr>
  <td class="failed">Given</td>
  <td class="failed">{$this->valueAnalyser($runner->getResult())}</td>
</tr>
html;
                    }
                }
            }
        }
        $html[] = <<<html
    </tbody>
  </table>
</body>
</html>
html;
        echo implode('', $html);
    }

    /**
     * @param mixed $v
     * @param int $max_str_length
     * @return string
     * @throws Exception
     */
    protected function valueAnalyser(mixed $v, int $max_str_length = 500): string
    {
        $short_string = fn(string $p): string => mb_strlen($p) >= $max_str_length ? mb_substr($p, 0, $max_str_length).'...' : $p;

        if ($v === null) {
            return 'null';
        } elseif (is_string($v)) {
            return $short_string('string: '.$this($v));
        } elseif (is_array($v)) {
            return $short_string('array<br>'.$this(print_r($v, true)));
        } elseif (is_int($v)) {
            return 'integer: '.$v;
        } elseif (is_float($v)) {
            return 'float: '.$v;
        } elseif (is_bool($v)) {
            return 'boolean: '.$v ? 'true' : 'false';
        } elseif (is_object($v)) {
            if ($v instanceof Exception) {
                return 'Exception: '.$v::class
                    .'<br>Code: '.$v->getCode()
                    .'<br>Message: '.$v->getMessage()
                    .'<br>File: '.$v->getFile()
                    .'<br>Line: '.$v->getLine()
                    .'<br>Trace: '.$v->getTraceAsString();
            } else {
                return 'Object: '.$v::class;
            }
        } elseif (is_resource($v)) {
            return $short_string('resource: '.$this(print_r($v, true)));
        } else {
            return 'UNKNOWN TYPE';
        }
    }
}