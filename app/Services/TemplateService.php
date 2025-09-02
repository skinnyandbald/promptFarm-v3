<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class TemplateService
{
    protected string $templatePath;

    public function __construct()
    {
        $this->templatePath = resource_path('advisor-templates');
    }

    /**
     * Load a template by name and optional version
     */
    public function loadTemplate(string $name, ?string $version = null): string
    {
        $filename = $version ? "{$name}_{$version}.md" : "{$name}.md";
        $path = $this->templatePath . '/' . $filename;

        if (!File::exists($path)) {
            throw new \Exception("Template not found: {$filename}");
        }

        return File::get($path);
    }

    /**
     * Substitute variables in a template with provided data
     */
    public function substituteVariables(string $template, array $variables): string
    {
        $processed = $template;

        // TODO: shouldn't we be using mustache variables rather than doing our own find and replace?
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            
            // Convert arrays to strings for template substitution
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            
            // Ensure value is a string
            $value = (string) $value;
            
            $processed = str_replace($placeholder, $value, $processed);
        }

        return $processed;
    }

    /**
     * Get all available templates
     */
    public function getAvailableTemplates(): array
    {
        $files = File::files($this->templatePath);
        $templates = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'md') {
                $templates[] = [
                    'name' => $file->getFilenameWithoutExtension(),
                    'path' => $file->getPathname(),
                    'type' => $this->detectTemplateType($file->getFilename())
                ];
            }
        }

        return $templates;
    }

    /**
     * Detect template type (PI or PK) from filename
     */
    protected function detectTemplateType(string $filename): string
    {
        if (Str::contains($filename, '_pi_')) {
            return 'PI';
        } elseif (Str::contains($filename, '_pk_')) {
            return 'PK';
        }

        return 'UNKNOWN';
    }

    /**
     * Validate that all required variables are present in the template
     */
    public function validateTemplate(string $template, array $requiredVariables): array
    {
        $missingVariables = [];
        $foundVariables = [];

        foreach ($requiredVariables as $variable) {
            $placeholder = '{{' . $variable . '}}';
            if (!Str::contains($template, $placeholder)) {
                $missingVariables[] = $variable;
            } else {
                $foundVariables[] = $variable;
            }
        }

        return [
            'valid' => empty($missingVariables),
            'missing' => $missingVariables,
            'found' => $foundVariables
        ];
    }

    /**
     * Extract variable placeholders from a template
     */
    public function extractVariables(string $template): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        return array_unique($matches[1]);
    }

    /**
     * Load and process a template with variables in one operation
     */
    public function loadAndProcess(string $name, array $variables, ?string $version = null): string
    {
        $template = $this->loadTemplate($name, $version);
        return $this->substituteVariables($template, $variables);
    }

    /**
     * Validate template structure and check for required sections
     */
    public function validateTemplateStructure(string $template): array
    {
        $issues = [];
        $requiredSections = [
            '# Voice Anchor',
            '# Primary Framework',
            '# Core Operating Principles',
            '# Chain-of-Thought',
            '# Few-Shot Priming',
            '# Expertise Integration'
        ];

        foreach ($requiredSections as $section) {
            if (!Str::contains($template, $section)) {
                $issues[] = "Missing required section: {$section}";
            }
        }

        // Check for remaining placeholders
        if (preg_match('/{{[^}]+}}/', $template)) {
            $issues[] = 'Template contains unsubstituted variable placeholders';
        }

        // Check for HTML comments that need processing
        if (preg_match('/<!--[^>]+-->/', $template)) {
            $issues[] = 'Template contains HTML comments that need LLM processing';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }

    /**
     * Extract HTML comments that need LLM processing
     */
    public function extractHTMLComments(string $template): array
    {
        preg_match_all('/<!--\s*([^>]+)\s*-->/', $template, $matches);

        $comments = [];
        foreach ($matches[0] as $index => $fullMatch) {
            $comments[] = [
                'full_match' => $fullMatch,
                'content' => trim($matches[1][$index]),
                'position' => strpos($template, $fullMatch)
            ];
        }

        return $comments;
    }

    /**
     * Get template metadata from YAML frontmatter
     */
    public function getTemplateMetadata(string $template): array
    {
        // Check if template starts with YAML frontmatter
        if (!Str::startsWith($template, '---')) {
            return [];
        }

        // Extract YAML frontmatter
        $parts = explode('---', $template, 3);
        if (count($parts) < 3) {
            return [];
        }

        try {
            $metadata = Yaml::parse($parts[1]);
            return $metadata ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if template has all required variables substituted
     */
    public function hasUnsubstitutedVariables(string $template): bool
    {
        return (bool) preg_match('/{{[^}]+}}/', $template);
    }

    /**
     * Get list of unsubstituted variables in template
     */
    public function getUnsubstitutedVariables(string $template): array
    {
        preg_match_all('/{{([^}]+)}}/', $template, $matches);
        return array_unique($matches[1]);
    }
}
