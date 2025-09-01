<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class AdvisorConfigService
{
    protected array $rawConfig = [];

    public function __construct(protected ?string $path = null)
    {
        $this->path = $this->path ?? base_path('docs/instructions-to-rebuild/starter-files/config/advisors.json');
        $this->loadRawConfig();
    }

    protected function loadRawConfig(): void
    {
        if (!file_exists($this->path)) {
            Log::error("AdvisorConfigService: config file not found", ['path' => $this->path]);
            throw new InvalidArgumentException("Advisors config file not found at {$this->path}");
        }

        $contents = file_get_contents($this->path);
        if ($contents === false) {
            Log::error("AdvisorConfigService: unable to read config file", ['path' => $this->path]);
            throw new InvalidArgumentException("Unable to read advisors config file at {$this->path}");
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            Log::error("AdvisorConfigService: invalid JSON in config file", ['path' => $this->path]);
            throw new InvalidArgumentException("Invalid JSON in advisors config file at {$this->path}");
        }

        $this->rawConfig = $decoded;
    }

    public function allAdvisors(): array
    {
        return $this->rawConfig['advisors'] ?? [];
    }

    public function getAdvisorConfig(string $key): array
    {
        $advisors = $this->allAdvisors();

        if (!isset($advisors[$key]) || !is_array($advisors[$key])) {
            throw new InvalidArgumentException("Advisor config not found for key: {$key}");
        }

        return $advisors[$key];
    }

    public function mapVariables(array $config): array
    {
        return [
            'advisor_name' => Arr::get($config, 'fullName', ''),
            'name' => Arr::get($config, 'name', ''),
            'voice_dna' => Arr::get($config, 'communication_style_description', ''),
            'operating_principles' => Arr::get($config, 'decision_making_approach', ''),
            'communication_style' => Arr::get($config, 'communication_style_description', ''),
            'core_expertise' => Arr::get($config, 'core_expertise_area', ''),
            'key_phrases' => Arr::get($config, 'key_phrases_or_terminology', ''),
            'emotional_characteristics' => Arr::get($config, 'emotional_characteristics', ''),
            'unique_perspectives' => Arr::get($config, 'unique_perspectives_or_contrarian_stances', ''),
            'generated_date' => now()->format('Y-m-d'),
            'generation_id' => uniqid('gen_'),
            'date' => now()->format('Y-m-d'),
        ];
    }
}