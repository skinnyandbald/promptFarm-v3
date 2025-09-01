# PromptFarm v3 - AI Advisor Generation System

<p align="center">
<a href="https://github.com/yourusername/promptFarm-v3/actions"><img src="https://github.com/yourusername/promptFarm-v3/workflows/Code%20Quality%20Checks/badge.svg" alt="Code Quality"></a>
<a href="https://coderabbit.ai"><img src="https://img.shields.io/badge/CodeRabbit-Enabled-brightgreen" alt="CodeRabbit"></a>
<a href="https://claude.ai"><img src="https://img.shields.io/badge/Claude%20AI-Integrated-blue" alt="Claude AI"></a>
<img src="https://img.shields.io/badge/Laravel-v12-red" alt="Laravel 12">
<img src="https://img.shields.io/badge/PHP-8.3-purple" alt="PHP 8.3">
</p>

## About PromptFarm v3

PromptFarm v3 is an advanced AI-powered advisor generation system built on Laravel 12. It creates sophisticated AI advisors with distinct personalities, expertise, and communication styles through a hybrid approach combining deterministic template processing with LLM enhancement.

### Key Features

- **Hybrid Generation System**: Combines deterministic template substitution with LLM-powered enhancement
- **Quality Validation**: Comprehensive scoring system ensuring high-quality advisor generation
- **AI Code Review**: Integrated CodeRabbit and Claude AI for automated PR reviews
- **Template Processing**: Advanced variable mapping and HTML comment replacement
- **Storage Management**: Dedicated storage disk for organized advisor files

### Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                   User Interface (CLI)                   │
└─────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────┐
│              Advisor Generation Service                  │
│  ┌─────────────────────────────────────────────────┐   │
│  │ PI Generation: Template + LLM Enhancement       │   │
│  │ PK Generation: LLM-Powered (o3-deep-research)   │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                            │
┌──────────────┬────────────┴────────────┬───────────────┐
│   Template   │    Quality Validation   │   Storage     │
│   Service    │       Service           │   (advisors)  │
└──────────────┴─────────────────────────┴───────────────┘
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Setup Instructions

### Prerequisites

- PHP 8.3 or higher
- Composer 2.x
- Node.js 20.x and npm
- SQLite or MySQL
- Git

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/yourusername/promptFarm-v3.git
cd promptFarm-v3
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install Node dependencies**
```bash
npm install
```

4. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Set up OpenAI API**
```bash
# Edit .env and add your OpenAI API key
OPENAI_API_KEY=your-api-key-here
PK_GENERATION_MODEL=o3-deep-research
PI_ENHANCEMENT_MODEL=gpt-4o-mini
```

6. **Set up database**
```bash
php artisan migrate
```

7. **Build assets**
```bash
npm run build
```

## Advisor Generation Usage

### Basic Generation

Generate an advisor using a predefined configuration:

```bash
php artisan advisor:generate bogusky
```

### With Quality Validation

Generate with detailed quality feedback:

```bash
php artisan advisor:generate bogusky --show-validation
```

### With Custom Quality Threshold

Enforce a minimum quality score:

```bash
php artisan advisor:generate bogusky --quality-threshold=85
```

## Quality Validation System

The system includes comprehensive quality validation for both PI (Project Instructions) and PK (Project Knowledge) components:

### PI Validation Criteria
- Required sections presence (30 points)
- No remaining placeholders (20 points)
- HTML comments processed (20 points)
- Content depth analysis (15 points)
- First-person voice usage (15 points)

### PK Validation Criteria
- Required sections presence (30 points)
- No remaining placeholders (20 points)
- No HTML comments (10 points)
- Content depth and specificity (25 points)
- Specific examples and cases (15 points)

### Quality Thresholds
- PI minimum score: 75%
- PK minimum score: 80%
- Overall pass criteria: Both components must meet thresholds

## AI Code Review Integration

### CodeRabbit Setup

CodeRabbit automatically reviews all pull requests. Configuration is in `.coderabbit.yaml`:

1. **Automatic Reviews**: All PRs are reviewed automatically
2. **Laravel-Specific Checks**: Enforces Laravel 12 best practices
3. **Security Scanning**: Detects potential vulnerabilities
4. **Quality Metrics**: Tracks code quality trends

### Claude AI Integration

Claude AI provides deep code review through GitHub Actions:

1. **Trigger Review**: Comment `@claude review this` on any PR
2. **Comprehensive Analysis**: Reviews for Laravel patterns, security, and performance
3. **Automated Fixes**: Claude can suggest and apply code fixes
4. **Context-Aware**: Understands the advisor generation system architecture

### Setting Up AI Reviews

1. **Add GitHub Secrets**:
   - `ANTHROPIC_API_KEY`: Your Claude API key
   - `CODERABBIT_API_KEY`: Your CodeRabbit API key (if using paid features)

2. **Enable GitHub Actions**: Ensure Actions are enabled in your repository settings

3. **Configure Branch Protection**: Optionally require AI review approval before merging

## Development Workflow

### 1. Create Feature Branch
```bash
git checkout -b feature/your-feature
```

### 2. Make Changes
Follow Laravel conventions and use provided services

### 3. Run Tests
```bash
php artisan test
```

### 4. Check Code Quality
```bash
vendor/bin/pint        # Format code
vendor/bin/phpstan     # Static analysis
```

### 5. Create Pull Request
AI reviews will run automatically

### 6. Address Review Comments
Both CodeRabbit and Claude will provide feedback

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Key Test Files
- `tests/Feature/AdvisorGenerationTest.php`: Integration tests for advisor generation
- `tests/Unit/AdvisorQualityServiceTest.php`: Quality validation logic tests
- `tests/Unit/TemplateServiceTest.php`: Template processing tests

## Troubleshooting

### Common Issues

**1. Template Not Found**
- Ensure templates exist in `resources/advisor-templates/`
- Check template naming convention: `meta_[pi|pk]_template_v1.md`

**2. Low Quality Scores**
- Review template for missing sections
- Check for unsubstituted variables (`{{variable}}`)
- Ensure HTML comments are processed
- Verify content meets minimum line requirements

**3. LLM Generation Failures**
- Verify OpenAI API key is set correctly
- Check API rate limits and quotas
- Review timeout settings in `.env`

**4. Storage Issues**
- Ensure `storage/app/advisors/` directory exists
- Check write permissions on storage directory
- Verify disk configuration in `config/filesystems.php`

### Debug Mode

Enable detailed logging:
```bash
# In .env
LOG_LEVEL=debug
```

View logs:
```bash
tail -f storage/logs/laravel.log
```

## Contributing

Thank you for considering contributing! Please follow these guidelines:

1. **Code Style**: Run `vendor/bin/pint` before committing
2. **Tests**: Add tests for new features
3. **Documentation**: Update README for significant changes
4. **AI Review**: Wait for AI review feedback on PRs
5. **Commit Messages**: Use conventional commit format

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
