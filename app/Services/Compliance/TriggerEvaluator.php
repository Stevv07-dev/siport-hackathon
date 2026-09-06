<?php

namespace App\Services\Compliance;

/**
 * Evaluates the `trigger`/`condition` strings authored in backend-ai's
 * dataset (usb-c-cable-dataset/02_clarification_questions.json,
 * 03_classification_rules.json) against the answers collected so far.
 *
 * Only a small, literal grammar is understood — clauses of the exact shape
 * `attribute == value`, joined by " AND ", where value is true/false, a
 * number, or a bare/quoted string:
 *
 *   "connector_present == true"
 *   "voltage_rating_v <= 1000"
 *   "connector_present == true AND voltage_rating_v <= 1000"
 *
 * Many triggers in the dataset are narrative instead ("classification
 * remains ambiguous", "shipment setup") — those aren't a claim this class
 * invents logic for. isParseable() reports whether a string matched the
 * grammar at all; callers must decide what "narrative, not parseable"
 * means for them rather than have this class silently guess.
 */
class TriggerEvaluator
{
    private const OPERATORS = ['==', '!=', '<=', '>=', '<', '>'];

    /**
     * @param  array<string, mixed>  $answers
     */
    public function evaluate(string $trigger, array $answers): bool
    {
        $clauses = preg_split('/\s+AND\s+/', trim($trigger));

        foreach ($clauses as $clause) {
            $parsed = $this->parseClause($clause);

            if ($parsed === null) {
                // A narrative clause inside an otherwise-parseable AND chain
                // can't be silently treated as satisfied — that would let a
                // condition through that was never actually checked.
                return false;
            }

            [$attribute, $operator, $expected] = $parsed;

            if (! array_key_exists($attribute, $answers)) {
                return false;
            }

            if (! $this->compare($answers[$attribute], $operator, $expected)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether every clause in $trigger matches the `attribute op value`
     * grammar. False means this is a narrative/descriptive trigger
     * ("shipment setup") that this class has no basis to evaluate.
     */
    public function isParseable(string $trigger): bool
    {
        $clauses = preg_split('/\s+AND\s+/', trim($trigger));

        foreach ($clauses as $clause) {
            if ($this->parseClause($clause) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{0: string, 1: string, 2: mixed}|null
     */
    private function parseClause(string $clause): ?array
    {
        $clause = trim($clause);

        foreach (self::OPERATORS as $operator) {
            $pos = strpos($clause, $operator);

            if ($pos === false) {
                continue;
            }

            // Skip "<=" being matched by the standalone "<" check, etc. —
            // operators are checked longest-first via array order above,
            // so the first hit is already the correct one.
            $attribute = trim(substr($clause, 0, $pos));
            $rawValue = trim(substr($clause, $pos + strlen($operator)));

            if ($attribute === '' || $rawValue === '') {
                return null;
            }

            return [$attribute, $operator, $this->parseValue($rawValue)];
        }

        return null;
    }

    private function parseValue(string $raw): mixed
    {
        $raw = trim($raw, "\"' \t");

        return match (true) {
            strcasecmp($raw, 'true') === 0 => true,
            strcasecmp($raw, 'false') === 0 => false,
            is_numeric($raw) => $raw + 0,
            default => $raw,
        };
    }

    private function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        if (is_bool($expected)) {
            $actual = filter_var($actual, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $actual;
        } elseif (is_numeric($expected) && is_numeric($actual)) {
            $actual = $actual + 0;
        } elseif (is_string($expected) && is_string($actual)) {
            $actual = strtolower($actual);
            $expected = strtolower($expected);
        }

        return match ($operator) {
            '==' => $actual == $expected,
            '!=' => $actual != $expected,
            '<=' => $actual <= $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '>' => $actual > $expected,
            default => false,
        };
    }
}
