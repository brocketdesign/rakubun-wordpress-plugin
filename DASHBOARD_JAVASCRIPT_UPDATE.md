# External Dashboard JavaScript Update

## Overview

The `externaldashboard/external-dashboard.js` file has been updated to support the new multi-provider configuration system (OpenAI and Novita AI) instead of just OpenAI-specific configuration.

## Changes Made

### 1. Event Binding Updates (bindEvents method)

**Removed:**
- `openaiConfigForm` submit handler
- `testConfig` click handler (for old OpenAI testing)

**Added:**
- `providerConfigForm` submit handler → `saveProviderConfig()`
- `providerSelect` change handler → `onProviderChanged()`
- `saveProviderConfig` click handler
- `testProviderConfig` click handler
- `testArticleGeneration` click handler
- `testImageGeneration` click handler
- `switchProviderBtn` click handler → `showSwitchProviderGuide()`

### 2. Configuration Loading (loadConfig method)

**Before:**
- Only loaded OpenAI-specific configuration from `/api/v1/admin/config/openai`

**After:**
- Now calls `loadProviderConfig()` instead
- Loads provider-agnostic configuration from `/api/v1/config/provider`
- Stripe configuration loading remains the same

### 3. New Methods Added

#### `loadProviderConfig()`
- Fetches current provider configuration from `/api/v1/config/provider`
- Populates form fields with provider details
- Updates provider info UI
- Loads provider-specific models
- Loads list of active providers for the site

#### `loadActiveProviders()`
- Fetches list of active providers from `/api/v1/config/providers`
- Displays provider information in a list group
- Shows base URL and models for each provider

#### `loadProviderModels(provider)`
- Fetches available article models from `/api/v1/config/article`
- Fetches available image models from `/api/v1/config/image`
- Dynamically populates dropdown options
- Handles provider-specific models (e.g., DeepSeek for Novita, GPT-4 for OpenAI)

#### `updateProviderInfo(provider)`
- Updates the provider information display
- Shows provider name, description, available models, and strengths
- Supports both OpenAI and Novita AI with customized information

#### `onProviderChanged(e)`
- Event handler for when provider dropdown changes
- Updates provider info display
- Loads provider-specific models
- Sets the appropriate base URL (e.g., `https://api.openai.com/v1` for OpenAI, `https://api.novita.ai/openai/v1` for Novita)

#### `saveProviderConfig(e)`
**Replaced:** Old `saveOpenAIConfig()` method

**New Features:**
- Validates that provider is selected
- Validates API key is provided
- Validates both article and image models are selected
- POSTs to `/api/v1/config/provider` with new provider data
- Handles provider switching seamlessly
- Reloads configuration after save

**Data Structure:**
```javascript
{
  provider: "openai" | "novita",
  api_key: "sk-...",
  model_article: "gpt-4" | "deepseek/deepseek-r1",
  model_image: "dall-e-3",
  max_tokens: 2000,
  temperature: 0.7
}
```

#### `testProviderConfig()`
- Tests if current provider configuration is valid
- Verifies API key and configuration
- Shows success/failure message

#### `testArticleGeneration()`
- Tests article generation with the selected provider
- Makes a request to `/api/v1/config/test/article`
- Returns which model was used
- Helps verify provider API credentials work

#### `testImageGeneration()`
- Tests image generation with the selected provider
- Makes a request to `/api/v1/config/test/image`
- Returns which model was used
- Validates image model configuration

#### `showSwitchProviderGuide()`
- Shows an informational guide for switching providers
- Includes before/after steps
- Lists testing procedures
- Explains rollback process
- References the main provider guide documentation

## API Endpoints Used

### Configuration Endpoints

- **GET** `/api/v1/config/provider` - Get current provider configuration
- **PUT** `/api/v1/config/provider` - Update provider configuration
- **GET** `/api/v1/config/providers` - List all providers
- **GET** `/api/v1/config/article` - Get provider-specific article models
- **GET** `/api/v1/config/image` - Get provider-specific image models
- **POST** `/api/v1/config/test/article` - Test article generation
- **POST** `/api/v1/config/test/image` - Test image generation

### Deprecated Endpoints

- **GET** `/api/v1/admin/config/openai` - Still works but no longer used

## Form Elements Expected in HTML

The following form elements must exist in `index.pug` for the JavaScript to work:

```html
<!-- Provider Selection -->
<select id="providerSelect">
  <option value="openai">OpenAI</option>
  <option value="novita">Novita AI</option>
</select>

<!-- Provider Information Display -->
<div id="providerInfo"></div>

<!-- Active Providers List -->
<div id="activeProvidersList"></div>

<!-- Provider Configuration Form -->
<form id="providerConfigForm">
  <input id="providerApiKey" type="password" />
  <input id="modelArticle" type="select" />
  <input id="modelImage" type="select" />
  <input id="maxTokens" type="number" />
  <input id="temperature" type="number" />
  <input id="baseUrl" type="text" readonly />
</form>

<!-- Action Buttons -->
<button id="saveProviderConfig">Save Provider Configuration</button>
<button id="testProviderConfig">Test Configuration</button>
<button id="testArticleGeneration">Test Article Generation</button>
<button id="testImageGeneration">Test Image Generation</button>
<button id="switchProviderBtn">Switch Provider Guidance</button>
```

## Usage Flow

1. **Page Load**: `loadConfig()` → `loadProviderConfig()` → displays current provider
2. **Provider Change**: User selects provider → `onProviderChanged()` → updates UI and loads models
3. **Configuration**: User enters API key and selects models
4. **Testing** (Optional):
   - Click "Test Configuration" to verify API key
   - Click "Test Article Generation" to test article model
   - Click "Test Image Generation" to test image model
5. **Save**: Click "Save Provider Configuration" → updates backend → reloads config
6. **Migration Help**: Click "Switch Provider Guidance" for step-by-step instructions

## Migration from Old Code

If you have old code using the deprecated `saveOpenAIConfig()` method:

**Old Code:**
```javascript
async saveOpenAIConfig(e) {
  const configData = {
    api_key: document.getElementById('apiKey').value,
    model_article: document.getElementById('modelArticle').value,
    model_image: document.getElementById('modelImage').value,
    max_tokens: parseInt(document.getElementById('maxTokens').value),
    temperature: parseFloat(document.getElementById('temperature').value)
  };
  // ... POST to /api/v1/admin/config/openai/global
}
```

**New Code:**
```javascript
async saveProviderConfig(e) {
  const provider = document.getElementById('providerSelect').value;
  const configData = {
    provider,
    api_key: document.getElementById('providerApiKey').value,
    model_article: document.getElementById('modelArticle').value,
    model_image: document.getElementById('modelImage').value,
    max_tokens: parseInt(document.getElementById('maxTokens').value),
    temperature: parseFloat(document.getElementById('temperature').value)
  };
  // ... PUT to /api/v1/config/provider
}
```

## Benefits of the New Implementation

✅ **Multi-Provider Support** - Easily switch between OpenAI and Novita AI
✅ **Dynamic Model Loading** - Models update based on selected provider
✅ **Validation** - Comprehensive form validation before saving
✅ **Testing** - Built-in testing for configuration and generation
✅ **User Guidance** - Help text and guides for provider switching
✅ **Extensible** - Easy to add new providers without code changes
✅ **Zero Downtime** - Switch providers without redeploying code

## References

- See `EXTERNAL_DASHBOARD_PROVIDER_GUIDE.md` for detailed provider configuration guide
- See `externaldashboard/ProviderConfig.js` for backend provider definitions
- See `externaldashboard/external.js` for API endpoint implementations
