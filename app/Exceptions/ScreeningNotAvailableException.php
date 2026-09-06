<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a category/material has no real screening content behind it
 * yet. Distinct from ComplianceEngineException (a transient failure worth
 * retrying) — this is a permanent "we don't have this yet", so the
 * controller shows a straightforward "not available" message instead of
 * "try again in a moment".
 */
class ScreeningNotAvailableException extends RuntimeException {}
