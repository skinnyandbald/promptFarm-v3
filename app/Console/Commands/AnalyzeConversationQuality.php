<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AnalyzeConversationQuality extends Command
{
    protected $signature = 'advisor:analyze-conversation 
                            {file : Path to exported conversation file (JSON or MD)}
                            {--format=auto : File format (json, markdown, auto)}';
    
    protected $description = 'Analyze exported ChatGPT conversations for engagement patterns';

    public function handle()
    {
        $filePath = $this->argument('file');
        $format = $this->option('format');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return 1;
        }
        
        $this->info('🔍 CONVERSATION QUALITY ANALYSIS');
        $this->info('=' . str_repeat('=', 50));
        $this->newLine();
        
        $content = file_get_contents($filePath);
        
        // Auto-detect format
        if ($format === 'auto') {
            $format = $this->detectFormat($content, $filePath);
        }
        
        $this->info("📄 File: " . basename($filePath));
        $this->info("📋 Format: $format");
        $this->newLine();
        
        $conversation = $this->parseConversation($content, $format);
        
        if (empty($conversation)) {
            $this->error('Could not parse conversation');
            return 1;
        }
        
        $this->analyzeConversation($conversation);
        
        return 0;
    }
    
    protected function detectFormat($content, $filePath): string
    {
        if (str_ends_with($filePath, '.json') || $this->isJson($content)) {
            return 'json';
        }
        return 'markdown';
    }
    
    protected function isJson($string): bool
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    protected function parseConversation($content, $format): array
    {
        if ($format === 'json') {
            return $this->parseJsonConversation($content);
        } else {
            return $this->parseMarkdownConversation($content);
        }
    }
    
    protected function parseJsonConversation($content): array
    {
        $data = json_decode($content, true);
        if (!$data) return [];
        
        $conversation = [];
        
        // Handle ChatGPT's complex export format with mapping structure
        if (isset($data['mapping'])) {
            // ChatGPT export format with nested mapping
            $mapping = $data['mapping'];
            
            // Build conversation tree
            $messages = [];
            foreach ($mapping as $id => $node) {
                if (!isset($node['message']) || !$node['message']) continue;
                
                $msg = $node['message'];
                $role = $msg['author']['role'] ?? 'unknown';
                
                // Skip system messages and empty content
                if ($role === 'system') continue;
                if (!isset($msg['content']['parts']) || empty($msg['content']['parts'])) continue;
                
                // Get content from parts
                $content = '';
                if (isset($msg['content']['parts'])) {
                    $content = implode("\n", array_filter($msg['content']['parts'], fn($p) => is_string($p) && !empty($p)));
                }
                
                if (empty($content)) continue;
                
                $messages[] = [
                    'id' => $id,
                    'parent' => $node['parent'],
                    'role' => $role,
                    'content' => $content,
                    'create_time' => $msg['create_time'] ?? null
                ];
            }
            
            // Sort by create_time to get chronological order
            usort($messages, function($a, $b) {
                $timeA = $a['create_time'] ?? 0;
                $timeB = $b['create_time'] ?? 0;
                return $timeA <=> $timeB;
            });
            
            // Build final conversation array
            foreach ($messages as $msg) {
                $conversation[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                    'timestamp' => $msg['create_time']
                ];
            }
            
        } elseif (isset($data['messages'])) {
            // Format 1: simple messages array
            foreach ($data['messages'] as $message) {
                $conversation[] = [
                    'role' => $message['role'] ?? 'unknown',
                    'content' => $message['content'] ?? '',
                    'timestamp' => $message['timestamp'] ?? null
                ];
            }
        } elseif (isset($data['conversation'])) {
            // Format 2: conversation object
            foreach ($data['conversation'] as $turn) {
                if (isset($turn['user'])) {
                    $conversation[] = ['role' => 'user', 'content' => $turn['user']];
                }
                if (isset($turn['assistant'])) {
                    $conversation[] = ['role' => 'assistant', 'content' => $turn['assistant']];
                }
            }
        }
        
        return $conversation;
    }
    
    protected function parseMarkdownConversation($content): array
    {
        $conversation = [];
        $lines = explode("\n", $content);
        $currentRole = null;
        $currentContent = [];
        
        foreach ($lines as $line) {
            // Check for role indicators
            if (preg_match('/^(#+ )?(User|Human|You):/i', $line)) {
                if ($currentRole) {
                    $conversation[] = ['role' => $currentRole, 'content' => implode("\n", $currentContent)];
                }
                $currentRole = 'user';
                $currentContent = [preg_replace('/^(#+ )?(User|Human|You):\s*/i', '', $line)];
            } elseif (preg_match('/^(#+ )?(Assistant|AI|ChatGPT|Alex Bogusky):/i', $line)) {
                if ($currentRole) {
                    $conversation[] = ['role' => $currentRole, 'content' => implode("\n", $currentContent)];
                }
                $currentRole = 'assistant';
                $currentContent = [preg_replace('/^(#+ )?(Assistant|AI|ChatGPT|Alex Bogusky):\s*/i', '', $line)];
            } else {
                $currentContent[] = $line;
            }
        }
        
        // Add final message
        if ($currentRole) {
            $conversation[] = ['role' => $currentRole, 'content' => implode("\n", $currentContent)];
        }
        
        return $conversation;
    }
    
    protected function analyzeConversation($conversation)
    {
        $stats = $this->calculateStats($conversation);
        $engagement = $this->analyzeEngagement($conversation);
        $patterns = $this->identifyPatterns($conversation);
        
        // Display basic stats
        $this->info('📊 CONVERSATION STATISTICS');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Messages', $stats['total_messages']],
                ['User Messages', $stats['user_messages']],
                ['Assistant Messages', $stats['assistant_messages']],
                ['Avg User Message Length', $stats['avg_user_length'] . ' chars'],
                ['Avg Assistant Message Length', $stats['avg_assistant_length'] . ' chars'],
                ['Conversation Length', $stats['total_length'] . ' chars'],
            ]
        );
        
        $this->newLine();
        
        // Display engagement analysis
        $this->info('🎯 ENGAGEMENT ANALYSIS');
        $this->table(
            ['Indicator', 'Count', 'Examples'],
            [
                ['User Excitement (!, wow, great, etc.)', $engagement['excitement_count'], implode(', ', array_slice($engagement['excitement_examples'], 0, 3))],
                ['User Questions', $engagement['question_count'], ''],
                ['User Requests for More', $engagement['more_requests'], implode(', ', array_slice($engagement['more_examples'], 0, 2))],
                ['Positive Feedback', $engagement['positive_feedback'], implode(', ', array_slice($engagement['positive_examples'], 0, 3))],
            ]
        );
        
        $this->newLine();
        
        // Display patterns
        $this->info('🔍 CONVERSATION PATTERNS');
        foreach ($patterns as $pattern => $details) {
            $this->line("• $pattern: {$details['count']} occurrences");
            if (!empty($details['examples'])) {
                $this->line("  Examples: " . implode(', ', array_slice($details['examples'], 0, 2)));
            }
        }
        
        $this->newLine();
        
        // Quality assessment
        $qualityScore = $this->calculateQualityScore($engagement, $patterns, $stats);
        $this->info("🏆 CONVERSATION QUALITY SCORE: {$qualityScore}%");
        
        if ($qualityScore >= 80) {
            $this->info('✅ High engagement conversation - analyze for best practices');
        } elseif ($qualityScore >= 60) {
            $this->comment('⚠️  Medium engagement - some good moments');
        } else {
            $this->warn('❌ Low engagement - needs improvement');
        }
        
        // Recommendations
        $this->newLine();
        $this->info('💡 RECOMMENDATIONS FOR PI/PK IMPROVEMENTS:');
        $recommendations = $this->generateRecommendations($engagement, $patterns);
        foreach ($recommendations as $rec) {
            $this->line("• $rec");
        }
    }
    
    protected function calculateStats($conversation): array
    {
        $userMessages = array_filter($conversation, fn($msg) => $msg['role'] === 'user');
        $assistantMessages = array_filter($conversation, fn($msg) => $msg['role'] === 'assistant');
        
        return [
            'total_messages' => count($conversation),
            'user_messages' => count($userMessages),
            'assistant_messages' => count($assistantMessages),
            'avg_user_length' => $userMessages ? round(array_sum(array_map(fn($msg) => strlen($msg['content']), $userMessages)) / count($userMessages)) : 0,
            'avg_assistant_length' => $assistantMessages ? round(array_sum(array_map(fn($msg) => strlen($msg['content']), $assistantMessages)) / count($assistantMessages)) : 0,
            'total_length' => array_sum(array_map(fn($msg) => strlen($msg['content']), $conversation))
        ];
    }
    
    protected function analyzeEngagement($conversation): array
    {
        $userMessages = array_filter($conversation, fn($msg) => $msg['role'] === 'user');
        
        $excitementPatterns = [
            '/\b(wow|amazing|incredible|brilliant|perfect|excellent|fantastic|love this|this is great)\b/i',
            '/!{2,}/',
            '/\b(yes!|exactly!|spot on!|nailed it!)\b/i'
        ];
        
        $moreRequestPatterns = [
            '/\b(tell me more|can you expand|give me more|what else|continue|go deeper)\b/i',
            '/\b(more examples|another example|other ideas)\b/i'
        ];
        
        $positivePatterns = [
            '/\b(good|great|helpful|useful|interesting|insightful)\b/i',
            '/\b(thank you|thanks|appreciate)\b/i'
        ];
        
        $engagement = [
            'excitement_count' => 0,
            'excitement_examples' => [],
            'question_count' => 0,
            'more_requests' => 0,
            'more_examples' => [],
            'positive_feedback' => 0,
            'positive_examples' => []
        ];
        
        foreach ($userMessages as $msg) {
            $content = $msg['content'];
            
            // Count excitement
            foreach ($excitementPatterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    $engagement['excitement_count'] += count($matches[0]);
                    $engagement['excitement_examples'] = array_merge($engagement['excitement_examples'], $matches[0]);
                }
            }
            
            // Count questions
            $engagement['question_count'] += substr_count($content, '?');
            
            // Count requests for more
            foreach ($moreRequestPatterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    $engagement['more_requests'] += count($matches[0]);
                    $engagement['more_examples'] = array_merge($engagement['more_examples'], $matches[0]);
                }
            }
            
            // Count positive feedback
            foreach ($positivePatterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    $engagement['positive_feedback'] += count($matches[0]);
                    $engagement['positive_examples'] = array_merge($engagement['positive_examples'], $matches[0]);
                }
            }
        }
        
        return $engagement;
    }
    
    protected function identifyPatterns($conversation): array
    {
        $assistantMessages = array_filter($conversation, fn($msg) => $msg['role'] === 'assistant');
        
        $patterns = [
            'Asks Questions Back' => ['count' => 0, 'examples' => []],
            'Provides Specific Examples' => ['count' => 0, 'examples' => []],
            'Challenges User' => ['count' => 0, 'examples' => []],
            'Uses First Person' => ['count' => 0, 'examples' => []],
            'References Frameworks' => ['count' => 0, 'examples' => []],
        ];
        
        foreach ($assistantMessages as $msg) {
            $content = $msg['content'];
            
            // Asks questions back
            $questionCount = substr_count($content, '?');
            if ($questionCount > 0) {
                $patterns['Asks Questions Back']['count'] += $questionCount;
                if (preg_match('/[^.!]*\?[^.!]*/', $content, $matches)) {
                    $patterns['Asks Questions Back']['examples'][] = trim($matches[0]);
                }
            }
            
            // Specific examples
            if (preg_match_all('/\b(at \w+|when I worked with|in my experience with \w+)\b/i', $content, $matches)) {
                $patterns['Provides Specific Examples']['count'] += count($matches[0]);
                $patterns['Provides Specific Examples']['examples'] = array_merge($patterns['Provides Specific Examples']['examples'], $matches[0]);
            }
            
            // Challenges user
            if (preg_match_all('/\b(but|however|challenge|question|disagree|push back)\b/i', $content, $matches)) {
                $patterns['Challenges User']['count'] += count($matches[0]);
            }
            
            // First person usage
            $firstPersonCount = substr_count($content, 'I ') + substr_count($content, 'my ') + substr_count($content, "I've");
            if ($firstPersonCount > 0) {
                $patterns['Uses First Person']['count'] += $firstPersonCount;
            }
            
            // References frameworks
            if (preg_match_all('/\b(\w+ framework|\w+ methodology|\w+ approach|my \w+ process)\b/i', $content, $matches)) {
                $patterns['References Frameworks']['count'] += count($matches[0]);
                $patterns['References Frameworks']['examples'] = array_merge($patterns['References Frameworks']['examples'], $matches[0]);
            }
        }
        
        return $patterns;
    }
    
    protected function calculateQualityScore($engagement, $patterns, $stats): int
    {
        $score = 0;
        
        // Base score from engagement
        $score += min(20, $engagement['excitement_count'] * 5);  // Max 20 points for excitement
        $score += min(15, $engagement['more_requests'] * 3);     // Max 15 points for wanting more
        $score += min(10, $engagement['positive_feedback'] * 2); // Max 10 points for positive feedback
        
        // Bonus for conversation patterns
        $score += min(20, $patterns['Asks Questions Back']['count'] * 2);     // Max 20 for interactivity
        $score += min(15, $patterns['Provides Specific Examples']['count']);  // Max 15 for examples
        $score += min(10, $patterns['Challenges User']['count']);            // Max 10 for challenge
        $score += min(10, $patterns['Uses First Person']['count'] / 5);      // Max 10 for voice
        
        return min(100, $score);
    }
    
    protected function generateRecommendations($engagement, $patterns): array
    {
        $recommendations = [];
        
        if ($patterns['Asks Questions Back']['count'] < 3) {
            $recommendations[] = 'PI: Add instruction to ask more follow-up questions';
        }
        
        if ($patterns['Provides Specific Examples']['count'] < 2) {
            $recommendations[] = 'PK: Include more specific, real-world examples with company names';
        }
        
        if ($patterns['Challenges User']['count'] < 2) {
            $recommendations[] = 'PI: Encourage more challenging/contrarian responses';
        }
        
        if ($patterns['Uses First Person']['count'] < 10) {
            $recommendations[] = 'PI: Strengthen first-person voice instructions';
        }
        
        if ($engagement['excitement_count'] < 2) {
            $recommendations[] = 'Overall: Focus on more engaging, surprising insights';
        }
        
        return $recommendations;
    }
}