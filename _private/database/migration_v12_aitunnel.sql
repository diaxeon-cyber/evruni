-- Add AITUNNEL as an OpenAI-compatible provider.
-- Requires AITUNNEL_API_KEY and optionally AITUNNEL_BASE_URL in the environment.
INSERT IGNORE INTO ai_models(slug,name,type,provider,provider_model,credit_cost,enabled,description)
VALUES('aitunnel-auto','Evruni Auto','text','aitunnel','auto',1,1,'Evruni automatic model routing');
