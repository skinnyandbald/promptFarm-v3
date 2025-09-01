<?php

namespace App\Services;

use Illuminate\Support\Str;

class AdvisorMetadataService
{
    /**
     * Strip all metadata from advisor content for clean export
     */
    public function stripMetadata(string $content): string
    {
        // Remove YAML frontmatter at the beginning (everything between --- markers)
        $content = preg_replace('/^---[\s\S]*?---\s*\n/m', '', $content);
        
        // CRITICAL: Remove YAML footer blocks too!
        // These often appear at the end of files like:
        // ## Version Notes
        // ```yaml
        // pi_version: v1.0
        // ```
        $content = preg_replace('/^##\s*Version Notes[\s\S]*?```yaml[\s\S]*?```[\s\S]*$/m', '', $content);
        
        // Also remove standalone YAML blocks that might be metadata
        $content = preg_replace('/^```yaml\s*\n(pi_version|pk_version|template_|validation_|compatible_)[\s\S]*?```\s*$/m', '', $content);
        
        // Remove any footer metadata sections that start with --- at end of file
        $content = preg_replace('/\n---\s*\n[\s\S]*?^(pi_version|pk_version|template_type|validation_)[\s\S]*$/m', '', $content);
        
        // Remove HTML comments
        $content = preg_replace('/<!--[\s\S]*?-->/m', '', $content);
        
        // Remove any remaining template variables
        $content = preg_replace('/\{\{\s*[^}]+\s*\}\}/', '', $content);
        
        // Clean up extra whitespace
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Remove trailing "No newline at end of file" markers
        $content = preg_replace('/\s*No newline at end of file\s*$/', '', $content);
        
        return trim($content);
    }
    
    /**
     * WARNING: Zero-width character watermarking DOES NOT WORK
     * 
     * LLMs DO process zero-width Unicode characters - they are tokenized and can:
     * - Affect the model's understanding and response
     * - Break tokenization patterns
     * - Degrade performance
     * - Be detected and reported by the LLM
     * 
     * @deprecated This method is based on a false assumption. Do not use.
     * @see https://github.com/yourusername/advisor-system/docs/zero-width-analysis.md
     */
    public function addWatermark(string $content, array $metadata): string
    {
        // DO NOT USE THIS METHOD - IT DOESN'T WORK AS INTENDED
        // Zero-width characters ARE visible to LLMs and will affect their behavior
        
        // If you need version tracking, use one of these alternatives:
        // 1. External metadata files (recommended)
        // 2. Database tracking with content hashing
        // 3. URL parameters for version identification
        // 4. Explicit version footers that are stripped before export
        
        // Returning content unchanged - watermarking disabled
        return $content;
    }
    
    /**
     * WARNING: Zero-width character detection is unreliable
     * 
     * @deprecated This method is based on a false assumption about LLM processing
     */
    public function extractWatermark(string $content): ?array
    {
        // This method is deprecated - zero-width characters are not invisible to LLMs
        // Returning null as watermarking is disabled
        return null;
    }
    
    /**
     * Create smart headers that help rather than confuse
     */
    public function createSmartHeaders(array $advisorData): string
    {
        $headers = [];
        
        // Only include headers that reinforce the persona
        if (!empty($advisorData['name'])) {
            $headers[] = "advisor: {$advisorData['name']}";
        }
        
        if (!empty($advisorData['expertise'])) {
            $headers[] = "expertise: {$advisorData['expertise']}";
        }
        
        // Skip technical metadata like template_type, validation_status, etc.
        
        if (empty($headers)) {
            return '';
        }
        
        return "---\n" . implode("\n", $headers) . "\n---\n\n";
    }
    
    /**
     * Prepare content for ChatGPT export
     */
    public function prepareForExport(string $content, array $options = []): string
    {
        $stripMetadata = $options['strip_metadata'] ?? true;
        $addWatermark = $options['add_watermark'] ?? false;
        $smartHeaders = $options['smart_headers'] ?? false;
        
        if ($stripMetadata) {
            $content = $this->stripMetadata($content);
        }
        
        // WARNING: Zero-width watermarking is disabled - it affects LLM behavior
        // if ($addWatermark && isset($options['metadata'])) {
        //     $content = $this->addWatermark($content, $options['metadata']);
        // }
        
        if ($smartHeaders && isset($options['advisor_data'])) {
            $headers = $this->createSmartHeaders($options['advisor_data']);
            $content = $headers . $content;
        }
        
        return $content;
    }
    
    /**
     * @deprecated Zero-width encoding doesn't work for LLM watermarking
     */
    private function encodeWatermark(string $version, string $date, string $quality): string
    {
        // Method disabled - zero-width characters are processed by LLMs
        return '';
    }
    
    /**
     * @deprecated Zero-width decoding is unreliable
     */
    private function decodeWatermark(string $encoded): array
    {
        // Method disabled - returning empty array
        return [];
    }
    
    /**
     * @deprecated Do not use zero-width characters for LLM content
     */
    private function charToZeroWidth(string $char): string
    {
        // Method disabled - zero-width characters affect LLM behavior
        return '';
    }
}