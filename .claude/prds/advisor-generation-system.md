---
name: advisor-generation-system
description: AI-powered board of advisors platform for entrepreneurs to get expert strategic guidance and accelerate decision-making
status: backlog
created: 2025-09-02T00:11:06Z
---

# PRD: advisor-generation-system

## Executive Summary

The Advisor Generation System is an AI-powered platform that enables entrepreneurs, founders, and growth-minded professionals to instantly access personalized strategic advice from a virtual board of advisors. By leveraging a proprietary analytical tensions framework and hybrid generation approach, the system creates highly authentic advisor personas based on real experts, delivering guidance that is "100x more helpful" than generic AI assistants. The platform transforms decision-making by providing diverse perspectives, challenging assumptions, and applying proven frameworks to users' specific contexts—all within 30 seconds instead of days.

## Problem Statement

### What problem are we solving?

Entrepreneurs and growth professionals face critical decisions daily but lack access to high-quality strategic advisors who can challenge their thinking and provide diverse perspectives. Current solutions fail because:

1. **Generic AI Assistants Fall Short**: ChatGPT and similar tools provide surface-level advice without the depth, personality, or confrontational insights of real experts
2. **Real Advisors Are Inaccessible**: Top advisors like Alex Hormozi, Naval Ravikant, or Paul Graham are expensive ($10K+/month) or simply unavailable
3. **Decision Paralysis**: Without diverse perspectives, entrepreneurs get stuck in their own mental models, missing blind spots and opportunities
4. **Speed vs Quality Tradeoff**: Current approaches require either quick but shallow advice (ChatGPT) or deep but slow/expensive advice (real advisors)

### Why is this important now?

- The AI revolution has reached a capability threshold where authentic persona generation is possible through analytical tensions
- Entrepreneurs are moving faster than ever and need real-time strategic guidance
- Cost optimization breakthroughs (80-90% reduction) make this economically viable
- The gap between generic AI and human expertise creates a massive market opportunity
- V1 deadline tomorrow afternoon requires immediate action on proven approach

## User Stories

### Primary User Personas

**1. The Solo Tech Founder (Primary)**
- Building their first or second startup in tech
- Makes 10+ strategic decisions weekly across multiple domains
- Needs diverse perspectives but can't afford $50K+ advisory board
- Values speed and quality equally
- Pain: "I'm stuck in my own head and don't know what I don't know"

**2. The Growth Professional**
- Marketing, product, or growth leader at scaling startup
- Needs to pressure-test strategies before execution
- Seeks specific frameworks from industry leaders (Hormozi's value equation, Graham's startup advice)
- Wants to level up rapidly by learning from the best
- Pain: "Generic advice doesn't apply to my specific situation"

**3. The Serial Entrepreneur**
- Experienced founder exploring new ventures or markets
- Needs specialized expertise in unfamiliar domains quickly
- Values contrarian perspectives and uncomfortable truths
- Seeks to compress learning curves from months to hours
- Pain: "I need domain expertise now, not after 6 months of research"

### Detailed User Journeys

**Journey 1: Strategic Decision Validation**
1. Founder faces critical product pivot decision with 3 options
2. Selects relevant advisors (e.g., Paul Graham for startup strategy, April Dunford for positioning)
3. Presents situation: current metrics, market feedback, resource constraints
4. Receives diverse perspectives with specific frameworks applied
5. Council surfaces uncomfortable truth about ignoring customer segment
6. Makes confident decision with clear action plan in 30 minutes vs 3 days

**Journey 2: Skill Development Through Expert Frameworks**
1. Growth marketer struggles with landing page conversion (2% rate)
2. Adds copywriting expert advisor (Eugene Schwartz persona)
3. Learns "5 levels of awareness" framework through contextual application
4. Iterates copy with advisor applying framework to specific product
5. Achieves 5.5% conversion rate while internalizing transferable framework

