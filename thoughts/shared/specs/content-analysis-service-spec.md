# ContentAnalysisService Technical Specification

## Overview
Service for extracting and analyzing specific elements from PI/PK content that correlate with conversation success.

## Core Functionality

### 1. PI Element Extraction

#### Tension Triggers
```php
public function extractTensionTriggers(string $piContent): array
{
    // Pattern examples:
    // - "challenge the assumption"
    // - "push back when"
    // - "don't accept"
    // - "demand evidence"
    
    return [
        'count' => 12,
        'examples' => [
            'Challenge vague objectives without metrics',
            'Push back on unrealistic timelines',
        ],
        'strength' => 'high', // low/medium/high based on language intensity
    ];
}
```

#### Challenge Patterns
```php
public function extractChallengePatterns(string $piContent): array
{
    // Identify how the advisor challenges users
    // - Direct confrontation
    // - Socratic questioning
    // - Evidence demands
    // - Alternative perspectives
    
    return [
        'types' => ['direct', 'socratic', 'evidence-based'],
        'frequency' => 8,
        'examples' => [...],
    ];
}
```

#### Voice Markers
```php
public function extractVoiceMarkers(string $piContent): array
{
    // Unique voice characteristics
    // - Sentence structure (short/punchy vs elaborate)
    // - Signature phrases
    // - Emotional tone
    // - Expertise signals
    
    return [
        'sentence_length' => 'short', // short/medium/long
        'signature_phrases' => ['Find the enemy', 'Name the pain'],
        'emotional_tone' => 'provocative',
        'expertise_signals' => 15, // count of expertise demonstrations
    ];
}
```

#### Reasoning Chains
```php
public function extractReasoningChains(string $piContent): array
{
    // Chain-of-thought patterns
    // - Step-by-step instructions
    // - "First... then... finally"
    // - Logical progression markers
    
    return [
        'chain_count' => 5,
        'avg_chain_length' => 4.2, // average steps per chain
        'examples' => [...],
    ];
}
```

### 2. PK Element Extraction

#### Specific Companies
```php
public function extractCompanies(string $pkContent): array
{
    // Extract company mentions and context
    return [
        'count' => 12,
        'companies' => [
            'Apple' => ['context' => 'Think Different campaign', 'year' => 1997],
            'Domino\'s' => ['context' => 'Pizza Tracker innovation', 'year' => 2009],
        ],
        'with_metrics' => 8, // how many include specific numbers
    ];
}
```

#### Metrics and Numbers
```php
public function extractMetrics(string $pkContent): array
{
    // Extract quantitative data
    return [
        'count' => 24,
        'types' => [
            'percentages' => 10,
            'dollar_amounts' => 8,
            'time_periods' => 6,
        ],
        'specificity' => 'high', // vague vs specific
        'examples' => ['47% increase in brand value', '$1.2B in losses'],
    ];
}
```

#### Case Studies
```php
public function extractCaseStudies(string $pkContent): array
{
    // Identify complete case studies
    return [
        'count' => 6,
        'complete_studies' => 4, // problem + solution + result
        'partial_studies' => 2,
        'average_detail_level' => 7.5, // 1-10 scale
        'studies' => [
            [
                'company' => 'Mini Cooper',
                'problem' => 'Revitalize brand in US',
                'solution' => 'Guerrilla marketing',
                'result' => '22% sales increase',
            ],
        ],
    ];
}
```

#### Controversial Stances
```php
public function extractControversial(string $pkContent): array
{
    // Identify controversial or contrarian positions
    return [
        'count' => 5,
        'intensity' => 'medium', // low/medium/high
        'topics' => [
            'Emotion trumps reason in sales',
            'Test until it bleeds',
        ],
        'has_enemies' => true, // explicitly names opposition
    ];
}
```

## Integration Points

### 1. With UnifiedAnalysisCommand
```php
// Enhanced quality analysis
$contentElements = $this->contentAnalysisService->extractElements($piContent, $pkContent);
$correlations = $this->correlationService->analyze($conversationQuality, $contentElements);
```

