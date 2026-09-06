<?php

namespace App\Services\Compliance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Reads the dataset the AI Engineer's team authored at
 * backend-ai/usb-c-cable-dataset/ — the real, current source of what
 * MAXPORT can honestly screen. Nothing here is duplicated into MAXPORT's
 * own database: if that dataset changes, this reflects it on the next
 * request (cache aside), with no reseed step to remember.
 *
 * Deliberately scoped to exactly what backend-ai's prototype covers today
 * (USB-C cable, per app/ai.py's SYSTEM_PROMPT) — see
 * ProductScreeningController for how that maps to what's selectable in
 * the UI.
 */
class BackendAiDataset
{
    private const BASE_PATH = 'backend-ai/usb-c-cable-dataset';

    private const TTL_SECONDS = 300;

    /** @return array<int, array<string, mixed>> */
    public function clarificationQuestions(): array
    {
        return $this->readJson('02_clarification_questions.json');
    }

    /** @return array<int, array<string, mixed>> */
    public function classificationRules(): array
    {
        return $this->readJson('03_classification_rules.json');
    }

    /** @return array<int, array<string, mixed>> */
    public function lartasRules(): array
    {
        return $this->readJson('04_indonesia_lartas_rules.json');
    }

    /** @return array<int, array<string, mixed>> */
    public function singaporeImportRules(): array
    {
        return $this->readJson('05_singapore_import_rules.json');
    }

    /** @return array<int, array<string, mixed>> */
    public function sources(): array
    {
        return $this->readJson('07_sources.json');
    }

    public function source(string $id): ?array
    {
        return collect($this->sources())->firstWhere('id', $id);
    }

    /** @return array<int, array<string, mixed>> */
    private function readJson(string $filename): array
    {
        $path = base_path(self::BASE_PATH.'/'.$filename);

        return Cache::remember("backend-ai-dataset:{$filename}", self::TTL_SECONDS, function () use ($path, $filename) {
            if (! File::exists($path)) {
                throw new \RuntimeException("backend-ai dataset file missing: {$filename}. Expected at {$path}.");
            }

            $decoded = json_decode(File::get($path), associative: true);

            if (! is_array($decoded)) {
                throw new \RuntimeException("backend-ai dataset file is not valid JSON: {$filename}.");
            }

            return $decoded;
        });
    }
}