**Journey 3: Custom Advisor Creation for Niche Expertise**
1. Founder needs advice on community-led growth strategy
2. Wants Greg Isenberg's specific approach but he's not pre-vetted
3. Provides 5 articles/interviews about Isenberg's philosophy
4. System generates authentic persona using analytical tensions in 30 seconds
5. Validates quality through confrontational tone and specific examples
6. Adds to personal board for ongoing consultation

### Pain Points Being Addressed

- **"I'm stuck in my own head"** → Council creates productive disagreement to break loops
- **"I don't know what I don't know"** → Advisors surface blind spots with uncomfortable truths
- **"Generic advice doesn't apply"** → Contextual guidance with real company examples
- **"I can't afford real advisors"** → 99% cost reduction ($0.01 vs $1000+ per session)
- **"I need answers now, not next week"** → 30-second generation, 5-second responses
- **"I'm weak in certain areas"** → Leverage expert frameworks to level up quickly

## Requirements

### Functional Requirements

**Core Features**

1. **Individual Advisor Generation**
   - Generate advisor from any public figure with sufficient information
   - Achieve 80%+ authenticity using analytical tensions framework
   - Support both pre-vetted library and custom advisor creation
   - Maintain consistent first-person confrontational voice
   - Include 3+ specific examples (real companies/numbers) per response

2. **Council Mode (Multi-Advisor Orchestration)**
   - Support 2-12+ advisors dynamically (not hardcoded to 4)
   - Enable productive disagreement between advisors
   - Intelligent routing based on expertise keywords
   - Progressive compression: 2 advisors (no compression) → 12+ (60 lines)
   - Single council PI file orchestrating individual advisor calls

3. **Quality Validation System**
   - Automated authenticity scoring (80% minimum threshold)
   - Retry mechanism for below-threshold responses
   - Voice consistency checking across interactions
   - Specific example verification (companies, metrics, dates)
   - Confrontational tone validation

4. **Conversation Management**
   - Store conversation history per user per advisor/council
   - Enable context switching between topics
   - Support follow-up questions with memory
   - Allow advisor hot-swapping mid-conversation
   - Export conversations for learning/reference

5. **Advisor Library & Discovery**
   - Pre-vetted advisor library (initial: Hormozi, Naval, Graham, Dunford)
   - Category browsing (Growth, Product, Engineering, Creative, etc.)
   - Advisor matching based on user's challenge
   - Community-contributed advisors with quality ratings
   - Personal board management (typically 15 advisors)

**User Interactions & Flow**

- **CLI Interface (M0-M1)**: Direct file generation and testing
- **API Layer (M1-M2)**: RESTful endpoints for advisor operations
- **Web UI (M2-M3)**: Inertia + React + TypeScript interface
- **Progressive Enhancement**: CLI → API → UI → Mobile-responsive

### Non-Functional Requirements

**Performance**
- Advisor generation: < 30 seconds (target: 15 seconds)
- Standard query response: < 5 seconds
- Council coordination: < 10 seconds for 5-advisor council
- System availability: 99.9% uptime SLA
- Concurrent operations: 100+ simultaneous generations

**Security & Privacy**
- End-to-end encryption for sensitive user data
- Industry-standard authentication (JWT + refresh tokens)
- Data isolation between users (no cross-contamination)
- GDPR-compliant data handling
- Encrypted database for personal/business information
- No storage of regulated advice (medical/legal/financial)

**Scalability**
- Support 1000+ concurrent users initially
- Handle 10,000+ stored advisors across system
- Process 100+ requests/second at peak
- Horizontal scaling capability built-in
- Progressive optimization as volume scales

**Cost Efficiency**
- 80-90% reduction vs GPT-4 deep research approaches
- < $0.01 per advisor query average (target: $0.005)
- Hybrid approach: Deterministic templates + targeted LLM enhancement
- Caching layer for repeated advisor activations

## Success Criteria

### Primary Metrics
- **Decision Speed**: 10x faster strategic decisions (3 days → 30 minutes)
- **Advisor Quality**: 80%+ authenticity rating (90% target for core advisors)
- **User Activation**: 70% create personal board within first session
- **Idea Generation**: 3x more actionable ideas vs ChatGPT baseline

