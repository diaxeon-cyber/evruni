# Evruni V3.1 — Independent AI Platform

Evruni V3.1 is the independent Evruni provider layer for Jino shared hosting.

It does **not** require an AI aggregator. Provider connections are configured directly:
- OpenAI
- Anthropic
- Google
- DeepSeek
- Qwen
- Mistral

The project keeps the Evruni dashboard/design and existing users, credits and V2 data.

## Jino deployment

1. Upload the project contents into `/domains/evruni.com/`.
2. Keep `_private/` on the same level as `index.php`; it is blocked from web access.
3. Keep `.htaccess` in the domain root.
4. Keep `.env` in the domain root and never publish it.
5. In phpMyAdmin, run `_private/database/migration_v3.sql` once after V2.
6. Put your direct provider key into `.env`.
7. Open `https://evruni.com/` and test AI Chat.

## First provider: OpenAI

Set:

OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_API_KEY=YOUR_OPENAI_API_KEY
AI_DEFAULT_MODEL=gpt-4o-mini

The database catalog maps Evruni model slugs to provider model IDs. `chat-default` is no longer sent to the provider as a model ID.

## Important

Do not paste API keys into chat, screenshots, Git repositories, or public files.
For production, use HTTPS, strong APP_KEY, database backups, and monitoring.

## API

OpenAI-compatible endpoints:
- `/api/v1/chat/completions`
- `/api/v1/models`

Authentication uses an Evruni API key from the dashboard, not a provider key.

## V3.1 scope

Included:
- provider adapters
- local model catalog
- model/provider status
- chat threads and history
- Evruni API keys with budgets
- usage accounting
- data masking foundation
- documents/knowledge-base foundation
- presets
- billing hooks
- OpenAI-compatible gateway
- Jino shared-hosting layout

Some advanced modalities remain provider-ready rather than fully implemented in the dashboard; they should be wired one by one instead of pretending a placeholder is production-ready.
