<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Export - {{ $advisor->name ?? 'Select Advisor' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8" x-data="advisorExport()">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Advisor Export System</h1>
            <p class="text-gray-600 mt-2">Export advisors for use in ChatGPT with optional PlayerContext personalization</p>
        </div>

        <!-- Stage Selection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Select Export Stage</h2>
            <div class="grid grid-cols-2 gap-4">
                <button @click="stage = 1; includePlayerContext = false" 
                        :class="stage === 1 ? 'bg-blue-500 text-white' : 'bg-gray-200'"
                        class="p-4 rounded-lg transition-colors">
                    <div class="font-semibold">Stage 1: Standalone</div>
                    <div class="text-sm mt-1">PI + PK only, no personalization</div>
                </button>
                <button @click="stage = 2; includePlayerContext = true" 
                        :class="stage === 2 ? 'bg-blue-500 text-white' : 'bg-gray-200'"
                        class="p-4 rounded-lg transition-colors">
                    <div class="font-semibold">Stage 2: PlayerContext</div>
                    <div class="text-sm mt-1">Personalized with your context</div>
                </button>
            </div>
        </div>

        <!-- Export Options -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Export Options</h2>
            
            <!-- Format Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" x-model="format" value="full" class="mr-2">
                        <span>Full Export (Complete PI + PK)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" x-model="format" value="condensed" class="mr-2">
                        <span>Condensed Export (Essential sections only)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" x-model="format" value="instructions" class="mr-2">
                        <span>Setup Instructions Only</span>
                    </label>
                </div>
            </div>

            <!-- Quality Score -->
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" x-model="includeQuality" class="mr-2">
                    <span>Include Quality Score Analysis</span>
                </label>
            </div>

            <!-- Quality Score Display -->
            <div x-show="qualityScore" class="mb-4 p-4 bg-gray-50 rounded">
                <div class="flex items-center justify-between">
                    <span class="font-medium">Quality Score:</span>
                    <span class="text-2xl font-bold" 
                          :class="{
                              'text-green-600': qualityScore >= 85,
                              'text-yellow-600': qualityScore >= 70 && qualityScore < 85,
                              'text-red-600': qualityScore < 70
                          }"
                          x-text="qualityScore + '%'"></span>
                </div>
                <div class="mt-2 text-sm text-gray-600">
                    <div>PI Score: <span x-text="piScore + '%'"></span></div>
                    <div>PK Score: <span x-text="pkScore + '%'"></span></div>
                </div>
            </div>

            <!-- Export Button -->
            <button @click="exportAdvisor()" 
                    :disabled="loading"
                    class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Export Advisor</span>
                <span x-show="loading">Generating...</span>
            </button>
        </div>

        <!-- PlayerContext Form (Stage 2 only) -->
        <div x-show="stage === 2" class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Player Context Configuration</h2>
            
            <form @submit.prevent="savePlayerContext()">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                        <input type="text" x-model="playerContext.industry" 
                               placeholder="e.g., SaaS, E-commerce, Healthcare"
                               class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Type</label>
                        <input type="text" x-model="playerContext.business_type"
                               placeholder="e.g., Startup, Enterprise, Agency"
                               class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Background Story</label>
                    <textarea x-model="playerContext.background_story" 
                              rows="3"
                              placeholder="Brief background about you and your business..."
                              class="w-full border rounded px-3 py-2"></textarea>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Communication Style</label>
                        <select x-model="playerContext.communication_style" class="w-full border rounded px-3 py-2">
                            <option value="direct">Direct</option>
                            <option value="collaborative">Collaborative</option>
                            <option value="analytical">Analytical</option>
                            <option value="inspirational">Inspirational</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Detail Level</label>
                        <select x-model="playerContext.detail_level" class="w-full border rounded px-3 py-2">
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Example Preference</label>
                        <select x-model="playerContext.example_preference" class="w-full border rounded px-3 py-2">
                            <option value="industry_specific">Industry Specific</option>
                            <option value="general">General</option>
                            <option value="mixed">Mixed</option>
                        </select>
                    </div>
                </div>

                <button type="submit" 
                        class="mt-4 bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600">
                    Save Context
                </button>
            </form>
        </div>

        <!-- Quality Dashboard -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Quality Metrics</h2>
            
            <div class="grid grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gray-50 rounded">
                    <div class="text-2xl font-bold text-blue-600" x-text="metrics.currentAverage + '%'"></div>
                    <div class="text-sm text-gray-600">Current Average</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded">
                    <div class="text-2xl font-bold text-green-600" x-text="metrics.successRate + '%'"></div>
                    <div class="text-sm text-gray-600">Success Rate</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded">
                    <div class="text-2xl font-bold text-purple-600" x-text="metrics.totalExports"></div>
                    <div class="text-sm text-gray-600">Total Exports</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded">
                    <div class="text-2xl font-bold text-orange-600" x-text="metrics.userSatisfaction + '/5'"></div>
                    <div class="text-sm text-gray-600">User Rating</div>
                </div>
            </div>

            <!-- Quality Trend Chart (simplified) -->
            <div class="mt-6">
                <h3 class="font-medium mb-2">Quality Trend (Last 30 Days)</h3>
                <div class="h-32 bg-gray-50 rounded flex items-center justify-center text-gray-500">
                    <!-- In production, integrate with Chart.js or similar -->
                    Quality trend visualization would go here
                </div>
            </div>
        </div>

        <!-- Export Result -->
        <div x-show="exportResult" class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Export Result</h2>
            
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium">Exported Content:</span>
                    <button @click="copyToClipboard()" 
                            class="text-blue-500 hover:text-blue-700 text-sm">
                        Copy to Clipboard
                    </button>
                </div>
                <textarea readonly 
                          x-model="exportContent"
                          rows="10"
                          class="w-full border rounded px-3 py-2 bg-gray-50 font-mono text-sm"></textarea>
            </div>

            <div class="flex gap-4">
                <button @click="downloadExport()" 
                        class="bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600">
                    Download as .md File
                </button>
                <a href="#" @click="openInChatGPT()" 
                   class="bg-purple-500 text-white py-2 px-4 rounded hover:bg-purple-600">
                    Open ChatGPT
                </a>
            </div>
        </div>

        <!-- Feedback Form -->
        <div x-show="exportResult" class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Feedback</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rate this advisor export:</label>
                <div class="flex gap-2">
                    <template x-for="star in 5">
                        <button @click="rating = star"
                                class="text-2xl"
                                :class="star <= rating ? 'text-yellow-500' : 'text-gray-300'">
                            ★
                        </button>
                    </template>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Additional Feedback (Optional)</label>
                <textarea x-model="feedbackText" 
                          rows="3"
                          placeholder="Any suggestions or issues?"
                          class="w-full border rounded px-3 py-2"></textarea>
            </div>
            
            <button @click="submitFeedback()" 
                    class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                Submit Feedback
            </button>
        </div>
    </div>

    <script>
        function advisorExport() {
            return {
                stage: 1,
                format: 'full',
                includeQuality: false,
                includePlayerContext: false,
                loading: false,
                exportResult: false,
                exportContent: '',
                qualityScore: null,
                piScore: null,
                pkScore: null,
                rating: 0,
                feedbackText: '',
                playerContext: {
                    industry: '',
                    business_type: '',
                    background_story: '',
                    communication_style: 'direct',
                    detail_level: 'medium',
                    example_preference: 'mixed'
                },
                metrics: {
                    currentAverage: 0,
                    successRate: 0,
                    totalExports: 0,
                    userSatisfaction: 0
                },

                async init() {
                    await this.loadMetrics();
                    await this.loadPlayerContext();
                },

                async exportAdvisor() {
                    this.loading = true;
                    try {
                        const endpoint = this.stage === 2 ? '/api/advisors/1/export-personalized' : '/api/advisors/1/export';
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            },
                            body: JSON.stringify({
                                format: this.format,
                                include_quality: this.includeQuality,
                                include_player_context: this.includePlayerContext
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.exportResult = true;
                            this.exportContent = data.export;
                            if (data.quality) {
                                this.qualityScore = data.quality.overall_score;
                                this.piScore = data.quality.pi_score;
                                this.pkScore = data.quality.pk_score;
                            }
                        }
                    } catch (error) {
                        console.error('Export failed:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async savePlayerContext() {
                    try {
                        const response = await fetch('/api/player-context', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            },
                            body: JSON.stringify(this.playerContext)
                        });

                        const data = await response.json();
                        if (data.success) {
                            alert('Player context saved successfully!');
                        }
                    } catch (error) {
                        console.error('Failed to save context:', error);
                    }
                },

                async loadPlayerContext() {
                    try {
                        const response = await fetch('/api/player-context');
                        const data = await response.json();
                        if (data.success && data.context) {
                            this.playerContext = data.context;
                        }
                    } catch (error) {
                        console.error('Failed to load context:', error);
                    }
                },

                async loadMetrics() {
                    try {
                        const response = await fetch('/api/quality-dashboard');
                        const data = await response.json();
                        if (data.success) {
                            this.metrics = {
                                currentAverage: data.metrics.current_average || 0,
                                successRate: data.metrics.generation_success_rate || 0,
                                totalExports: data.metrics.export_metrics?.total_exports || 0,
                                userSatisfaction: data.metrics.user_satisfaction || 0
                            };
                        }
                    } catch (error) {
                        console.error('Failed to load metrics:', error);
                    }
                },

                async submitFeedback() {
                    try {
                        const response = await fetch('/api/advisors/1/feedback', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            },
                            body: JSON.stringify({
                                rating: this.rating,
                                feedback: this.feedbackText
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            alert('Thank you for your feedback!');
                            this.rating = 0;
                            this.feedbackText = '';
                        }
                    } catch (error) {
                        console.error('Failed to submit feedback:', error);
                    }
                },

                copyToClipboard() {
                    navigator.clipboard.writeText(this.exportContent);
                    alert('Copied to clipboard!');
                },

                downloadExport() {
                    const blob = new Blob([this.exportContent], { type: 'text/markdown' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'advisor-export.md';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                },

                openInChatGPT() {
                    window.open('https://chat.openai.com/', '_blank');
                }
            }
        }
    </script>
</body>
</html>