---
name: advisor-generation-system
status: backlog
created: 2025-09-02T00:25:42Z
progress: 0%
prd: .claude/prds/advisor-generation-system.md
github: [Will be updated when synced to GitHub]
---

# Epic: advisor-generation-system

## Overview
Build an AI-powered advisory platform that generates authentic expert personas using a proprietary analytical tensions framework, enabling entrepreneurs to access strategic guidance from virtual advisors within 30 seconds. The system leverages existing Laravel infrastructure with a hybrid generation approach (deterministic templates + targeted LLM enhancement) to achieve 80-90% cost reduction while maintaining quality.

## Architecture Decisions

### Core Design Choices
- **Monolithic Laravel 12 Architecture**: Leverage existing framework expertise, avoid microservices complexity for MVP
- **File-Based PI/PK Storage**: Separate behavioral instructions (PI) from knowledge base (PK) for flexible persona management
- **Hybrid Generation Strategy**: Use deterministic templates for structure, enhance with LLMs only where needed (cost optimization)
- **Progressive Enhancement UI**: Start CLI-only for rapid validation, add API/UI layers incrementally

### Technology Stack Rationale
- **OpenRouter API Integration**: Access to multiple models (Grok-3, GPT-4o-mini) with fallback options
- **Inertia + React**: SPA experience without API complexity, faster development than pure API + frontend
- **PostgreSQL**: Robust data persistence for user sessions, conversation history, and advisor metadata
- **Laravel Boost**: Accelerate development with pre-built tools and patterns

### Key Patterns
- **Analytical Tensions Framework**: Four-stage prompt engineering (Paradox → Evidence → Constraint → Truth) for quality
- **Progressive Compression**: Dynamic content scaling based on council size (2-12+ advisors)
- **Temperature Control**: Strict 0.7-0.85 range for consistent quality (never 0.9+)

## Technical Approach

### Phase 1: Core Generation Engine (Days 1-2)
**CLI-Based Advisor Generation**
- Artisan command for single advisor generation
- PI/PK file creation in `storage/app/advisor-files/`
- Analytical tensions prompt implementation
- Quality validation with 80% threshold gate

**Essential Models & Services**
- `Advisor` model with PI/PK file references
- `AdvisorGenerator` service with retry logic
- `QualityValidator` for authenticity scoring
- `PromptBuilder` for analytical tensions

### Phase 2: Council Orchestration (Days 3-4)
**Multi-Advisor System**
- `Council` model for advisor groupings
- `CouncilOrchestrator` service for routing
- Dynamic compression based on advisor count
- Productive disagreement prompt patterns

**Conversation Management**
- `Conversation` model with history tracking
- Context switching capabilities
- Memory persistence between sessions

### Phase 3: User Interface (Week 2)
**Inertia + React Components**
- Advisor selection interface
- Chat interface with streaming responses
- Council builder with drag-and-drop
- Conversation history viewer

**API Endpoints**
- `POST /api/advisors/generate` - Create new advisor
- `POST /api/councils/create` - Build advisor council  
- `POST /api/conversations/message` - Send query
- `GET /api/advisors/library` - Browse pre-vetted advisors

## Implementation Strategy

### Simplification Opportunities
1. **Reuse Existing Laravel Auth**: Skip custom authentication system
2. **Leverage Eloquent ORM**: Avoid raw SQL, use relationships
3. **File Storage Abstraction**: Use Laravel's Storage facade for PI/PK files
4. **Queue System**: Use Laravel queues for advisor generation (better UX)
5. **Validation Requests**: Use Form Requests for input validation

### Risk Mitigation
- **Model Availability**: Implement fallback chain (Grok-3 → GPT-4o-mini → backup)
- **Quality Issues**: Automated retry with different temperatures
- **Cost Overruns**: Request throttling, user quotas
- **Tomorrow's Deadline**: Focus only on CLI generation, defer everything else

