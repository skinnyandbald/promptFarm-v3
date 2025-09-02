# Coding Standards for promptFarm-v3

## Comments and Documentation

### CRITICAL: Comment Rules
**DO NOT:**
- Add comments stating what was removed (e.g., `// Removed old function` - just delete it completely)
- Reword existing comments unless they're factually wrong or misleading
- Add obvious comments (e.g., `// Set the name` above `$this->name = $name`)
- Leave placeholder/tombstone comments when deleting code (e.g., `// Feature X was here`)
- Comment on WHAT the code does when it's self-explanatory
- Add "TODO" or "FIXME" comments unless explicitly requested

**ONLY add/keep comments when:**
- Explaining WHY something non-obvious is done (not WHAT)
- Warning about critical side effects, gotchas, or security implications
- Documenting complex algorithms or business logic that isn't obvious
- Providing essential context that can't be understood from the code alone

### When Removing Code:
- Delete the code AND all its comments completely
- No breadcrumbs, no "was here" markers
- Clean deletion - as if it never existed
- Remove entire comment blocks if the code they describe is gone

### Examples:
```php
// ❌ BAD - Obvious comment
// Get all users
$users = User::all();

// ❌ BAD - Tombstone comment
// Removed tax calculation
// [deleted code]

// ✅ GOOD - Explains WHY
// Use bcrypt for Laravel 5.x compatibility (newer versions use argon2id)
$user->password = bcrypt($password);

// ✅ GOOD - Critical warning
// NEVER change this order - payment gateway requires status check first
```

**Core Principle: The best comment is often no comment. Write self-documenting code.**

## Laravel Conventions
- Use singular model names (User, Post, not Users, Posts)
- Use plural table names (users, posts)
- Use snake_case for database columns
- Use camelCase for model attributes
- Use PascalCase for class names

## Livewire Best Practices
- Keep components focused and single-purpose
- Use public properties for data binding
- Validate input in the component
- Use lifecycle hooks appropriately
- Emit events for component communication

## Tailwind CSS Guidelines
- Use utility classes over custom CSS
- Follow mobile-first responsive design
- Use consistent spacing scale
- Leverage Tailwind's color palette
- Use component classes for repeated patterns

## Filament Conventions
- Organize resources logically
- Use proper form validation
- Implement proper authorization
- Use custom pages when needed
- Follow Filament's naming conventions
