# Rakurabu AI Content Generator - Features Documentation

## Overview

The Rakurabu AI Content Generator is a comprehensive WordPress plugin that leverages cutting-edge AI technology to help content creators generate high-quality articles and images efficiently.

## Core Features

### 1. AI Article Generation

**Technology**: OpenAI GPT-4

**Capabilities**:
- Generate well-structured, professional articles on any topic
- Customizable prompts for specific content needs
- Automatic draft post creation
- Content length optimization (up to 2000 tokens per article)
- Maintains tone and style consistency

**Use Cases**:
- Blog posts
- Product descriptions
- How-to guides
- News articles
- Educational content

**How It Works**:
1. User enters a detailed prompt describing the desired article
2. Optional: User can specify a custom title
3. Plugin sends request to GPT-4 via OpenAI API
4. Generated article is displayed in the interface
5. Optionally creates a WordPress draft post automatically
6. User can copy content or use the created post

### 2. AI Image Generation

**Technology**: OpenAI DALL-E 3

**Capabilities**:
- Generate unique, high-quality images from text descriptions
- Three size options:
  - Square: 1024x1024 (standard)
  - Portrait: 1024x1792 (vertical)
  - Landscape: 1792x1024 (horizontal)
- Automatic media library integration
- High-quality output suitable for thumbnails and featured images

**Use Cases**:
- Blog post featured images
- Article thumbnails
- Social media graphics
- Product mockups
- Illustration concepts

**How It Works**:
1. User provides a detailed image description
2. User selects desired image size
3. Plugin requests image from DALL-E 3
4. Generated image is displayed
5. Optionally saved to WordPress media library
6. Can be used as post thumbnail or in content

### 3. Credit System

**Free Credits**:
- 3 article generation credits per user
- 5 image generation credits per user
- Credits assigned automatically on first use

**Credit Management**:
- Real-time credit balance display
- Separate tracking for articles and images
- Credit deduction upon successful generation
- Purchase history tracking
- Transaction logging for audit purposes

**Database Storage**:
- User credits table for balance tracking
- Transactions table for payment history
- Generated content table for audit trail

### 4. Payment Integration

**Technology**: Stripe

**Features**:
- Secure payment processing
- PCI-compliant credit card handling
- Test mode for development
- Live mode for production
- Instant credit delivery
- Transaction verification

**Payment Flow**:
1. User selects credit package (articles or images)
2. Secure payment form powered by Stripe
3. Payment intent created on server
4. Card details handled by Stripe.js (never touch your server)
5. Payment confirmed
6. Credits automatically added to user account
7. Transaction logged for records

**Pricing Configuration**:
- Customizable package prices
- Adjustable credits per package
- Admin-controlled pricing structure
- Separate pricing for articles and images

### 5. WordPress Integration

**Admin Dashboard**:
- Clean, intuitive interface
- Credit balance overview
- Quick action buttons
- Usage statistics

**Post Creation**:
- Automatic draft post creation from articles
- Pre-filled title and content
- Ready for editing and publishing
- Maintains user authorship

**Media Library**:
- Generated images saved automatically
- Proper file naming
- Standard WordPress attachment handling
- Can be used anywhere in WordPress

**User Management**:
- Per-user credit tracking
- User capability checks
- Author attribution
- Activity logging

### 6. Security Features

**Input Validation**:
- All user inputs sanitized
- Type validation for parameters
- Whitelist validation for critical values
- XSS prevention

**Database Security**:
- Prepared SQL statements
- No dynamic column names without validation
- Proper escaping
- Secure data storage

**API Security**:
- Secure API key storage
- WordPress options API
- Keys not exposed to frontend
- Proper authentication headers

**Payment Security**:
- Stripe handles card data (PCI compliant)
- Server-side payment verification
- Payment intent ID validation
- Metadata verification
- User authentication required

**WordPress Security**:
- Nonce verification for all AJAX requests
- User capability checks
- Direct access prevention
- Proper hook usage

### 7. Error Handling

**User-Friendly Messages**:
- Clear error descriptions
- Actionable feedback
- No technical jargon for end users

**API Error Handling**:
- OpenAI API errors caught and reported
- Stripe payment failures handled gracefully
- Network timeout management
- Rate limit awareness

**Validation Errors**:
- Empty prompt detection
- Insufficient credits notification
- Invalid configuration alerts
- Missing API key warnings

## Technical Specifications

### System Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **HTTPS**: Recommended for production

### API Requirements

- **OpenAI**: Active account with API key and credits
- **Stripe**: Account with API keys (test or live)

### Database Tables

1. **wp_rakurabu_user_credits**
   - Stores user credit balances
   - Tracks articles and images separately
   - Updated on generation and purchase

2. **wp_rakurabu_transactions**
   - Logs all purchases
   - Stores Stripe payment IDs
   - Tracks amounts and credit types

3. **wp_rakurabu_generated_content**
   - Archives generated content
   - Links to WordPress posts
   - Stores prompts for reference

### Performance Considerations

**Generation Times**:
- Articles: 30-60 seconds (GPT-4)
- Images: 20-40 seconds (DALL-E 3)

**Server Requirements**:
- Sufficient PHP execution time (120+ seconds recommended)
- Adequate memory (128MB+ recommended)
- Stable internet connection

**API Rate Limits**:
- Respects OpenAI rate limits
- Queues requests if needed
- Error messages for rate limit hits

## User Roles and Permissions

### Content Creators (edit_posts capability)
- Generate articles
- Generate images
- Purchase credits
- View own credit balance
- Access generation interfaces

### Administrators (manage_options capability)
- All content creator permissions
- Configure API keys
- Set pricing
- View all transactions
- Access plugin settings

## Extensibility

### Hooks Available

The plugin uses WordPress standard hooks and can be extended:

- Activation hook for setup
- Deactivation hook for cleanup
- AJAX hooks for asynchronous operations
- Admin menu hooks for interface

### Filter Opportunities

While not explicitly defined, the plugin can be extended with:
- Credit amount filters
- Pricing filters
- Content filters
- Generation parameter filters

## Limitations

### OpenAI Limitations

- Subject to OpenAI's content policy
- Requires active OpenAI account with credits
- Rate limits apply
- Some prompts may be rejected

### Stripe Limitations

- Requires Stripe account
- Payment processing fees apply
- Country availability varies
- Some card types may not work

### WordPress Limitations

- Requires WordPress environment
- PHP version dependency
- MySQL required
- Admin access needed for setup

## Future Enhancement Possibilities

- Multiple AI model selection
- Bulk generation
- Content scheduling
- Team credits sharing
- Advanced analytics
- Content templates
- SEO optimization features
- Multi-language support
- Custom model fine-tuning
- API rate limit handling
- Webhook integration for payments

## Support and Maintenance

### Regular Updates

- Security patches
- WordPress compatibility
- API version updates
- Bug fixes

### Documentation

- README for general info
- INSTALL for setup
- FEATURES for capabilities
- Code comments for developers

### Community

- GitHub repository for issues
- Code is open source (GPL-2.0+)
- Community contributions welcome
