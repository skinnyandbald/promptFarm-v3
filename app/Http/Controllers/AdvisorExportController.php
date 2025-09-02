<?php

namespace App\Http\Controllers;

use App\Models\Advisor;
use App\Models\PlayerContext;
use App\Services\PlayerContextService;
use App\Services\SimpleQualityService;
use App\Services\AdvisorGenerationService;
use App\Services\AdvisorMetadataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Advisor Export Controller for External ChatGPT Use
 * 
 * Handles advisor exports with Stage 1 (standalone) and Stage 2 (PlayerContext) support.
 */
class AdvisorExportController extends Controller
{
    public function __construct(
        protected PlayerContextService $playerContextService,
        protected SimpleQualityService $qualityService,
        protected AdvisorGenerationService $generationService,
        protected AdvisorMetadataService $metadataService
    ) {}

    /**
     * Export advisor for ChatGPT (Stage 1 - Standalone)
     */
    public function export(Request $request, Advisor $advisor)
    {
        Log::info('Exporting advisor (Stage 1)', ['advisor_id' => $advisor->id]);
        
        $validated = $request->validate([
            'format' => 'in:full,condensed,instructions',
            'include_quality' => 'boolean',
            'strip_metadata' => 'boolean',
            'add_watermark' => 'boolean',
            'smart_headers' => 'boolean'
        ]);
        
        $format = $validated['format'] ?? 'full';
        $includeQuality = $validated['include_quality'] ?? false;
        
        // Metadata handling options
        $stripMetadata = $validated['strip_metadata'] ?? 
                        (app()->environment('production') ? true : false);
        $addWatermark = $validated['add_watermark'] ?? false;
        $smartHeaders = $validated['smart_headers'] ?? false;
        
        try {
            // Generate advisor without player context (Stage 1)
            $result = $this->playerContextService->generatePersonalizedAdvisor(
                $advisor,
                null,  // No player context
                false  // Don't include player context
            );
            
            $exportPackage = $result['export_package'];
            
            // Add quality validation if requested
            if ($includeQuality) {
                $qualityScore = $this->qualityService->scoreGeneratedAdvisor(
                    $result['personalized_pi'],
                    $result['personalized_pk']
                );
                $exportPackage['quality_score'] = $qualityScore;
            }
            
            // Return appropriate format
            return response()->json([
                'success' => true,
                'advisor' => $advisor->name,
                'stage' => 'Stage 1 - Standalone',
                'export' => $this->getExportByFormat($exportPackage, $format),
                'quality' => $includeQuality ? $exportPackage['quality_score'] : null,
                'metadata' => $exportPackage['export_metadata']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Advisor export failed', [
                'advisor_id' => $advisor->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export personalized advisor with player context (Stage 2)
     */
    public function exportPersonalized(Request $request, Advisor $advisor)
    {
        Log::info('Exporting personalized advisor (Stage 2)', ['advisor_id' => $advisor->id]);
        
        $validated = $request->validate([
            'format' => 'in:full,condensed,instructions',
            'include_quality' => 'boolean',
            'include_player_context' => 'boolean'
        ]);
        
        $format = $validated['format'] ?? 'full';
        $includeQuality = $validated['include_quality'] ?? false;
        $includePlayerContext = $validated['include_player_context'] ?? false;
        
        try {
            // Get player context if user is authenticated
            $playerContext = null;
            if (Auth::check() && $includePlayerContext) {
                $playerContext = $this->playerContextService->getPlayerContext(Auth::user());
                
                if (!$playerContext) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No player context found. Please configure your context first.'
                    ], 404);
                }
            }
            
            // Generate advisor with optional player context
            $result = $this->playerContextService->generatePersonalizedAdvisor(
                $advisor,
                $playerContext,
                $includePlayerContext  // Explicit flag for Stage 2
            );
            
            $exportPackage = $result['export_package'];
            
            // Add quality validation if requested
            if ($includeQuality) {
                $qualityScore = $this->qualityService->scoreGeneratedAdvisor(
                    $result['personalized_pi'],
                    $result['personalized_pk']
                );
                $exportPackage['quality_score'] = $qualityScore;
            }
            
            // Return appropriate format
            return response()->json([
                'success' => true,
                'advisor' => $advisor->name,
                'stage' => $includePlayerContext ? 'Stage 2 - PlayerContext' : 'Stage 1 - Standalone',
                'personalized' => $result['personalized'],
                'export' => $this->getExportByFormat($exportPackage, $format),
                'quality' => $includeQuality ? $exportPackage['quality_score'] : null,
                'context_summary' => $result['player_context_summary'],
                'metadata' => $exportPackage['export_metadata']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Personalized advisor export failed', [
                'advisor_id' => $advisor->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ChatGPT setup instructions
     */
    public function getChatGPTInstructions(Request $request, Advisor $advisor)
    {
        try {
            // Generate basic export package to get instructions
            $result = $this->playerContextService->generatePersonalizedAdvisor(
                $advisor,
                null,
                false
            );
            
            return response()->json([
                'success' => true,
                'advisor' => $advisor->name,
                'instructions' => $result['export_package']['setup_instructions']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate instructions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View export history
     */
    public function exportHistory(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required'
            ], 401);
        }
        
        $playerContext = $this->playerContextService->getPlayerContext(Auth::user());
        
        if (!$playerContext) {
            return response()->json([
                'success' => true,
                'exports' => [],
                'message' => 'No export history available'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'total_exports' => $playerContext->exported_advisors_count,
            'last_export' => $playerContext->last_advisor_export_at,
            'context_configured' => true
        ]);
    }

    /**
     * Save player context
     */
    public function savePlayerContext(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required'
            ], 401);
        }
        
        $validated = $request->validate([
            'background_story' => 'nullable|string|max:5000',
            'industry' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'current_challenges' => 'nullable|array',
            'goals' => 'nullable|array',
            'communication_style' => 'nullable|in:direct,collaborative,analytical,inspirational',
            'detail_level' => 'nullable|in:high,medium,low',
            'example_preference' => 'nullable|in:industry_specific,general,mixed',
            'framework_preferences' => 'nullable|array'
        ]);
        
        try {
            $context = $this->playerContextService->savePlayerContext(Auth::user(), $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Player context saved successfully',
                'context' => $context
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to save context: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get player context
     */
    public function getPlayerContext(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required'
            ], 401);
        }
        
        $context = $this->playerContextService->getPlayerContext(Auth::user());
        
        if (!$context) {
            return response()->json([
                'success' => true,
                'context' => null,
                'message' => 'No player context configured'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'context' => $context,
            'summary' => $context->getSummary()
        ]);
    }

    /**
     * Get quality dashboard metrics
     */
    public function qualityDashboard(Request $request)
    {
        try {
            $metrics = $this->qualityService->getDashboardMetrics();
            
            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load quality metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit feedback for exported advisor
     */
    public function submitFeedback(Request $request, Advisor $advisor)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000'
        ]);
        
        try {
            $this->qualityService->collectFeedback(
                $advisor->id,
                $validated['rating'],
                $validated['feedback'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to submit feedback: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download advisor export as file
     */
    public function downloadExport(Request $request, Advisor $advisor)
    {
        $validated = $request->validate([
            'format' => 'in:full,condensed',
            'include_player_context' => 'boolean'
        ]);
        
        $format = $validated['format'] ?? 'full';
        $includePlayerContext = $validated['include_player_context'] ?? false;
        
        try {
            // Get player context if requested
            $playerContext = null;
            if (Auth::check() && $includePlayerContext) {
                $playerContext = $this->playerContextService->getPlayerContext(Auth::user());
            }
            
            // Generate advisor
            $result = $this->playerContextService->generatePersonalizedAdvisor(
                $advisor,
                $playerContext,
                $includePlayerContext
            );
            
            $exportPackage = $result['export_package'];
            $content = $format === 'condensed' 
                ? $exportPackage['condensed_export']
                : $exportPackage['full_export'];
            
            $filename = Str::slug($advisor->name) . '-' . $format . '-export.md';
            
            return response($content)
                ->header('Content-Type', 'text/markdown')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Download failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get export by format
     */
    protected function getExportByFormat(array $exportPackage, string $format): string
    {
        switch ($format) {
            case 'condensed':
                return $exportPackage['condensed_export'];
            case 'instructions':
                return $exportPackage['setup_instructions'];
            case 'full':
            default:
                return $exportPackage['full_export'];
        }
    }
}