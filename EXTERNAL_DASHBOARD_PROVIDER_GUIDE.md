# External Dashboard Provider Configuration Guide

## Overview

The External Dashboard API has been updated to support multiple AI providers (OpenAI and Novita AI) with dynamic provider switching. This guide explains how the provider system integrates with the external dashboard, why it's beneficial, and the configuration steps required.

---

## 1. Understanding the Provider Architecture

### What Changed

Previously, the external API endpoints were hardcoded to work only with OpenAI configurations. Now, the system:

- ✅ **Supports multiple providers** - OpenAI, Novita AI, and extensible for future providers
- ✅ **Dynamic provider switching** - Switch providers without API code changes
- ✅ **Provider-aware models** - Each provider has different available models
- ✅ **Backward compatible** - Old `/config/openai` endpoint still works with deprecation notice
- ✅ **Flexible configuration** - Site-specific or global provider settings

### Provider Models

Each provider offers different models optimized for different use cases:

#### OpenAI
```
Articles: GPT-4, GPT-4 Turbo, GPT-3.5 Turbo
Images: DALL-E 3, DALL-E 2
Base URL: https://api.openai.com/v1
```

#### Novita AI
```
Articles: DeepSeek R1, DeepSeek V2.5, DeepSeek Chat, Llama 2 7B, Mistral 7B
Images: DALL-E 3 (via OpenAI compatibility layer)
Base URL: https://api.novita.ai/openai/v1
```

---

## 2. Why Provider Switch Works Well with External Dashboard

### Architectural Benefits

#### A. **Centralized Configuration Management**
The external dashboard provides a single point for managing all provider configurations:
- Administrators can see all available providers
- Easy switching between providers at the site level
- API keys stored securely with encryption
- Configuration validated before saving

#### B. **Multi-Provider Support**
WordPress sites can now:
- Use **OpenAI for production** (high reliability, premium models)
- Use **Novita AI for cost-sensitive** operations (budget-friendly alternatives)
- Switch providers **without redeploying code**
- Maintain **separate configurations** per site

#### C. **API Consistency**
All endpoints work transparently with any provider:
```
GET  /api/v1/config/provider       → Works with any provider
GET  /api/v1/config/article        → Returns provider-specific models
GET  /api/v1/config/image          → Returns provider-specific models
PUT  /api/v1/config/provider       → Switch providers instantly
```

#### D. **Fallback Support**
Sites can:
- Configure global provider settings (used by all sites)
- Override with site-specific settings
- Gracefully handle missing configurations
- Maintain continuity during provider migrations

#### E. **Extensibility**
New providers can be added by:
1. Defining new provider in `ProviderConfig.PROVIDERS`
2. Adding validation rules to `ProviderConfig.validateConfig()`
3. No changes needed to API endpoints (they work automatically)

---

## 3. Updated API Endpoints

### New Endpoints

#### Get Current Provider Configuration
```
GET /api/v1/config/provider
```
Returns configuration for the currently active provider

**Response:**
```json
{
  "success": true,
  "provider": "novita",
  "provider_name": "Novita AI",
  "api_key": "nv-...",
  "model_article": "deepseek/deepseek-r1",
  "model_image": "dall-e-3",
  "max_tokens": 2000,
  "temperature": 0.7,
  "base_url": "https://api.novita.ai/openai/v1"
}
```

#### List All Available Providers
```
GET /api/v1/config/providers
```
Returns all available providers and active configurations for this site

**Response:**
```json
{
  "success": true,
  "all_providers": [
    {
      "id": "openai",
      "name": "OpenAI",
      "base_url": "https://api.openai.com/v1",
      "description": "State-of-the-art AI models...",
      "models": { ... }
    },
    {
      "id": "novita",
      "name": "Novita AI",
      "base_url": "https://api.novita.ai/openai/v1",
      "description": "Cost-effective alternative...",
      "models": { ... }
    }
  ],
  "active_providers": [ ... ]
}
```

#### Update Provider Configuration
```
PUT /api/v1/config/provider
```
Switch providers and update configuration

**Request:**
```json
{
  "provider": "novita",
  "api_key": "nv-your-api-key",
  "model_article": "deepseek/deepseek-r1",
  "model_image": "dall-e-3",
  "max_tokens": 2000,
  "temperature": 0.7
}
```

**Response:**
```json
{
  "success": true,
  "message": "Provider configuration updated successfully",
  "provider": "novita",
  "provider_name": "Novita AI"
}
```

### Updated Endpoints (Now Provider-Aware)

#### Get Article Configuration
```
GET /api/v1/config/article
```
Now returns provider-specific article models and settings

