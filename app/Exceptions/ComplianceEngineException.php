<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a ComplianceEngine implementation can't produce a questionnaire
 * or a verdict — a failed/malformed AI response, a timeout, missing
 * credentials, etc. Controllers catch this and show a friendly retry message
 * instead of a 500.
 */
class ComplianceEngineException extends RuntimeException {}
