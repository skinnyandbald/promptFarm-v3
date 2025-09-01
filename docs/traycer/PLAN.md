I have created the following plan after thorough exploration and analysis of the codebase. Follow the below plan verbatim. Trust the files and references. Do not re-verify what's written in the plan. Explore only when absolutely necessary. First implement all the proposed file changes and then I'll review all the changes together at the end.

### Observations

Your research shows that current PK generation failures are primarily due to **prompt engineering issues** rather than model limitations. Your empirical analysis demonstrates that standard models with improved prompts can achieve 85-90% quality scores. Since advisors will be deployed to ChatGPT, we need a quality framework that works with external usage rather than internal session tracking. The focus should be on **generation-time quality** and **periodic sampling** rather than real-time session monitoring.

### Approach

Based on your clarification, I'll focus the plan on **Stages 1 and 2 only** (standalone advisor + PlayerContext), with Stage 3 (council) as future consideration. Since the advisors will be used externally in ChatGPT, I'll design a **simple quality measurement framework** that works with external usage patterns and doesn't require session tracking. The approach prioritizes **prompt engineering fixes first** (35-40% quality gains), then **PlayerContext personalization**, with **lightweight quality measurement** that can work with external deployment.

### Reasoning

I analyzed your comprehensive research including the PKPI framework, empirical PK quality analysis, and current Laravel-based system. You clarified that council sessions won't be trackable since advisors will be used in ChatGPT externally, and you want to focus on standalone advisor + PlayerContext initially, with a simple quality measurement framework that doesn't overcomplicate things.

## Mermaid Diagram

sequenceDiagram
    participant U as User
    participant S1 as Stage 1: Standalone
    participant S2 as Stage 2: PlayerContext
    participant E as Export System
    participant Q as Simple Quality
    participant C as ChatGPT (External)

    Note over S1,Q: Stage 1: Fix Core PK Generation
    U->>S1: Request Advisor
    S1->>S1: Apply Improved Prompts
    S1->>S1: Enforce Specificity & Voice
    S1->>Q: Score Quality (Target 85%+)
    S1->>E: Prepare for Export
    E->>U: High-Quality Advisor Ready

    Note over S2,Q: Stage 2: Add PlayerContext
    U->>S2: Input Player Context
    S2->>S2: Load Player Background/Goals
    S2->>S2: Inject Context into PI/PK
    S2->>S2: Generate Personalized Advisor
    S2->>Q: Measure Personalization Quality
    S2->>E: Create Personalized Export

    Note over E,C: External Deployment
    U->>E: Export Advisor for ChatGPT
    E->>E: Format for ChatGPT Compatibility
    E->>E: Generate Setup Instructions
    E->>U: Advisor Files + Instructions
    U->>C: Import Advisor to ChatGPT
    C->>U: Personalized Advisor Experience

    Note over Q,E: Simple Quality Tracking
    Q->>Q: Track Generation Quality
    Q->>Q: Collect User Feedback
    Q->>Q: Monitor Export Success
    Q->>S1: Optimize Prompt Engineering
    Q->>S2: Improve Personalization

## Proposed File Changes

### ../../Users/ben/code/promptFarm-v3/app/Services/AdvisorGenerationService.php(MODIFY)

References: 

- ../../Users/ben/code/promptFarm-v3/docs/analysis/pk-quality-empirical-analysis.md

**Stage 1: Fix Core PK Generation Issues**

Implement the empirically-validated prompt engineering improvements:

