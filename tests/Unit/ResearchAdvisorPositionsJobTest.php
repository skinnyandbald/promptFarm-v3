<?php

namespace Tests\Unit;

use App\Jobs\ResearchAdvisorPositionsJob;
use PHPUnit\Framework\TestCase;

class ResearchAdvisorPositionsJobTest extends TestCase
{
    protected function getJobInstance(): ResearchAdvisorPositionsJob
    {
        return new ResearchAdvisorPositionsJob('test_advisor', [], false);
    }
    
    public function test_validates_proper_position_format(): void
    {
        $job = $this->getJobInstance();
        $reflection = new \ReflectionClass($job);
        $validateMethod = $reflection->getMethod('validatePositionFormat');
        $validateMethod->setAccessible(true);
        
        $validPositions = <<<POSITIONS
POSITION 1: Direct Response
BELIEF: Emotion trumps reason in sales every time.
TRIGGER: Mainstream overvalues polished branding.

POSITION 2: Starvation Marketing
BELIEF: Hunger for results fuels unbeatable copywriting.
TRIGGER: Rejecting complacency in marketing.

POSITION 3: Market Research
BELIEF: Find the hidden, desperate buyer pain.
TRIGGER: Ignoring surface-level desires.

POSITION 4: Testing Mania
BELIEF: Test every idea until it bleeds.
TRIGGER: Avoiding untested, lazy strategies.

POSITION 5: Headline Power
BELIEF: 80% of success is the headline.
TRIGGER: Underestimating first impressions.

POSITION 6: Specific Claims
BELIEF: Numbers and proof sell over promises.
TRIGGER: Rejecting vague promises.

POSITION 7: Big Promises
BELIEF: Outrageous offers create unstoppable demand.
TRIGGER: Playing small, safe bets.

POSITION 8: Failure Worship
BELIEF: Fail fast to win big later.
TRIGGER: Fleeing from risk.
POSITIONS;
        
        $result = $validateMethod->invoke($job, $validPositions);
        $this->assertTrue($result);
    }
    
    public function test_rejects_invalid_position_format_with_mel_bug(): void
    {
        $job = $this->getJobInstance();
        $reflection = new \ReflectionClass($job);
        $validateMethod = $reflection->getMethod('validatePositionFormat');
        $validateMethod->setAccessible(true);
        
        $invalidPositions = <<<POSITIONS
POSITION 1: Direct Response
BELIEF: Emotion trumps reason in sales every time.
TRIGGER: Mainstream overvalues polished branding.

POSITION 2: Starvation Marketing
BELIEF: Hunger for results fuels unbeatable copywriting.
TRIGGER: Rejecting complacency in marketing.

mel 3: Market Research
BELIEF: Find the hidden, desperate buyer pain.
TRIGGER: Ignoring surface-level desires.

mel 4: Testing Mania
BELIEF: Test every idea until it bleeds.
TRIGGER: Avoiding untested, lazy strategies.

POSITION 5: Headline Power
BELIEF: 80% of success is the headline.
TRIGGER: Underestimating first impressions.
POSITIONS;
        
        $result = $validateMethod->invoke($job, $invalidPositions);
        $this->assertFalse($result);
    }
    
    public function test_rejects_insufficient_positions(): void
    {
        $job = $this->getJobInstance();
        $reflection = new \ReflectionClass($job);
        $validateMethod = $reflection->getMethod('validatePositionFormat');
        $validateMethod->setAccessible(true);
        
        $tooFewPositions = <<<POSITIONS
POSITION 1: Direct Response
BELIEF: Emotion trumps reason in sales every time.
TRIGGER: Mainstream overvalues polished branding.

POSITION 2: Starvation Marketing
BELIEF: Hunger for results fuels unbeatable copywriting.
TRIGGER: Rejecting complacency in marketing.
POSITIONS;
        
        $result = $validateMethod->invoke($job, $tooFewPositions);
        $this->assertFalse($result);
    }
}
