# Deep Research Model Benchmarks

Purpose: Compare OpenAI deep models for PK generation quality during single‑advisor iteration.

Models to test
- o3-deep (default)
- o4-mini-deep-research

Procedure
```bash
# o4-mini-deep-research
sed -i '' 's/^ADVISOR_DEEP_MODEL_OPENAI=.*/ADVISOR_DEEP_MODEL_OPENAI=o4-mini-deep-research/' .env
php artisan advisor:generate AlexBogusky
mv storage/app/advisor-files/AlexBogusky_PK.md storage/app/advisor-files/AlexBogusky_PK.o4mini.md

# o3-deep
sed -i '' 's/^ADVISOR_DEEP_MODEL_OPENAI=.*/ADVISOR_DEEP_MODEL_OPENAI=o3-deep/' .env
php artisan advisor:generate AlexBogusky
mv storage/app/advisor-files/AlexBogusky_PK.md storage/app/advisor-files/AlexBogusky_PK.o3deep.md
```

Evaluation criteria
- Voice authenticity (first‑person, signature phrases)
- Evidence density (numbers, dates, named projects)
- Structure completeness (required sections present)
- Case study specificity (challenge → strategy → result → lesson)

Record results in `docs/model-benchmarks.md` and select the default for M1.