1. **Replace buildGenerationPrompt() method** with specificity-enforced version:
   - Demand specific company names (Domino's, Nike, Apple) not placeholders
   - Require exact metrics ("increased sales 47%" not "significant improvement")
   - Enforce real campaign names and dates
   - Mandate first-person voice throughout

2. **Add pre-validation loop** in generatePK() method:
   - Check for placeholder text like `[company]` or `{{}}`
   - Validate sentence length averages (max 15 words)
   - Score content and retry if below 80% quality threshold
   - Maximum 3 attempts before accepting best result

3. **Switch to optimized model configuration**:
   - Use `gpt-4-turbo-preview` instead of deep research models
   - Lower temperature (0.4) for consistency
   - Add structured output validation

4. **Implement voice calibration system**:
   - Extract voice patterns from advisor data
   - Enforce specific sentence structures and vocabulary
   - Add contrarian position requirements
   - Validate authentic voice markers

Expected outcome: 35-40% quality improvement (62% → 85%+) with 80% cost reduction and 6x speed improvement.

### ../../Users/ben/code/promptFarm-v3/app/Services/PlayerContextService.php(NEW)

References: 

- ../../Users/ben/code/promptFarm-v3/app/Services/AdvisorGenerationService.php(MODIFY)
- ../../Users/ben/code/promptFarm-v3/resources/advisor-templates/meta_pi_template.md

**Stage 2: PlayerContext Integration Service**

Create service to handle player context integration for external ChatGPT deployment:

1. **Context Data Management**:
   - Store player background, goals, industry, and preferences
   - Generate context-enhanced advisor files
   - Create player-specific advisor variants

2. **PI-Level Integration** (Primary focus):
   - Inject player context into advisor instructions
   - Customize communication style based on player preferences
   - Adapt response formats and detail levels
   - Personalize examples and case studies

3. **PK-Level Integration** (Secondary):
   - Filter advisor knowledge based on player's industry/domain
   - Emphasize relevant frameworks and methodologies
   - Customize battle-tested case studies to player's context

4. **Export for External Use**:
   - Generate player-specific advisor files for ChatGPT import
   - Create condensed versions that fit ChatGPT context limits
   - Provide clear instructions for ChatGPT setup
   - Include player context summary for advisor reference

This service generates personalized advisor files that can be easily imported into ChatGPT for external use.

### ../../Users/ben/code/promptFarm-v3/app/Models/PlayerContext.php(NEW)

References: 

- ../../Users/ben/code/promptFarm-v3/app/Models/User.php
- ../../Users/ben/code/promptFarm-v3/app/Models/Advisor.php

**Stage 2: PlayerContext Data Model**

Create Eloquent model for player context storage and advisor personalization:

1. **Core Player Data**:
   - Background and origin story
   - Industry and business type
   - Current challenges and goals
   - Communication preferences
   - Success metrics and KPIs

2. **Advisor Preferences**:
   - Preferred advisor types and styles
   - Response detail level preferences
   - Example types (industry-specific vs general)
   - Framework preferences

3. **Export Tracking**:
   - Track which personalized advisors have been generated
   - Store export timestamps and versions
   - Maintain advisor effectiveness feedback (when available)

4. **Relationships**:
   - Belongs to User
   - Has many PersonalizedAdvisors
   - Tracks advisor generation history

Since advisors will be used externally in ChatGPT, this model focuses on generating and tracking personalized advisor exports rather than real-time session management.

### ../../Users/ben/code/promptFarm-v3/database/migrations/2025_09_01_120000_create_player_contexts_table.php(NEW)

References: 

- ../../Users/ben/code/promptFarm-v3/database/migrations/2025_01_01_000001_create_advisors_table.php

**Stage 2: PlayerContext Database Schema**

Create migration for player context storage optimized for external advisor deployment:

1. **Core Fields**:
   - `user_id` (foreign key to users table)
   - `background_story` (text) - player's defining origin story
   - `industry` (string) - primary business domain
   - `business_type` (string) - startup, enterprise, agency, etc.
   - `current_challenges` (json) - array of current pain points
   - `goals` (json) - short and long-term objectives

2. **Preferences**:
   - `communication_style` (enum) - direct, collaborative, analytical
   - `detail_level` (enum) - high, medium, low
   - `example_preference` (enum) - industry_specific, general, mixed
   - `framework_preferences` (json) - preferred methodologies

3. **Export Tracking**:
   - `last_advisor_export_at` (timestamp)
   - `exported_advisors_count` (integer)
   - `feedback_notes` (text) - manual feedback on advisor effectiveness
   - `created_at` and `updated_at` timestamps

This schema supports external advisor deployment by focusing on context storage and export tracking rather than real-time session management.

### ../../Users/ben/code/promptFarm-v3/app/Services/SimpleQualityService.php(NEW)

References: 

- ../../Users/ben/code/promptFarm-v3/app/Services/Validation/AdvisorQualityService.php

**Simple Quality Measurement Framework for External Deployment**

Create lightweight quality measurement that works with external ChatGPT usage:

1. **Generation-Time Quality Scoring**:
   - Score advisors at creation time using existing `AdvisorQualityService`
   - Track quality trends over time
   - Identify which prompt engineering changes improve scores
   - Store quality metrics with each generated advisor

2. **Periodic Sampling and Testing**:
   - Generate test advisors weekly with standard prompts
   - Compare quality scores across different configurations
   - A/B test prompt variations on sample advisors
   - Track quality regression or improvement

3. **User Feedback Collection**:
   - Simple feedback forms for advisor effectiveness
   - Optional user ratings when they export advisors
   - Collect qualitative feedback on advisor performance
   - Track which advisor types are most requested

4. **Quality Reporting Dashboard**:
   - Simple metrics: average quality scores, generation success rate
   - Trend analysis: quality over time, prompt effectiveness
   - User satisfaction: feedback ratings and export frequency
   - Cost and speed metrics: generation time and model costs

5. **Automated Quality Alerts**:
   - Alert when quality scores drop below thresholds
   - Notify when generation failures increase
   - Flag advisors that fail validation checks

This framework provides actionable quality insights without requiring session tracking from external ChatGPT usage.

### ../../Users/ben/code/promptFarm-v3/app/Http/Controllers/AdvisorExportController.php(NEW)

References: 

- ../../Users/ben/code/promptFarm-v3/app/Http/Controllers/AdvisorGenerationController.php

**Advisor Export Controller for External ChatGPT Use**

Create controller to handle advisor exports for external deployment:

1. **Export Endpoints**:
   - `POST /advisors/{advisor}/export` - Export advisor for ChatGPT
   - `POST /advisors/{advisor}/export-personalized` - Export with player context
   - `GET /advisors/{advisor}/chatgpt-instructions` - Get setup instructions
   - `GET /exports/history` - View export history

2. **Export Formats**:
   - **Full Export**: Complete PI + PK for advanced users
   - **Condensed Export**: Essential advisor info within ChatGPT limits
   - **Setup Instructions**: Step-by-step ChatGPT configuration guide
   - **Context Summary**: Player context for advisor reference

3. **Quality Validation**:
   - Validate advisor quality before export
   - Check for template artifacts and placeholders
   - Ensure voice consistency and specificity
   - Provide quality score with export

4. **Usage Tracking**:
   - Track which advisors are exported most frequently
   - Monitor export success rates
   - Collect optional user feedback on export quality
   - Store export metadata for analysis

5. **ChatGPT Optimization**:
   - Format advisors for optimal ChatGPT performance
   - Include clear role definitions and constraints
   - Provide context injection instructions
   - Add troubleshooting tips for common issues

This controller enables seamless advisor deployment to external ChatGPT while maintaining quality tracking.

### ../../Users/ben/code/promptFarm-v3/resources/views/advisor-export.blade.php(NEW)

References: 

- ../../Users/ben/code/promptFarm-v3/resources/views/welcome.blade.php

**Advisor Export Interface**

Create simple web interface for advisor export and quality tracking:

1. **Export Options**:
   - Radio buttons for export format (Full, Condensed, Instructions-only)
   - Checkbox for PlayerContext integration
   - Quality score display with color coding
   - Export button with loading state

2. **Quality Dashboard**:
   - Simple metrics cards: Average Quality, Export Count, Success Rate
   - Recent exports list with quality scores
   - Quality trend chart (last 30 days)
   - Top-performing advisors list

3. **ChatGPT Setup Guide**:
   - Step-by-step instructions for ChatGPT import
   - Copy-paste ready advisor content
   - Troubleshooting common issues
   - Best practices for ChatGPT usage

4. **Feedback Collection**:
   - Simple star rating for exported advisors
   - Optional text feedback field
   - "Report Issue" button for quality problems
   - Success stories submission

5. **PlayerContext Integration**:
   - Form to input/edit player context
   - Preview of personalized advisor changes
   - Context summary for reference
   - Save context for future exports

This interface provides a simple, user-friendly way to export advisors and track quality without overcomplicating the system.

### ../../Users/ben/code/promptFarm-v3/tests/Feature/AdvisorExportTest.php(NEW)

References: 

- ../../Users/ben/code/promptFarm-v3/tests/Feature/AdvisorGenerationTest.php
- ../../Users/ben/code/promptFarm-v3/app/Services/Validation/AdvisorQualityService.php

**Testing Framework for Two-Stage Implementation**

Create focused tests for standalone advisor + PlayerContext stages:

1. **Stage 1 Tests (Standalone Advisor)**:
   - Test PK generation quality improvements (target 85%+ scores)
   - Validate voice consistency and specificity requirements
   - Verify cost and speed improvements (80% cost reduction, 6x speed)
   - Test template artifact elimination
   - Validate export format quality

2. **Stage 2 Tests (PlayerContext Integration)**:
   - Test PI-level personalization effectiveness
   - Validate context-aware advisor responses
   - Test PK-level context filtering and relevance
   - Verify personalized export quality
   - Test context injection accuracy

3. **Export Quality Tests**:
   - Test different export formats (Full, Condensed, Instructions)
   - Validate ChatGPT compatibility
   - Test export file size limits
   - Verify setup instruction accuracy

4. **Simple Quality Measurement Tests**:
   - Test generation-time quality scoring
   - Validate quality trend tracking
   - Test feedback collection and storage
   - Verify quality alert thresholds

5. **Integration Tests**:
   - Test seamless progression from Stage 1 to Stage 2
   - Validate that PlayerContext enhances rather than degrades quality
   - Test export workflow end-to-end
   - Verify quality measurement accuracy

This testing framework ensures both stages deliver expected improvements while maintaining simplicity and external deployment compatibility.

### ../../Users/ben/code/promptFarm-v3/config/advisors.php(MODIFY)

**Enhanced Configuration for Two-Stage Implementation**

Update advisor configuration to support standalone advisor + PlayerContext stages:

1. **Stage 1 Configurations**:
   - Add prompt engineering settings (specificity requirements, voice calibration)
   - Configure model selection (gpt-4-turbo-preview vs deep research)
   - Set quality thresholds and validation rules (minimum 80% score)
   - Define voice pattern enforcement settings
   - Configure export format options

2. **Stage 2 PlayerContext Settings**:
   - Configure PI vs PK integration preferences
   - Set personalization levels and context injection points
   - Define player context data sources and validation
   - Configure context-aware enhancement parameters
   - Set context summary generation rules

3. **Export and Quality Settings**:
   - Configure export format templates
   - Set ChatGPT compatibility requirements
   - Define quality measurement intervals
   - Configure feedback collection settings
   - Set quality alert thresholds

4. **Simple Quality Framework**:
   - Configure generation-time quality scoring
   - Set periodic sampling schedules
   - Define quality trend analysis parameters
   - Configure automated quality alerts
   - Set user feedback collection rules

This configuration focuses on the two active stages while maintaining simplicity and external deployment compatibility.
