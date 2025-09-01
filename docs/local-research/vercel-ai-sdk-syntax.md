Querying perplexity using sonar-pro for: Vercel AI SDK streamText API parameters correct syntax maxTokens max_tokens temperature 2024 2025 with maxTokens: 21000
As of 2024–2025, the **Vercel AI SDK `streamText` API** expects parameters for output length and randomness to use the SDK’s *standardized settings*, which are slightly different from the raw OpenAI or other provider parameter names.

### Correct Parameter Names (Vercel AI SDK)

- **maxOutputTokens** — Use this for setting the maximum number of tokens to output.  
- **temperature** — Use this setting directly for output randomness.

**Example syntax** using Vercel AI SDK 5+:

```typescript
import { streamText } from 'ai';

const result = await streamText({
  model: 'openai/gpt-4o',
  prompt: 'Your question here.',
  maxOutputTokens: 512,
  temperature: 0.7
});
```
[maxOutputTokens and temperature][2][3]

### Common Mistakes

- **max_tokens** (snake_case) and **maxTokens** (camelCase):  
  These are *not standard* in the latest Vercel AI SDK API (2024–2025). Use **maxOutputTokens** instead.[2]
  
- If you pass parameters that the underlying provider does not support, the SDK may generate a warning you can check in the `.warnings` property.[2]

### Provider-Specific Caveats

- Some models or providers have recently **deprecated `max_tokens` and sometimes `temperature`** parameters.[4]
    - For some OpenAI models (2024/2025), the correct parameter may be `max_completion_tokens` or only support randomness internally (no external `temperature`).[4]

- If you use the SDK’s **default or global provider** configuration, just use `maxOutputTokens` and `temperature`. The SDK will translate this for the provider if that provider supports it.[3]

### If You Use a Custom API

If you are writing a **custom doStream** function or sending a custom POST body, *you must match the parameter names and semantics of your API endpoint.* For OpenAI-compatible APIs, use:

```json
{
  "max_tokens": 512,
  "temperature": 0.7
}
```
But in your SDK code (Vercel AI’s side), still use `maxOutputTokens`, so the SDK converts it for you (if using an official provider bridge). For raw custom endpoints, you can map as you wish.[1]

### Summary Table

| Vercel AI SDK Parameter (2024/2025) | Translates To (OpenAI API) | Notes                                                                      |
|-------------------------------------|----------------------------|----------------------------------------------------------------------------|
| **maxOutputTokens**                 | max_tokens                 | Use in SDK; SDK bridges to provider-specific name if supported             |
| **temperature**                     | temperature                | Use in SDK; only present for models/providers that support it              |
| maxTokens, max_tokens               | (deprecated/invalid)       | Not used by current SDK core                                              |

[2]: https://ai-sdk.dev/docs/ai-sdk-core/settings  
[3]: https://vercel.com/blog/ai-sdk-5  
[4]: https://community.openai.com/t/api-stopped-working-max-tokens-and-temperature-no-longer-allowed/1110863  
[1]: https://github.com/vercel/ai/discussions/4070

**Key advice:**  
- Always use `maxOutputTokens` and `temperature` for Vercel AI SDK in 2024/2025.  
- Do not use `maxTokens` or `max_tokens` directly with the SDK.  
- For custom APIs, use whichever parameter names your endpoint expects, but the SDK’s API expects the above.