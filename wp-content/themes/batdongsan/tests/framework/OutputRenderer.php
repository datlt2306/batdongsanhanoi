<?php

/**
 * Console Output Renderer for E2E Test Suite.
 */

namespace BDS\Tests\Framework;

class OutputRenderer {
    protected bool $colorsSupported;

    public function __construct() {
        $this->colorsSupported = (DIRECTORY_SEPARATOR !== '\\' || getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON');
    }

    public function color(string $text, string $color): string {
        if (!$this->colorsSupported) {
            return $text;
        }
        $codes = [
            'green' => "\033[32m",
            'red' => "\033[31m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'cyan' => "\033[36m",
            'bold' => "\033[1m",
            'reset' => "\033[0m",
            'gray' => "\033[90m",
            'bg_red' => "\033[41;37m",
            'bg_green' => "\033[42;30m",
        ];
        return ($codes[$color] ?? '') . $text . ($codes['reset'] ?? '');
    }

    public function printHeader(string $title): void {
        echo PHP_EOL;
        echo $this->color(str_repeat('=', 80), 'cyan') . PHP_EOL;
        echo $this->color('  ' . strtoupper($title), 'bold') . PHP_EOL;
        echo $this->color(str_repeat('=', 80), 'cyan') . PHP_EOL;
    }

    public function printTierHeader(string $tierName, string $tierDescription): void {
        echo PHP_EOL;
        echo $this->color('--------------------------------------------------------------------------------', 'gray') . PHP_EOL;
        echo $this->color("▶ {$tierName}: ", 'bold') . $this->color($tierDescription, 'yellow') . PHP_EOL;
        echo $this->color('--------------------------------------------------------------------------------', 'gray') . PHP_EOL;
    }

    public function printTestResult(string $id, string $name, bool $passed, ?string $failureReason = null, float $duration = 0.0): void {
        $status = $passed
            ? $this->color('  ✔ PASS', 'green')
            : $this->color('  ✖ FAIL', 'red');

        $time = $this->color(sprintf('[%.2fms]', $duration * 1000), 'gray');
        $idFormatted = $this->color(str_pad($id, 14), 'bold');

        echo sprintf("%s %s %s %s\n", $status, $idFormatted, $name, $time);

        if (!$passed && $failureReason) {
            $indented = '       ' . str_replace("\n", "\n       ", trim($failureReason));
            echo $this->color($indented, 'red') . PHP_EOL;
        }
    }

    public function printSummary(int $total, int $passed, int $failed, float $duration, array $tierStats): void {
        echo PHP_EOL;
        echo $this->color(str_repeat('=', 80), 'cyan') . PHP_EOL;
        echo $this->color("                   E2E TEST SUITE EXECUTION SUMMARY                             ", 'bold') . PHP_EOL;
        echo $this->color(str_repeat('=', 80), 'cyan') . PHP_EOL;

        echo sprintf(" %-35s | %-8s | %-8s | %-8s | %-8s\n", "Test Tier", "Total", "Pass", "Fail", "Pass %");
        echo str_repeat('-', 80) . PHP_EOL;

        foreach ($tierStats as $tier => $stats) {
            $t = $stats['total'];
            $p = $stats['passed'];
            $f = $stats['failed'];
            $pct = $t > 0 ? round(($p / $t) * 100, 1) : 0;
            $color = $f === 0 ? 'green' : 'yellow';
            echo sprintf(" %-35s | %-8d | %-8d | %-8d | %s\n",
                $tier, $t, $p, $f,
                $this->color(sprintf("%6.1f%%", $pct), $color)
            );
        }

        echo str_repeat('=', 80) . PHP_EOL;
        $totalPct = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        $totalColor = $failed === 0 ? 'green' : 'red';

        echo sprintf(" %-35s | %-8d | %-8d | %-8d | %s\n",
            "OVERALL TOTAL", $total, $passed, $failed,
            $this->color(sprintf("%6.1f%%", $totalPct), $totalColor)
        );
        echo str_repeat('-', 80) . PHP_EOL;
        echo sprintf(" Execution time: %.3f seconds\n", $duration);

        if ($failed === 0) {
            echo PHP_EOL . $this->color("  [PASS] ALL E2E TEST CRITERIA SATISFIED SUCCESSFULLY!  ", 'bg_green') . PHP_EOL . PHP_EOL;
        } else {
            echo PHP_EOL . $this->color("  [FAIL] $failed TEST(S) FAILED. VERIFY IMPLEMENTATION GAPS.  ", 'bg_red') . PHP_EOL . PHP_EOL;
        }
    }
}
