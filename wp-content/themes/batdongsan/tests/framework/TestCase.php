<?php

/**
 * Base TestCase for BatDongSan E2E Test Suite.
 */

namespace BDS\Tests\Framework;

abstract class TestCase {
    /**
     * @var string
     */
    protected string $name = '';

    /**
     * @var array
     */
    protected array $assertions = [];

    /**
     * Root directory of the theme.
     * @var string
     */
    protected string $themeDir = '';

    public function __construct() {
        $this->themeDir = dirname(__DIR__, 2);
    }

    /**
     * Safely require a theme file or fail assertion with a clear message.
     */
    protected function requireThemeFile(string $relativePath): void {
        $fullPath = $this->themeDir . '/' . ltrim($relativePath, '/');
        if (!file_exists($fullPath)) {
            $this->fail("Required theme file '$relativePath' does not exist.");
        }
        require_once $fullPath;
    }

    /**
     * Run before each test method.
     */
    public function setUp(): void {
        MockWordPress::reset();
    }

    /**
     * Run after each test method.
     */
    public function tearDown(): void {
        MockWordPress::reset();
    }

    /**
     * Assert that a condition is true.
     */
    public function assertTrue(bool $condition, string $message = 'Failed asserting that condition is true.'): void {
        if (!$condition) {
            throw new \AssertionError($message);
        }
    }

    /**
     * Assert that a condition is false.
     */
    public function assertFalse(bool $condition, string $message = 'Failed asserting that condition is false.'): void {
        if ($condition) {
            throw new \AssertionError($message);
        }
    }

    /**
     * Assert that two values are equal.
     */
    public function assertEquals(mixed $expected, mixed $actual, string $message = ''): void {
        if ($expected !== $actual) {
            $msg = $message ?: sprintf('Failed asserting that %s matches expected %s.', json_encode($actual), json_encode($expected));
            throw new \AssertionError($msg);
        }
    }

    /**
     * Assert that two values are not equal.
     */
    public function assertNotEquals(mixed $expected, mixed $actual, string $message = ''): void {
        if ($expected === $actual) {
            $msg = $message ?: sprintf('Failed asserting that %s is not equal to expected %s.', json_encode($actual), json_encode($expected));
            throw new \AssertionError($msg);
        }
    }

    /**
     * Assert that a haystack string or array contains a needle.
     */
    public function assertContains(mixed $needle, mixed $haystack, string $message = ''): void {
        if (is_string($haystack)) {
            if (strpos($haystack, (string) $needle) === false) {
                $msg = $message ?: sprintf('Failed asserting that string contains "%s".', $needle);
                throw new \AssertionError($msg);
            }
        } elseif (is_array($haystack)) {
            if (!in_array($needle, $haystack, true)) {
                $msg = $message ?: sprintf('Failed asserting that array contains item %s.', json_encode($needle));
                throw new \AssertionError($msg);
            }
        } else {
            throw new \InvalidArgumentException('Haystack must be string or array.');
        }
    }

    /**
     * Assert that a haystack does not contain a needle.
     */
    public function assertNotContains(mixed $needle, mixed $haystack, string $message = ''): void {
        if (is_string($haystack)) {
            if (strpos($haystack, (string) $needle) !== false) {
                $msg = $message ?: sprintf('Failed asserting that string does NOT contain "%s".', $needle);
                throw new \AssertionError($msg);
            }
        } elseif (is_array($haystack)) {
            if (in_array($needle, $haystack, true)) {
                $msg = $message ?: sprintf('Failed asserting that array does NOT contain item %s.', json_encode($needle));
                throw new \AssertionError($msg);
            }
        } else {
            throw new \InvalidArgumentException('Haystack must be string or array.');
        }
    }

    /**
     * Assert that a string matches a regular expression.
     */
    public function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void {
        if (!preg_match($pattern, $string)) {
            $msg = $message ?: sprintf('Failed asserting that "%s" matches pattern "%s".', $string, $pattern);
            throw new \AssertionError($msg);
        }
    }

    /**
     * Assert that a string does NOT match a regular expression.
     */
    public function assertDoesNotMatchRegularExpression(string $pattern, string $string, string $message = ''): void {
        if (preg_match($pattern, $string)) {
            $msg = $message ?: sprintf('Failed asserting that "%s" does NOT match pattern "%s".', $string, $pattern);
            throw new \AssertionError($msg);
        }
    }

    /**
     * Assert that an array has a specified key.
     */
    public function assertArrayHasKey(string|int $key, array $array, string $message = ''): void {
        if (!array_key_exists($key, $array)) {
            $msg = $message ?: sprintf('Failed asserting that array has key "%s".', $key);
            throw new \AssertionError($msg);
        }
    }

    /**
     * Assert count of elements.
     */
    public function assertCount(int $expectedCount, countable|array $haystack, string $message = ''): void {
        $actualCount = count($haystack);
        if ($expectedCount !== $actualCount) {
            $msg = $message ?: sprintf('Failed asserting that count is %d (actual: %d).', $expectedCount, $actualCount);
            throw new \AssertionError($msg);
        }
    }

    /**
     * Assert null.
     */
    public function assertNull(mixed $value, string $message = ''): void {
        if ($value !== null) {
            $msg = $message ?: sprintf('Failed asserting that %s is null.', json_encode($value));
            throw new \AssertionError($msg);
        }
    }

    /**
     * Assert not null.
     */
    public function assertNotNull(mixed $value, string $message = ''): void {
        if ($value === null) {
            $msg = $message ?: 'Failed asserting that value is not null.';
            throw new \AssertionError($msg);
        }
    }

    /**
     * Force fail a test.
     */
    public function fail(string $message = 'Test explicitly failed.'): void {
        throw new \AssertionError($message);
    }
}
