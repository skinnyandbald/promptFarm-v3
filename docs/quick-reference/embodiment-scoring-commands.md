# AI Embodiment Analysis - Quick Start Guide

## 🚀 Run Analysis in 30 Seconds

### Basic Score Test
```bash
php artisan tinker --execute="
echo \$scorer = app(App\Services\Validation\AIEmbodimentQualityScorer::class)->scoreAIEmbodiment('YOUR_CONTENT_HERE', ['name' => 'Advisor Name'])['total_score'] . '/100';
"
```

### Full Analysis 
```bash
php artisan tinker --execute="
\$result = app(App\Services\Validation\AIEmbodimentQualityScorer::class)->scoreAIEmbodiment('YOUR_CONTENT', ['name' => 'Name']);
echo 'Score: ' . \$result['total_score'] . '/100 (' . (\$result['valid'] ? 'VALID' : 'NEEDS WORK') . ')' . PHP_EOL;
echo 'Static: ' . \$result['breakdown']['static_analysis']['score'] . ' | Semantic: ' . \$result['breakdown']['semantic_analysis']['score'] . PHP_EOL;
foreach(\$result['recommendations'] as \$i => \$rec) echo (\$i+1) . '. ' . \$rec . PHP_EOL;
"
```

### Component Breakdown
```bash
php artisan tinker --execute="
\$r = app(App\Services\Validation\AIEmbodimentQualityScorer::class)->scoreAIEmbodiment('CONTENT', ['name'=>'Name']);
echo 'Info Density: '.\$r['breakdown']['static_analysis']['components']['information_density']['score'].'/100'.PHP_EOL;
echo 'Constitutional: '.\$r['breakdown']['static_analysis']['components']['constitutional_ai']['score'].'/100'.PHP_EOL;
echo 'Voice Auth: '.\$r['breakdown']['semantic_analysis']['components']['voice_authenticity']['score'].'/100'.PHP_EOL;
echo 'Behavioral: '.\$r['breakdown']['semantic_analysis']['components']['behavioral_triggers']['score'].'/100'.PHP_EOL;
echo 'Context Eng: '.\$r['breakdown']['semantic_analysis']['components']['context_engineering']['score'].'/100'.PHP_EOL;
"
```

## 🎯 Quick Improvement Tips

**Score < 70?** → Add constitutional constraints:
```
- MUST NEVER accept vague briefs without evidence
- FORBIDDEN to work on harmful projects  
- DEFER technical questions to experts
```

**Score 70-80?** → Add few-shot examples:
```
Client: "Best practices suggest..."
Advisor: "Best practices killed more brands than creativity. Show me proof."
```

**Score 80+?** → Optimize voice authenticity with personal anecdotes and signature phrases.

## 📊 Score Ranges
- **60-70**: Basic personality
- **70-80**: Recognizable voice  
- **80-90**: Strong embodiment ⭐ (Target)
- **90+**: Indistinguishable from real advisor

## 📁 Files & Documentation
- **Full Analysis**: `/thoughts/shared/diary/2025-09-03-ai-embodiment-scoring-analysis.md`
- **Improvement Framework**: `/analysis-improvement-framework.md`
- **Implementation**: `/app/Services/Validation/AIEmbodimentQualityScorer.php`