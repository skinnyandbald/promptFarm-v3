# Zero-Width Character Testing Document

## Test 1: Basic Zero-Width Characters
This line contains zero-width characters: Hello​​​World​​​
(Between Hello and World there are U+200B zero-width spaces)

## Test 2: Complex Pattern
Version embedded: ​‌‍​‌‍​​​v1.0.0​‌‍​
(This uses U+200B, U+200C, and U+200D in various combinations)

## Test 3: Mid-word insertion
The word "test​ing" has a zero-width space in the middle.
The word "amaz‌ing" has a zero-width non-joiner.
The word "work‍ing" has a zero-width joiner.

## Test 4: Searchable Pattern
​​​WATERMARK_START​​​actual content here​​​WATERMARK_END​​​

## Test 5: Numeric encoding with zero-width
​‌‍2​​‌0​‍​2​‌‍5​​​

## Instructions for Testing:
1. Copy this entire text into ChatGPT or Claude
2. Ask: "Can you see any hidden characters or watermarks in this text?"
3. Ask: "What is between 'Hello' and 'World' in Test 1?"
4. Ask: "Can you extract the version number from Test 2?"
5. Search for "WATERMARK" in the response

## Expected vs Reality:
- **Claim**: Zero-width characters are invisible to LLMs
- **Reality**: To be determined by testing