### Testing Approach
- Unit tests for analytical tensions framework
- Integration tests for advisor generation
- Quality benchmarks for core 4 advisors
- Performance tests for council coordination

## Task Breakdown Preview

High-level task categories (keeping to 10 or less):

- [ ] **Task 1: Laravel Project Setup** - Initialize Laravel 12, configure OpenRouter, set up environment
- [ ] **Task 2: Analytical Tensions Framework** - Implement 4-stage prompt builder with templates
- [ ] **Task 3: Advisor Generation Core** - PI/PK file generation, storage management, Artisan command
- [ ] **Task 4: Quality Validation System** - Authenticity scoring, retry mechanism, threshold gates
- [ ] **Task 5: Database Schema & Models** - Advisor, Conversation, User relationships
- [ ] **Task 6: Council Orchestration** - Multi-advisor routing, compression pipeline, disagreement mechanics
- [ ] **Task 7: API Endpoints** - RESTful interface for advisor operations
- [ ] **Task 8: Inertia + React UI** - Chat interface, advisor library, council builder
- [ ] **Task 9: Conversation Management** - History tracking, context switching, memory persistence
- [ ] **Task 10: Performance Optimization** - Caching, queue processing, response streaming

## Dependencies

### External Service Dependencies
- OpenRouter API access (Grok-3, GPT-4o-mini models)
- GitHub for version control
- PostgreSQL database server
- Redis for caching/queues (optional but recommended)

### Internal Team Dependencies
- Access to existing Laravel codebase patterns
- Laravel Boost tools availability
- Existing authentication system (if reusing)

### Prerequisite Work
- OpenRouter API key configuration
- Database setup and migrations
- Storage directory permissions
- Queue worker configuration (if using)

## Success Criteria (Technical)

### Performance Benchmarks
- Advisor generation: < 30 seconds (15 second target)
- Query response: < 5 seconds average
- Council coordination: < 10 seconds for 5 advisors
- Concurrent users: 100+ without degradation

### Quality Gates
- 80% minimum authenticity score per advisor
- 3+ specific examples per response
- Consistent first-person voice
- Zero placeholder responses

### Acceptance Criteria
- CLI generates working advisor in single command
- Council mode handles 2-12 advisors dynamically
- Conversations maintain context across sessions
- System handles OpenRouter API failures gracefully

## Estimated Effort

### Overall Timeline
- **MVP (CLI Only)**: 2 days - Due tomorrow afternoon
- **Full System**: 4 weeks total
- **Production Ready**: Week 4

### Resource Requirements
- 1 full-stack developer (Laravel + React)
- OpenRouter API budget (~$50/day testing)
- PostgreSQL database instance
- Hosting infrastructure (Vercel/Railway)

### Critical Path Items
1. **Day 1**: Analytical tensions framework (blocks everything)
2. **Day 2**: Quality validation (blocks launch)
3. **Day 3**: Council orchestration (blocks multi-advisor)
4. **Week 2**: UI implementation (blocks user adoption)

## Tasks Created
- [ ] 001.md - Laravel Project Setup (parallel: true)
- [ ] 002.md - Analytical Tensions Framework (parallel: false, depends on 001)
- [ ] 003.md - Advisor Generation Core (parallel: false, depends on 001, 002)
- [ ] 004.md - Quality Validation System (parallel: false, depends on 003)
- [ ] 005.md - Database Schema & Models (parallel: true, depends on 001)
- [ ] 006.md - Council Orchestration (parallel: false, depends on 003, 005)
- [ ] 007.md - API Endpoints (parallel: false, depends on 003, 005, 006)
- [ ] 008.md - Inertia + React UI (parallel: false, depends on 007)
- [ ] 009.md - Conversation Management (parallel: false, depends on 005, 007)
- [ ] 010.md - Performance Optimization (parallel: false, depends on 003, 005, 006, 007, 008, 009)

Total tasks: 10
Parallel tasks: 2 (001, 005)
Sequential tasks: 8
Estimated total effort: 102 hours (~2.5 weeks for single developer)