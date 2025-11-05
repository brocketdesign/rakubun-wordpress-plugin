# Changelog

All notable changes to the Rakurabu AI Content Generator plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-05

### Added

#### Core Features
- AI article generation using OpenAI GPT-4
- AI image generation using OpenAI DALL-E 3
- Credit system with free starter credits (3 articles, 5 images)
- Stripe payment integration for purchasing additional credits
- WordPress admin dashboard with credit balance overview

#### Article Generation
- Customizable article prompts
- Optional custom titles
- Automatic WordPress draft post creation
- Copy to clipboard functionality
- Real-time generation status

#### Image Generation
- Text-to-image generation with DALL-E 3
- Three size options (Square, Portrait, Landscape)
- Automatic WordPress media library integration
- Image download capability
- Real-time generation status

#### User Interface
- Clean, modern admin dashboard
- Intuitive generation forms
- Credit balance display
- Purchase page with pricing cards
- Comprehensive settings page

#### Payment System
- Stripe.js integration for secure payments
- Server-side payment verification
- Customizable pricing structure
- Transaction logging
- Instant credit delivery

#### Database Management
- User credits table for balance tracking
- Transactions table for payment history
- Generated content table for audit trail
- Automatic table creation on activation

#### Security Features
- WordPress nonce verification for AJAX requests
- Input sanitization and validation
- Prepared SQL statements to prevent injection
- User capability checks
- Secure API key storage
- Payment intent validation
- Whitelist validation for critical parameters

#### Developer Features
- Modular class structure
- WordPress coding standards compliance
- Comprehensive code documentation
- Hook system for extensibility
- Error handling and logging

#### Documentation
- Comprehensive README with usage instructions
- Step-by-step installation guide
- Features documentation
- Security best practices
- Troubleshooting guide

### Security
- Fixed SQL injection vulnerability in credits manager (whitelist validation)
- Fixed payment verification security (proper Stripe integration)
- Fixed checkbox value handling for form submissions
- Secured Stripe API base URL (using constant)
- Improved image filename handling
- Added payment intent ID format validation

### Technical Details
- WordPress 5.0+ compatibility
- PHP 7.4+ requirement
- MySQL 5.6+ requirement
- OpenAI GPT-4 and DALL-E 3 integration
- Stripe API v3 integration
- GPL-2.0+ license

### Known Limitations
- Requires active OpenAI account with API credits
- Subject to OpenAI's content policy and rate limits
- Requires Stripe account for payment processing
- Payment processing fees apply (Stripe's standard rates)
- Generation times: 30-60 seconds for articles, 20-40 seconds for images

## [Unreleased]

### Planned Features
- Multiple AI model selection (GPT-3.5, GPT-4, GPT-4 Turbo)
- Bulk article generation
- Content templates
- Advanced analytics dashboard
- Team credits sharing
- Multi-language support
- Content scheduling
- SEO optimization features
- Custom model fine-tuning options
- Webhook integration for payment confirmations

### Under Consideration
- Integration with other AI providers
- Mobile app companion
- Content revision history
- AI content editing tools
- Collaboration features
- Export/import functionality
- White-label options

---

## Version History

- **1.0.0** (2024-11-05) - Initial release with core features

---

## Upgrade Notice

### 1.0.0
Initial release. No upgrade needed.

---

## Support

For bug reports and feature requests, please visit:
[GitHub Repository](https://github.com/brocketdesign/rakurabu-wordpress-plugin)

For security issues, please email security@example.com (do not file public issues).

---

## Credits

- Developed by Brocket Design
- Powered by OpenAI (GPT-4 and DALL-E 3)
- Payment processing by Stripe
- Built for WordPress
