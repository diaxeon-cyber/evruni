-- Evruni V3 independent provider migration.
-- Safe to run after V2 migration. It does not delete existing users, credits or chats.
SET @db=DATABASE();
SET @sql=IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='ai_models' AND column_name='provider_model')=0,
 'ALTER TABLE ai_models ADD COLUMN provider_model VARCHAR(160) NULL AFTER provider', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql=IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='ai_models' AND column_name='description')=0,
 'ALTER TABLE ai_models ADD COLUMN description VARCHAR(255) NULL AFTER credit_cost', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE ai_models SET provider='openai', provider_model='gpt-4o-mini', name='GPT-4o mini', description='Fast general-purpose chat model' WHERE slug='chat-default';
UPDATE ai_models SET provider='openai', provider_model='gpt-image-1', name='Evruni Image', description='Image generation' WHERE slug='image-default';
UPDATE ai_models SET provider='openai', provider_model='gpt-4o-mini-tts', name='Evruni Voice', description='Provider-ready voice surface' WHERE slug='speech-default';
UPDATE ai_models SET provider='openai', provider_model='gpt-4o-mini-transcribe', name='Evruni Transcribe', description='Speech to text' WHERE slug='transcribe-default';

INSERT IGNORE INTO ai_models(slug,name,type,provider,provider_model,credit_cost,enabled,description) VALUES
('openai-gpt-4o-mini','GPT-4o mini','text','openai','gpt-4o-mini',1,1,'OpenAI fast chat'),
('deepseek-v4-flash','DeepSeek V4 Flash','text','deepseek','deepseek-v4-flash',1,1,'DeepSeek V4 Flash'),
('deepseek-v4-pro','DeepSeek V4 Pro','text','deepseek','deepseek-v4-pro',3,1,'DeepSeek V4 Pro'),
('qwen-plus','Qwen Plus','text','qwen','qwen-plus',1,1,'Qwen general chat'),
('mistral-large-4','Mistral Large 4','text','mistral','mistral-large-4',2,1,'Mistral Large 4'),
('claude-sonnet-5-5','Claude Sonnet 5.5','text','anthropic','claude-sonnet-5-5',3,1,'Anthropic Sonnet 5.5'),
('gemini-3-8-flash','Gemini 3.8 Flash','text','google','gemini-3.8-flash',1,1,'Google Gemini 3.8 Flash');

-- Keep the local catalog as the source of truth. No external model catalog URL is used.
