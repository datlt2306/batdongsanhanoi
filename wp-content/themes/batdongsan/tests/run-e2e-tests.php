<?php

/**
 * BatDongSan Theme E2E Test Runner.
 * 
 * Usage:
 *   php tests/run-e2e-tests.php [options]
 * 
 * Options:
 *   --tier=1|2|3|4         Run tests for a specific tier only
 *   --feature=R1|R2|R3|R4  Run tests for a specific requirement/feature only
 *   --help                 Display help message
 */

require_once __DIR__ . '/bootstrap.php';

// Include Tier 1 Tests
require_once __DIR__ . '/e2e/tier1-feature-coverage/TestR1CptMigration.php';
require_once __DIR__ . '/e2e/tier1-feature-coverage/TestR2PerformanceSecurity.php';
require_once __DIR__ . '/e2e/tier1-feature-coverage/TestR3AjaxSearchFilter.php';
require_once __DIR__ . '/e2e/tier1-feature-coverage/TestR4LeadsProcessing.php';

// Include Tier 2 Tests
require_once __DIR__ . '/e2e/tier2-boundary-corner/TestR1BoundaryCases.php';
require_once __DIR__ . '/e2e/tier2-boundary-corner/TestR2BoundaryCases.php';
require_once __DIR__ . '/e2e/tier2-boundary-corner/TestR3BoundaryCases.php';
require_once __DIR__ . '/e2e/tier2-boundary-corner/TestR4BoundaryCases.php';

// Include Tier 3 Tests
require_once __DIR__ . '/e2e/tier3-combinations/TestCrossFeatureCombinations.php';

// Include Tier 4 Tests
require_once __DIR__ . '/e2e/tier4-scenarios/TestRealWorldScenarios.php';

use BDS\Tests\Framework\TestSuite;
use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\E2E\Tier1\TestR1CptMigration;
use BDS\Tests\E2E\Tier1\TestR2PerformanceSecurity;
use BDS\Tests\E2E\Tier1\TestR3AjaxSearchFilter;
use BDS\Tests\E2E\Tier1\TestR4LeadsProcessing;
use BDS\Tests\E2E\Tier2\TestR1BoundaryCases;
use BDS\Tests\E2E\Tier2\TestR2BoundaryCases;
use BDS\Tests\E2E\Tier2\TestR3BoundaryCases;
use BDS\Tests\E2E\Tier2\TestR4BoundaryCases;
use BDS\Tests\E2E\Tier3\TestCrossFeatureCombinations;
use BDS\Tests\E2E\Tier4\TestRealWorldScenarios;

// Parse CLI Options
$options = [];
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--tier=') === 0) {
        $options['tier'] = substr($arg, 7);
    } elseif (strpos($arg, '--feature=') === 0) {
        $options['feature'] = substr($arg, 10);
    } elseif ($arg === '--help') {
        echo "BatDongSan E2E Test Suite Runner\n";
        echo "Usage: php tests/run-e2e-tests.php [--tier=1|2|3|4] [--feature=R1|R2|R3|R4]\n";
        exit(0);
    }
}

$suite = new TestSuite();

// Helper to register tests from test class
$registerClassTests = function (string $tier, string $feature, string $className) use ($suite) {
    $instance = new $className();
    $methods = get_class_methods($instance);
    foreach ($methods as $method) {
        if (strpos($method, 'test_') === 0) {
            $readableName = ucwords(str_replace('_', ' ', substr($method, 5)));
            // Extract or infer test ID
            $id = 'TEST';
            if (preg_match('/test_([a-z0-9_]+)/', $method, $m)) {
                $id = strtoupper(str_replace('_', '-', $m[1]));
            }

            $suite->addTest($tier, $feature, $id, $readableName, function () use ($instance, $method) {
                $instance->setUp();
                try {
                    $instance->$method();
                } finally {
                    $instance->tearDown();
                }
            });
        }
    }
};

// Tier 1: Feature Coverage
$registerClassTests('Tier 1: Feature Coverage', 'R1', TestR1CptMigration::class);
$registerClassTests('Tier 1: Feature Coverage', 'R2', TestR2PerformanceSecurity::class);
$registerClassTests('Tier 1: Feature Coverage', 'R3', TestR3AjaxSearchFilter::class);
$registerClassTests('Tier 1: Feature Coverage', 'R4', TestR4LeadsProcessing::class);

// Tier 2: Boundary & Corner Cases
$registerClassTests('Tier 2: Boundary & Corner Cases', 'R1', TestR1BoundaryCases::class);
$registerClassTests('Tier 2: Boundary & Corner Cases', 'R2', TestR2BoundaryCases::class);
$registerClassTests('Tier 2: Boundary & Corner Cases', 'R3', TestR3BoundaryCases::class);
$registerClassTests('Tier 2: Boundary & Corner Cases', 'R4', TestR4BoundaryCases::class);

// Tier 3: Cross-Feature Combinations
$registerClassTests('Tier 3: Cross-Feature Combos', 'COMBO', TestCrossFeatureCombinations::class);

// Tier 4: Real-World Scenarios
$registerClassTests('Tier 4: Real-World Scenarios', 'SCENARIO', TestRealWorldScenarios::class);

// Execute Suite
$exitCode = $suite->run($options);
exit($exitCode);
