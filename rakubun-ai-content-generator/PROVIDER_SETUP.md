# AI Provider Configuration Guide

This plugin now supports switching between multiple AI providers: **OpenAI** and **Novita AI**.

## Overview

The plugin has been updated to allow flexible switching between different AI service providers without code changes. Each provider offers different models and capabilities.

## Supported Providers

### OpenAI
- **Base URL:** `https://api.openai.com/v1`
- **Default Text Model:** `gpt-4`
- **Default Image Model:** `dall-e-3`
- **Features:** Chat completions, Image generation
- **Best for:** High-quality, state-of-the-art AI models

### Novita AI
- **Base URL:** `https://api.novita.ai/openai/v1`
- **Default Text Model:** `deepseek/deepseek-r1`
- **Default Image Model:** `dall-e-3`
- **Features:** Chat completions, Image generation (via OpenAI compatibility)
- **Best for:** Cost-effective alternatives and diverse model options

## Setting Up Providers

### Step 1: WordPress Admin Panel Configuration

1. Navigate to **Rakubun AI → Settings**
2. Scroll to the **APIプロバイダー設定** (API Provider Settings) section
3. Select your desired provider:
   - **OpenAI** - For GPT-4 and DALL-E-3
   - **Novita AI** - For DeepSeek and other models
4. Click **プロバイダーを更新** (Update Provider)

### Step 2: Configure API Keys

1. Go to **Settings → General Settings** (or the appropriate settings area)
2. Enter your API key for the selected provider:
   - For OpenAI: Get your key from https://platform.openai.com/api-keys
   - For Novita AI: Get your key from https://novita.ai/

## Using the Provider System in Code

### Getting Current Provider Information

```php
require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-provider.php';

// Get current provider name
$provider = Rakubun_AI_Provider::get_current_provider(); // Returns 'openai' or 'novita'

// Get current provider configuration
$config = Rakubun_AI_Provider::get_current_provider_config();

// Get provider display name
$display_name = Rakubun_AI_Provider::get_provider_display_name(); // Returns 'OpenAI' or 'Novita AI'

// Get provider base URL
$base_url = Rakubun_AI_Provider::get_provider_base_url(); // Returns the API base URL
```

### Checking Provider Capabilities

```php
// Check if provider supports a feature
if (Rakubun_AI_Provider::supports('images')) {
    // Provider supports image generation
}

if (Rakubun_AI_Provider::supports('chat')) {
    // Provider supports chat completions
}
```

### Getting Provider-Specific Models

```php
// Get default article model for current provider
$article_model = Rakubun_AI_Provider::get_default_model(null, 'article');

// Get default image model for current provider
$image_model = Rakubun_AI_Provider::get_default_model(null, 'image');

// Get model for specific provider
$openai_model = Rakubun_AI_Provider::get_default_model('openai', 'article'); // 'gpt-4'
$novita_model = Rakubun_AI_Provider::get_default_model('novita', 'article'); // 'deepseek/deepseek-r1'
```

### Changing Provider Programmatically

```php
// Switch to Novita AI
Rakubun_AI_Provider::set_provider('novita');

// Verify provider was set
$current = Rakubun_AI_Provider::get_current_provider(); // Returns 'novita'
```

### Validating Provider Names

```php
// Check if a provider is valid
if (Rakubun_AI_Provider::is_valid_provider('novita')) {
    // Provider exists
}
```

### Getting All Available Providers

```php
$all_providers = Rakubun_AI_Provider::get_providers();
// Returns array with all configured providers and their details
```

## How the Rakubun_AI_OpenAI Class Uses Providers

The main `Rakubun_AI_OpenAI` class automatically detects and uses the configured provider:

```php
$openai = new Rakubun_AI_OpenAI();

// The class will:
// 1. Read the current provider from WordPress options
// 2. Set the correct API base URL for that provider
// 3. Use the provider's default models for article/image generation

// Generate article (will use current provider's model)
$result = $openai->generate_article($prompt, $max_tokens, $language);

// Get provider info
$provider = $openai->get_provider(); // Returns 'openai' or 'novita'
$base_url = $openai->get_api_base(); // Returns provider's base URL
```

## Model Customization

While the plugin uses default models per provider, you can customize which models are used through WordPress options:

```php
// Get configuration
$config = get_option('rakubun_ai_openai_api_key'); // Gets the API key
$provider = get_option('rakubun_ai_api_provider'); // Gets the provider name

// The models can be extended in the external API configuration
// or modified through the Rakubun dashboard
```

## API Key Storage

API keys are stored separately from the provider setting:
- **Option Key:** `rakubun_ai_openai_api_key`
- **Provider Key:** `rakubun_ai_api_provider`

When changing providers, ensure your API key is valid for the selected provider.

## Configuration Caching

The plugin caches provider configuration for 1 hour to improve performance:
- **Cache Key:** `rakubun_ai_api_config`
- **TTL:** 1 hour (3600 seconds)

To force a cache refresh:
```php
delete_transient('rakubun_ai_api_config');
```

The cache is automatically cleared when you change providers in the admin panel.

## Error Handling

The API class provides clear error messages indicating the current provider:

```php
// Error messages will show which provider failed
// "Novita API Error: ..." or "OpenAI API Error: ..."
```

## Migration from Single Provider

If you previously had only OpenAI support:

1. **Default Behavior:** New installations default to OpenAI
2. **Existing Installations:** Continue using OpenAI (backward compatible)
3. **Switching:** Use the admin panel or `Rakubun_AI_Provider::set_provider('novita')` to switch

## Troubleshooting

### Provider Not Changing?
- Verify the nonce field in the form
- Check that `update_provider` button is clicked
- Clear WordPress transients if needed

### API Key Error After Switching?
- Ensure your API key is valid for the new provider
- Verify the correct key is stored in WordPress options
- Test the connection in the Settings page

### Model Not Available?
- Check if the selected provider supports the model
- Verify the model name matches the provider's API
- For Novita, ensure you're using valid model identifiers like `deepseek/deepseek-r1`

## Future Provider Support

To add a new provider:

1. Add configuration to `Rakubun_AI_Provider::$providers`:
```php
'my_provider' => array(
    'name' => 'My Provider',
    'base_url' => 'https://api.myprovider.com/v1',
    'description' => 'Description of models',
    'models' => array(
        'article' => 'model-name',
        'image' => 'image-model'
    ),
    'supports_images' => true,
    'supports_chat' => true
)
```

2. The `Rakubun_AI_OpenAI` class will automatically use it
3. The provider becomes available in the admin settings
