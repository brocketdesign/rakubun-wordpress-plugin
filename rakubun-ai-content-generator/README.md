# Rakubun AI Content Generator

A powerful WordPress plugin that enables users to generate high-quality articles and images using OpenAI's GPT-4 and DALL-E 3 models. The plugin includes Stripe payment integration for purchasing additional credits.

## Features

- **AI Article Generation**: Generate well-structured, engaging articles using GPT-4
- **AI Image Generation**: Create unique images using DALL-E 3
- **Credit System**: 
  - 3 free article generation credits per user
  - 5 free image generation credits per user
  - Purchase additional credits via Stripe
- **WordPress Integration**: 
  - Automatically create draft posts from generated articles
  - Save generated images to WordPress media library
- **Payment Processing**: Secure payment handling through Stripe
- **User-Friendly Interface**: Clean admin dashboard with easy-to-use generation forms

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- OpenAI API account with API key
- Stripe account with API keys

## Installation

1. Download the plugin folder `rakubun-ai-content-generator`
2. Upload it to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings (see Configuration section)

## Configuration

### Step 1: OpenAI API Setup

1. Create an account at [OpenAI Platform](https://platform.openai.com/)
2. Navigate to API keys section
3. Generate a new API key
4. Copy the API key

### Step 2: Stripe Setup

1. Create an account at [Stripe](https://stripe.com/)
2. For testing, use test mode keys from [Stripe Dashboard](https://dashboard.stripe.com/test/apikeys)
3. For production, use live mode keys
4. Copy both the Publishable Key and Secret Key

### Step 3: Plugin Configuration

1. In WordPress admin, go to **AI Content → Settings**
2. Paste your OpenAI API key
3. Paste your Stripe Publishable Key and Secret Key
4. Configure pricing (default: $5 for 10 articles, $2 for 20 images)
5. Save settings

## Usage

### Dashboard

Access the main dashboard at **AI Content → Dashboard** to:
- View your remaining credits
- Quick access to generation tools
- Overview of plugin features

### Generate Article

1. Go to **AI Content → Generate Article**
2. Enter an optional title for your article
3. Provide a detailed prompt describing the article you want
4. Choose whether to automatically create a draft post
5. Click "Generate Article"
6. The AI will generate your article (uses 1 credit)

**Example Prompt:**
```
Write a comprehensive article about the benefits of meditation for mental health. 
Include scientific research, practical tips for beginners, and address common 
misconceptions. Make it engaging and approximately 1000 words.
```

### Generate Image

1. Go to **AI Content → Generate Image**
2. Provide a detailed description of the image you want
3. Select the image size (Square, Portrait, or Landscape)
4. Choose whether to save to media library
5. Click "Generate Image"
6. The AI will generate your image (uses 1 credit)

**Example Prompt:**
```
A peaceful mountain landscape at sunset with a crystal clear lake in the 
foreground, pine trees on the sides, and snow-capped peaks in the background. 
Photorealistic style.
```

### Purchase Credits

1. Go to **AI Content → Purchase Credits**
2. Choose between Article Credits or Image Credits
3. Click "Purchase Now"
4. Complete the payment through Stripe
5. Credits are automatically added to your account

## Database Tables

The plugin creates three database tables:

1. **wp_rakubun_user_credits**: Stores user credit balances
2. **wp_rakubun_transactions**: Logs all payment transactions
3. **wp_rakubun_generated_content**: Records all generated content

## API Usage

### OpenAI Models Used

- **Articles**: GPT-4 (gpt-4)
- **Images**: DALL-E 3 (dall-e-3)

### Rate Limits

Be aware of OpenAI's rate limits and pricing:
- GPT-4: Token-based pricing
- DALL-E 3: Per-image pricing

Refer to [OpenAI Pricing](https://openai.com/pricing) for current rates.

## Security

The plugin implements several security measures:

- WordPress nonces for AJAX requests
- Input sanitization and validation
- Secure API key storage
- User capability checks
- SQL injection prevention

## Troubleshooting

### "OpenAI API key is not configured" error
- Ensure you've entered your API key in Settings
- Verify the API key is valid and active

### "Stripe secret key is not configured" error
- Enter both Stripe Publishable and Secret keys in Settings
- Ensure you're using the correct mode (test/live)

### Image generation fails
- Check that your prompt is detailed and specific
- Ensure your OpenAI account has credits
- DALL-E 3 may reject prompts that violate content policy

### Article generation is slow
- GPT-4 generation can take 30-60 seconds
- Ensure your server timeout settings allow sufficient time
- Check your internet connection

## Support

For issues and feature requests, please visit:
[GitHub Repository](https://github.com/brocketdesign/rakubun-wordpress-plugin)

## License

This plugin is licensed under the GPL-2.0+ license.

## Credits

Developed by Brocket Design

Powered by:
- OpenAI GPT-4 and DALL-E 3
- Stripe Payment Processing

## Changelog

### Version 1.0.0
- Initial release
- GPT-4 article generation
- DALL-E 3 image generation
- Stripe payment integration
- Credit system with free credits
- WordPress post and media library integration