### 2. With Learning Pipeline
```php
// Feed extracted elements to learning service
$patterns = $this->contentAnalysisService->extractElements($pi, $pk);
$this->learningService->recordPatterns($conversationId, $patterns, $quality);
```

### 3. With Generation Service
```php
// Use successful patterns in generation
$successfulPatterns = $this->contentAnalysisService->getHighPerformingPatterns();
$enhancedPrompt = $this->injectPatterns($basePrompt, $successfulPatterns);
```

## Database Schema

```sql
-- Store extracted elements for analysis (SQLite syntax)
CREATE TABLE content_elements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    conversation_id TEXT NOT NULL,
    element_type TEXT NOT NULL, -- tension_trigger, company_mention, etc.
    element_value TEXT NOT NULL,
    element_context TEXT,
    element_score REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_conversation ON content_elements(conversation_id);
CREATE INDEX idx_type ON content_elements(element_type);

-- Track element performance
CREATE TABLE element_performance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    element_type TEXT NOT NULL,
    element_value TEXT NOT NULL,
    success_count INTEGER DEFAULT 0,
    failure_count INTEGER DEFAULT 0,
    avg_quality_score REAL,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX unique_element ON element_performance(element_type, element_value);
```

## Usage Examples

### Basic Extraction
```php
$piContent = File::get('path/to/PI.md');
$pkContent = File::get('path/to/PK.md');

$elements = $contentAnalysisService->extractElements($piContent, $pkContent);
```

### Quality Correlation
```php
$conversation = $this->parseConversation($jsonFile);
$quality = $this->analyzeQuality($conversation);
$elements = $contentAnalysisService->extractElements($pi, $pk);

$correlation = $this->correlate($quality, $elements);
// Returns: ['high_impact' => [...], 'low_impact' => [...]]
```

### Pattern Learning
```php
foreach ($historicalConversations as $conv) {
    $elements = $contentAnalysisService->extractElements($conv->pi, $conv->pk);
    $this->learningService->learn($elements, $conv->quality_score);
}

$insights = $this->learningService->getInsights();
```

## Testing Strategy

### Unit Tests
```php
class ContentAnalysisServiceTest extends TestCase
{
    public function test_extracts_tension_triggers()
    {
        $pi = "Challenge vague objectives. Push back on assumptions.";
        $triggers = $this->service->extractTensionTriggers($pi);
        
        $this->assertEquals(2, $triggers['count']);
        $this->assertContains('Challenge vague objectives', $triggers['examples']);
    }
    
    public function test_extracts_company_mentions()
    {
        $pk = "Apple's Think Different campaign in 1997 increased brand value by 47%.";
        $companies = $this->service->extractCompanies($pk);
        
        $this->assertArrayHasKey('Apple', $companies['companies']);
        $this->assertEquals(1997, $companies['companies']['Apple']['year']);
    }
}
```

### Integration Tests
```php
public function test_correlates_elements_with_quality()
{
    $conversation = $this->loadTestConversation('high-quality.json');
    $elements = $this->service->extractElements($pi, $pk);
    $correlation = $this->correlationService->analyze($conversation, $elements);
    
    $this->assertGreaterThan(0.7, $correlation['correlation_coefficient']);
}
```

## Performance Considerations

1. **Caching**: Cache extracted elements for repeated analysis
2. **Batch Processing**: Process multiple conversations in parallel
3. **Incremental Updates**: Only re-analyze changed content
4. **Pattern Indexing**: Use database indexes for fast pattern lookup

## Future Enhancements

1. **ML Pattern Recognition**: Use NLP models for deeper pattern extraction
2. **Sentiment Analysis**: Analyze emotional tone correlation
3. **Topic Modeling**: Identify successful topic clusters
4. **Real-time Analysis**: Analyze conversations as they happen
5. **Cross-advisor Learning**: Share successful patterns across advisors