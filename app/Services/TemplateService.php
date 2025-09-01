<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
        
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
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
    public function validateTemplate(string $template, array $requiredVariables): bool
    {
        foreach ($requiredVariables as $variable) {
            $placeholder = '{{' . $variable . '}}';
            if (!Str::contains($template, $placeholder)) {
                return false;
            }
        }
        
        return true;
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
}