**Response:**
```json
{
  "success": true,
  "provider": "novita",
  "provider_name": "Novita AI",
  "config": {
    "api_key": "nv-...",
    "model": "deepseek/deepseek-r1",
    "temperature": 0.7,
    "max_tokens": 2000,
    "system_prompt": "You are a professional content writer...",
    "base_url": "https://api.novita.ai/openai/v1"
  },
  "models": [
    { "value": "deepseek/deepseek-r1", "label": "DeepSeek R1" },
    { "value": "deepseek/deepseek-v2.5", "label": "DeepSeek V2.5" },
    ...
  ]
}
```

#### Get Image Configuration
```
GET /api/v1/config/image
```
Now returns provider-specific image models

#### Get Rewrite Configuration
```
GET /api/v1/config/rewrite
```
Now returns provider-specific rewrite models

### Backward Compatibility

#### Deprecated: Get OpenAI Configuration
```
GET /api/v1/config/openai
```
⚠️ **Deprecated** - Still works but returns deprecation notice
Use `/api/v1/config/provider` instead

---

## 4. Configuration Steps

### Step 1: Update WordPress Plugin
Ensure your WordPress plugin version includes provider support:
- Check PROVIDER_SETUP.md for plugin updates
- Update the Rakubun AI Content Generator plugin to latest version

### Step 2: Configure Provider via Dashboard
In the external dashboard:
1. Go to **Configuration** tab
2. Select provider from dropdown (OpenAI or Novita AI)
3. Enter API key for selected provider
4. Configure models:
   - Article model (e.g., `deepseek/deepseek-r1` for Novita)
   - Image model (e.g., `dall-e-3`)
5. Set parameters:
   - max_tokens (default: 2000)
   - temperature (default: 0.7)
6. Click **Update Provider Configuration**

### Step 3: Test Configuration
```bash
# Test provider configuration
curl -X GET http://your-external-dashboard/api/v1/config/provider \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "X-Instance-ID: YOUR_INSTANCE_ID"

# Test article configuration
curl -X GET http://your-external-dashboard/api/v1/config/article \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "X-Instance-ID: YOUR_INSTANCE_ID"
```

### Step 4: Update WordPress Plugin Configuration
In WordPress admin panel (Rakubun AI → Settings):
1. Go to **APIプロバイダー設定** (API Provider Settings)
2. Select matching provider (OpenAI or Novita)
3. Ensure API key matches external dashboard
4. Save settings

---

## 5. Migration Guide

### Migrating from OpenAI to Novita AI

#### Without Downtime
1. **Backup current configuration**
   ```javascript
   // Save OpenAI API key
   const openaiConfig = await ProviderConfig.getConfigForSite(siteId, 'openai');
   ```

2. **Configure Novita in dashboard**
   - Get Novita API key from https://novita.ai/
   - Go to Configuration tab
   - Select "Novita AI" provider
   - Enter Novita API key
   - Select models (e.g., DeepSeek R1 for articles)
   - Save

3. **Test with real generation**
   - Generate a test article
   - Verify output quality
   - Check credit usage

4. **Switch WordPress plugin provider**
   - Update WordPress plugin settings to use Novita
   - Test generation from WordPress admin

5. **Verify user experience**
   - Check that all generations work
   - Monitor error logs
   - Verify credit deductions

6. **Keep OpenAI as fallback** (optional)
   - Maintain OpenAI configuration for emergency use
   - Switch back if needed: just update provider setting

---

## 6. Provider Comparison

| Feature | OpenAI | Novita AI |
|---------|--------|----------|
| **Article Models** | GPT-4, GPT-4 Turbo, GPT-3.5 | DeepSeek, Llama, Mistral |
| **Image Models** | DALL-E 3, DALL-E 2 | DALL-E 3 (via compatibility) |
| **Cost** | Premium | Budget-friendly |
| **Quality** | Highest | Very Good |
| **Speed** | Moderate | Fast |
| **Reliability** | Excellent | Excellent |
| **Model Variety** | Limited | High |
| **Switching Cost** | None | None |

---

## 7. Troubleshooting

### Issue: "Invalid provider: xxx"
**Solution:** Ensure provider is exactly `"openai"` or `"novita"` (lowercase)

### Issue: Configuration endpoint returns 404
**Solution:** 
- Verify API authentication is working
- Check if global configuration exists
- Try setting configuration first with PUT endpoint

### Issue: Models not available for selected provider
**Solution:**
- Verify you're using correct model name
- Check provider's available models in `/config/article` response
- Update model_article or model_image in configuration

### Issue: API requests failing after provider switch
**Solution:**
- Verify new API key is correct
- Test configuration with `/config/provider` endpoint
- Check logs for authentication errors
- Ensure WordPress plugin is using same provider

---

## 8. API Integration Examples

### Example 1: Switch Provider Programmatically
```javascript
// Switch to Novita AI
const response = await fetch('/api/v1/config/provider', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${apiToken}`,
    'X-Instance-ID': instanceId,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    provider: 'novita',
    api_key: 'nv-your-api-key',
    model_article: 'deepseek/deepseek-r1',
    model_image: 'dall-e-3'
  })
});