### Secondary Metrics
- **Framework Adoption**: Users apply 5+ new frameworks monthly
- **Cost per Insight**: 99% reduction vs human advisors ($1000 → $0.01)
- **Time to First Value**: Working advisor in < 30 seconds
- **Council Utilization**: 40% of sessions use multi-advisor councils
- **Custom Advisor Success**: 60% of custom advisors meet quality threshold

### Key Performance Indicators (KPIs)
- Daily Active Users (DAU): Target 100+ by week 2
- Advisors per User: Average 15, median 12
- Questions per Session: Average 7, showing deep engagement
- Repeat Usage: 80% return within 48 hours
- Net Promoter Score: 60+ (would recommend to other founders)

## Constraints & Assumptions

### Technical Constraints
- Laravel 12 monolithic architecture (no microservices)
- File-based PI/PK storage architecture
- OpenRouter API dependency (Grok-3, GPT-4o-mini)
- Temperature settings: 0.7-0.85 range (NEVER 0.9+)
- Single developer for MVP implementation
- 4-day timeline for functional v1 (tomorrow afternoon deadline)

### Resource Constraints
- No budget for GPT-4 or Claude-3 (must use optimized models)
- Limited to English language initially
- No mobile native app development resources
- No dedicated DevOps (must be self-hosted initially)

### Critical Assumptions
- Sufficient public information exists for quality advisor generation
- Users comfortable with AI-generated advice for strategic decisions
- OpenRouter maintains model availability and pricing
- Analytical tensions framework produces consistent quality
- 80% quality threshold is sufficient for user value
- Laravel Boost accelerates development sufficiently for timeline

## Out of Scope

### Explicitly NOT Building (v1)
- Real-time collaboration between multiple users
- Voice/video interface or avatar generation
- Mobile native applications (iOS/Android)
- Non-English language support
- Legal/medical/financial regulated advice
- Web scraping for advisor research (requires manual input)
- Custom model fine-tuning or training
- Blockchain/cryptocurrency integration
- Social features (public sharing, following, discovery)
- Marketplace for buying/selling advisor personas
- Integration with external tools (Slack, Discord, etc.)

### Future Considerations (v2+)
- API for third-party integrations (CRM, note-taking apps)
- White-label B2B solution for companies
- Team collaboration features (shared boards)
- Advanced analytics dashboard with insight tracking
- Advisor personality evolution based on feedback
- Real-time data integration (market data, news)
- Scheduled advisor check-ins and reports
- Mobile app with offline advisor access

## Dependencies

### External Dependencies
- **OpenRouter API**: Primary LLM provider
  - Grok-3 for PI enhancement (voice authenticity)
  - GPT-4o-mini for PK generation (analytical tensions)
  - Fallback models for redundancy
- **GitHub**: Version control and CI/CD
- **Hosting**: Vercel/Railway for deployment
- **Database**: PostgreSQL for user data
- **Future**: Stripe for payment processing

### Internal Dependencies
- **PI/PK File Architecture**: Core persona storage pattern
  - PI: Behavioral rules, voice, interaction patterns (~1000 words)
  - PK: Deep knowledge, frameworks, examples (4000-6000 words)
- **Analytical Tensions Framework**: Quality generation method
  - Paradox → Evidence → Constraint → Uncomfortable Truth
- **Template Compression Pipeline**: Council efficiency
  - Meta template (150 lines) → Production (87 lines)
- **Laravel Boost**: Development acceleration tools

### Technical Stack Dependencies
- PHP 8.3+ (Laravel 12 requirement)
- Laravel 12 (latest framework)
- Inertia.js (SPA without API complexity)
- React + TypeScript (UI components)
- Tailwind CSS (rapid styling)
- PostgreSQL/MySQL (data persistence)

## Implementation Roadmap

### Phase 1: MVP Foundation (Day 1-2) - Due Tomorrow Afternoon
**Day 1 Morning**
- Set up Laravel 12 project structure
- Implement analytical tensions prompt framework
- Create PI/PK file generation system
- Build single advisor CLI generator

