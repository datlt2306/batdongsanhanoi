<?php

/**
 * Test Suite Runner and Aggregator.
 */

namespace BDS\Tests\Framework;

class TestSuite {
    protected array $tests = [];
    protected OutputRenderer $renderer;

    public function __construct() {
        $this->renderer = new OutputRenderer();
    }

    public function addTest(string $tier, string $feature, string $id, string $name, callable $callable): void {
        $this->tests[] = [
            'tier' => $tier,
            'feature' => $feature,
            'id' => $id,
            'name' => $name,
            'callable' => $callable,
        ];
    }

    public function run(array $options = []): int {
        $tierFilter = $options['tier'] ?? null;
        $featureFilter = $options['feature'] ?? null;

        $this->renderer->printHeader('BatDongSan Theme E2E Test Suite (Tiers 1-4)');

        $startTime = microtime(true);
        $total = 0;
        $passed = 0;
        $failed = 0;

        $tierStats = [
            'Tier 1: Feature Coverage' => ['total' => 0, 'passed' => 0, 'failed' => 0],
            'Tier 2: Boundary & Corner Cases' => ['total' => 0, 'passed' => 0, 'failed' => 0],
            'Tier 3: Cross-Feature Combos' => ['total' => 0, 'passed' => 0, 'failed' => 0],
            'Tier 4: Real-World Scenarios' => ['total' => 0, 'passed' => 0, 'failed' => 0],
        ];

        $currentTier = null;

        foreach ($this->tests as $test) {
            if ($tierFilter && stripos($test['tier'], $tierFilter) === false && strpos($test['tier'], (string) $tierFilter) === false) {
                continue;
            }
            if ($featureFilter && strcasecmp($test['feature'], $featureFilter) !== 0) {
                continue;
            }

            if ($currentTier !== $test['tier']) {
                $currentTier = $test['tier'];
                $this->renderer->printTierHeader($currentTier, $this->getTierDescription($currentTier));
            }

            $total++;
            $tierKey = $this->matchTierKey($test['tier'], $tierStats);
            $tierStats[$tierKey]['total']++;

            $testStart = microtime(true);
            $testPassed = false;
            $failureReason = null;

            try {
                call_user_func($test['callable']);
                $testPassed = true;
                $passed++;
                $tierStats[$tierKey]['passed']++;
            } catch (\AssertionError $e) {
                $failed++;
                $tierStats[$tierKey]['failed']++;
                $failureReason = $e->getMessage() . " (" . basename($e->getFile()) . ":" . $e->getLine() . ")";
            } catch (\Throwable $t) {
                $failed++;
                $tierStats[$tierKey]['failed']++;
                $failureReason = "Exception [" . get_class($t) . "]: " . $t->getMessage() . " in " . basename($t->getFile()) . ":" . $t->getLine();
            }

            $testDuration = microtime(true) - $testStart;
            $this->renderer->printTestResult($test['id'], $test['name'], $testPassed, $failureReason, $testDuration);
        }

        $totalDuration = microtime(true) - $startTime;
        $this->renderer->printSummary($total, $passed, $failed, $totalDuration, $tierStats);

        return $failed === 0 ? 0 : 1;
    }

    protected function matchTierKey(string $tierName, array $tierStats): string {
        foreach (array_keys($tierStats) as $k) {
            if (stripos($tierName, explode(':', $k)[0]) !== false) {
                return $k;
            }
        }
        return array_key_first($tierStats);
    }

    protected function getTierDescription(string $tierName): string {
        return match (true) {
            str_contains($tierName, 'Tier 1') => 'Feature contracts & acceptance criteria (>=5 tests per feature)',
            str_contains($tierName, 'Tier 2') => 'Edge cases, malformed payloads, boundaries & limits',
            str_contains($tierName, 'Tier 3') => 'Pairwise cross-feature interactions (R1+R2, R1+R3, R1+R4, R2+R3, R2+R4, R3+R4)',
            str_contains($tierName, 'Tier 4') => 'End-to-end real-world user and admin workflows',
            default => 'General Verification Suite',
        };
    }
}