const result = await response.json();
console.log('Provider switched to:', result.provider_name);
```

### Example 2: Get Available Models
```javascript
// Get all providers and their models
const response = await fetch('/api/v1/config/providers', {
  headers: {
    'Authorization': `Bearer ${apiToken}`,
    'X-Instance-ID': instanceId
  }
});

const { all_providers } = await response.json();
all_providers.forEach(provider => {
  console.log(`${provider.name}:`);
  provider.models.article.forEach(model => {
    console.log(`  - ${model.label} (${model.value})`);
  });
});
```

### Example 3: Get Article Config Before Generation
```javascript
// Get provider config before generating article
const config = await fetch('/api/v1/config/article', {
  headers: {
    'Authorization': `Bearer ${apiToken}`,
    'X-Instance-ID': instanceId
  }
}).then(r => r.json());

console.log(`Using ${config.provider_name}`);
console.log(`Model: ${config.config.model}`);
console.log(`Base URL: ${config.config.base_url}`);

// Now make generation request with this config
const articleResponse = await makeArticleGenerationRequest(config);
```

---

## 9. What's Next

### Immediate Actions
1. ✅ **Update external.js** - Completed (you have the updated file)
2. ✅ **Update WordPress Plugin** - Should already support providers
3. 📋 **Test both providers** - OpenAI and Novita configurations
4. 📋 **Configure external dashboard** - Set default provider and API keys

### Short Term
- [ ] Add provider switching UI to external dashboard
- [ ] Implement provider health checks
- [ ] Add provider usage analytics by site
- [ ] Create provider-specific cost calculators

### Medium Term
- [ ] Support additional providers (Claude, LLaMA, etc.)
- [ ] Implement automatic provider failover
- [ ] Add A/B testing between providers
- [ ] Provider performance monitoring

### Long Term
- [ ] Machine learning for optimal provider selection
- [ ] Dynamic cost optimization
- [ ] Multi-provider load balancing
- [ ] Custom provider implementations

---

## 10. Validation Rules

The `ProviderConfig` class validates configurations based on provider:

### OpenAI Validation
```
✓ API key: Required, must be valid OpenAI key
✓ Model Article: Must be GPT-4, GPT-4 Turbo, or GPT-3.5 Turbo
✓ Model Image: Must be DALL-E 3 or DALL-E 2
✓ Max Tokens: 100-2000
✓ Temperature: 0-2
```

### Novita Validation
```
✓ API key: Required, must be valid Novita key
✓ Model Article: Must be valid Novita model (DeepSeek, Llama, Mistral)
✓ Model Image: Must be DALL-E 3
✓ Max Tokens: 100-8000
✓ Temperature: 0-2
```

---

## 11. Key Files Updated

| File | Changes |
|------|---------|
| `externaldashboard/external.js` | Added ProviderConfig import, updated 5 endpoints, added 3 new endpoints |
| `externaldashboard/ProviderConfig.js` | Already contains provider definitions (no changes needed) |
| `externaldashboard/OpenAIConfig.js` | Legacy support maintained (no changes needed) |
| `externaldashboard/NovitaConfig.js` | Extended ProviderConfig wrapper (no changes needed) |

---

## 12. Error Responses

### Provider Not Found
```json
{
  "success": false,
  "error": "Invalid provider: xyz. Must be one of: openai, novita"
}
```

### Configuration Validation Failed
```json
{
  "success": false,
  "error": "Configuration validation failed",
  "errors": [
    "API key is required",
    "Article model is required",
    "Temperature must be between 0 and 2"
  ]
}
```

### No Configuration Found
```json
{
  "success": false,
  "error": "No provider configuration found"
}
```

---

## 13. Security Considerations

- ✅ **API keys encrypted** - All provider API keys stored encrypted in database
- ✅ **Authentication required** - All config endpoints require plugin authentication
- ✅ **Rate limiting** - All external API endpoints rate-limited to 100 req/min
- ✅ **Key rotation** - Update provider config to rotate keys without downtime
- ✅ **Validation** - All input validated before storage

---

## Summary

The provider switch architecture provides:

1. **Flexibility** - Switch providers without code changes
2. **Scalability** - Support multiple providers simultaneously
3. **Cost Optimization** - Choose providers based on use case and budget
4. **Reliability** - Fallback support and configuration management
5. **Extensibility** - Easy to add new providers
6. **Backward Compatibility** - Existing integrations continue to work

The external dashboard now acts as a **unified configuration hub** for all AI providers, making multi-provider management seamless and efficient.

---

## Support & Documentation

For detailed setup instructions:
- See `PROVIDER_SETUP.md` - Provider configuration in WordPress
- See `README.md` - General plugin documentation
- See API endpoint code in `external.js` - Full endpoint documentation