**Day 1 Afternoon**
- Quality validation system (80% threshold)
- Retry mechanism for failed generations
- Test with 4 core advisors (Hormozi, Naval, Graham, Dunford)
- Achieve 80% quality baseline

**Day 2 Morning**
- Voice refinement sprint (target 90% quality)
- Temperature optimization (0.7-0.85 range)
- Extended voice anchors (300+ words)
- Specific example injection

**Day 2 Afternoon**
- Basic persistence layer
- Simple CLI interface for testing
- Generate 10+ advisors for validation
- Document generation process

### Phase 2: Multi-Advisor System (Day 3-4)
**Day 3**
- Council orchestration architecture
- Dynamic routing system
- Progressive compression pipeline
- Productive disagreement mechanics

**Day 4**
- Council testing with various sizes (2-12 advisors)
- Performance optimization
- API endpoint development
- Integration testing

### Phase 3: Production Interface (Week 2)
- Inertia + React UI setup
- User authentication system
- Conversation management
- Advisor library interface
- Payment integration prep

### Phase 4: Scale & Polish (Week 3-4)
- Performance optimization
- Advanced council features
- Analytics and insights
- Community features
- Mobile responsiveness

## Risk Mitigation

### Technical Risks
- **Model Availability**: Multi-provider fallback strategy, cached responses
- **Quality Degradation**: Automated testing, quality gates, user feedback loops
- **Cost Overruns**: Progressive optimization, request throttling, usage caps
- **Security Breach**: Encryption, regular audits, minimal data retention

### Business Risks
- **User Adoption**: Free tier, referral program, founder community seeding
- **Competition**: First-mover advantage, quality moat, rapid iteration
- **Regulatory Issues**: Clear disclaimers, no regulated advice, terms of service
- **Advisor Authenticity**: User-reported issues, quality voting, curation

### Timeline Risks
- **Tomorrow's Deadline**: Focus on CLI MVP only, defer UI
- **Quality vs Speed**: Pre-built templates for core 4 advisors
- **Technical Blockers**: Laravel Boost for rapid development
- **Testing Time**: Parallel testing during development

## Appendix

### A. Analytical Tensions Framework (Secret Sauce)
The core innovation enabling 80-90% cost reduction while maintaining quality:

1. **Paradox Stage**: Surface the tension between common belief and reality
   - "Everyone thinks X, but actually Y"
   - Creates cognitive dissonance that mirrors expert thinking

2. **Evidence Stage**: Require specific, verifiable examples
   - Real company names, actual metrics, specific dates
   - "Shopify did X in 2019 and saw Y result"

3. **Constraint Stage**: Explain why the problem persists
   - Structural, psychological, or market forces
   - "This persists because founders fear..."

4. **Uncomfortable Truth Stage**: Deliver hard guidance
   - What they need to hear, not want to hear
   - "You need to fire your co-founder" level directness

### B. Progressive Compression Scale
Council size determines compression needs:
- **2 advisors**: No compression needed
- **4 advisors**: Light compression (~100 lines each)
- **8 advisors**: Moderate compression (~80 lines each)
- **12+ advisors**: Aggressive compression (~60 lines each)

### C. Quality Validation Criteria
Minimum thresholds for production:
- **Confrontational Tone**: Must challenge, not please
- **Specific Examples**: 3+ per response with real data
- **First-Person Voice**: Consistent "I" perspective
- **Uncomfortable Truths**: 1+ hard truth per interaction
- **Framework Application**: Clear methodology in advice

### D. Temperature Configuration Guide
Model-specific settings for optimal output:
- **Technical Advisors**: 0.7 (consistency over creativity)
- **Strategic Advisors**: 0.75 (balanced approach)
- **Creative Advisors**: 0.8-0.85 (maximum creativity)
- **NEVER**: Use 0.9+ (degrades quality dramatically)

### E. Initial Advisor Library
Core four for MVP launch:
1. **Alex Hormozi**: Growth, offers, value equations
2. **Naval Ravikant**: Philosophy, leverage, wealth creation
3. **Paul Graham**: Startups, essays, YC wisdom
4. **April Dunford**: Positioning, messaging, category design