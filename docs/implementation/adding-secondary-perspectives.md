# Adding Secondary Perspectives to Advisors

Secondary perspectives make advisors multi-dimensional and affect how they respond in ChatGPT conversations.

## What Are Secondary Perspectives?

These are cross-functional viewpoints that make an advisor more than just their primary expertise. For example:
- Cal Henderson isn't just technical - he's product-minded
- Bogusky isn't just creative - he's a business strategist
- Hormozi isn't just about growth - he cares about sustainable profit

## How Secondary Perspectives Are Used

The `secondary_perspectives` field is injected directly into the PI enhancement prompt as:
```
CRITICAL PERSPECTIVE: [your secondary_perspectives text]
```

This text guides Grok when generating the PI to include examples and behavioral patterns that reflect these perspectives.

## How to Add/Edit Secondary Perspectives

### Method 1: Database (Recommended)

Use Laravel Tinker to update directly:

```bash
php artisan tinker
```

```php
// Find the advisor
$advisor = App\Models\Advisor::where('key', 'henderson')->first();

// Update their secondary perspectives
$advisor->secondary_perspectives = "Cal is not just technical - he's deeply product-minded. He measures engineering success by user impact, not system elegance.";
$advisor->save();
```

## Formatting Options & Their Impact

### Option 1: Full Sentences (RECOMMENDED)
```
"Cal is not just technical - he's deeply product-minded. He measures engineering success by user impact, not system elegance."
```
**Impact**: Gives Grok complete context to understand the nuance. Results in PI with richer examples and more sophisticated behavioral patterns.

### Option 2: Comma-Delimited Terms
```
"product-minded, user-focused, ships fast, developer experience"
```
**Impact**: Works but less effective. Grok might miss the relationships between concepts. Results in more generic PI enhancements.

### Option 3: Just Keywords
```
"product engineering metrics"
```
**Impact**: Least effective. Too vague for Grok to generate meaningful behavioral changes in the PI.

## Why Full Sentences Work Better

When you write: 
```
"He measures engineering success by user impact, not system elegance"
```

Grok understands:
1. **The tradeoff** - user impact OVER system elegance
2. **The decision framework** - how Cal makes choices
3. **The contrarian stance** - going against typical engineering values

This results in PI that includes questions like:
- "What's the user impact of this technical decision?"
- "Are we optimizing for architectural purity or shipping value?"

Whereas keywords like "product-minded" might only result in generic additions like "considers product implications."

## Writing Effective Secondary Perspectives

### Good Examples:

✅ **Cal Henderson**: "Cal is not just technical - he's deeply product-minded. He measures engineering success by user impact, not system elegance."
- Shows the contrast (technical BUT ALSO product)
- Gives specific decision filters (user impact > system elegance)

✅ **Hormozi**: "Every decision filters through 'What's the leverage here?' He thinks in systems and repeatability, not one-off tactics."
- Provides a mental model ("What's the leverage?")
- Shows how he thinks differently

### Bad Examples:

❌ "Cal is a good engineer who likes products"
- Too vague
- Doesn't show HOW it affects his thinking

❌ "Hormozi knows about business"
- Doesn't add dimension
- Doesn't differentiate from primary expertise

## Impact on Advisor Behavior

Secondary perspectives affect the PI (Instructions) generation, which directly influences how the advisor responds in ChatGPT:

**Without secondary perspective:**
User: "Should we refactor this code?"
Cal: "What's the technical debt cost? How complex is the current system?"

**With product-minded perspective:**
User: "Should we refactor this code?"
Cal: "Will it help ship features faster? What's the user impact if we don't?"

## When to Add Secondary Perspectives

Add them when you notice an advisor is:
- Too one-dimensional in their responses
- Missing a key aspect of their real-world approach
- Not differentiating enough from others in their field

## Current Secondary Perspectives

| Advisor | Secondary Perspectives |
|---------|------------------------|
| Cal Henderson | Product-minded, measures by user impact, ships fast |
| Alex Bogusky | Business strategist, cultural reader, human behavior expert |
| Alex Hormozi | Offer architect, systems thinker, leverage seeker |
| Gary Halbert | Psychology expert, testing obsessed, data over debate |

## Character Limit & Best Practices

- **Recommended length**: 50-200 characters
- **Sweet spot**: 2-3 sentences that capture the essence
- **Too short** (<20 chars): Not enough context for meaningful impact
- **Too long** (>300 chars): May dilute focus, better to be concise and specific

## Testing Your Secondary Perspectives

After adding, ask yourself:
1. Does this add a dimension not covered by their primary expertise?
2. Does it include specific decision-making criteria?
3. Would this change how they answer questions?

## After Adding/Updating

After updating secondary perspectives, regenerate the advisor to apply changes:

```bash
php artisan advisor:generate [advisor-key]
```

The new perspective will be embedded in their PI during generation, affecting all future ChatGPT conversations.

## Technical Implementation

The secondary perspective is injected into the PI enhancement prompt at line 284 of `AdvisorGenerationService.php`:
```php
if (!empty($this->advisorData['secondary_perspectives'])) {
    $secondaryPerspectives = "CRITICAL PERSPECTIVE: " . $this->advisorData['secondary_perspectives'];
}
```

This ensures Grok considers these perspectives when generating behavioral examples and response patterns in the PI.
