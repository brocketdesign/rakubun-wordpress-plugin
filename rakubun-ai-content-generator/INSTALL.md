# Installation Guide

## Quick Start

Follow these steps to install and configure the Rakubun AI Content Generator plugin:

### 1. Upload Plugin Files

**Method A: Via WordPress Admin Panel**
1. In WordPress admin, go to **Plugins → Add New**
2. Click **Upload Plugin**
3. Choose the `rakubun-ai-content-generator.zip` file
4. Click **Install Now**
5. Click **Activate Plugin**

**Method B: Via FTP/File Manager**
1. Upload the `rakubun-ai-content-generator` folder to `/wp-content/plugins/`
2. Go to **Plugins** in WordPress admin
3. Find "Rakubun AI Content Generator" and click **Activate**

### 2. Get OpenAI API Key

1. Visit [OpenAI Platform](https://platform.openai.com/)
2. Sign up or log in to your account
3. Navigate to [API Keys](https://platform.openai.com/api-keys)
4. Click **Create new secret key**
5. Give it a name (e.g., "WordPress Plugin")
6. Copy the API key (you won't be able to see it again!)

**Important Notes:**
- OpenAI requires a paid account with credits
- Check [OpenAI Pricing](https://openai.com/pricing) for costs:
  - GPT-4: Approximately $0.03 per 1K tokens (input) and $0.06 per 1K tokens (output)
  - DALL-E 3: $0.040 - $0.080 per image depending on quality and size
- Keep your API key secure and never share it publicly

### 3. Get Stripe API Keys

**For Testing (Recommended First)**
1. Visit [Stripe](https://stripe.com/) and create an account
2. Go to [Test Mode Dashboard](https://dashboard.stripe.com/test/dashboard)
3. Navigate to **Developers → API keys**
4. Copy the **Publishable key** (starts with `pk_test_`)
5. Click to reveal and copy the **Secret key** (starts with `sk_test_`)

**For Production**
1. Complete Stripe account verification
2. Switch to **Live mode** in Stripe Dashboard
3. Navigate to **Developers → API keys**
4. Copy the **Publishable key** (starts with `pk_live_`)
5. Click to reveal and copy the **Secret key** (starts with `sk_live_`)

**Test Cards for Development:**
- Success: `4242 4242 4242 4242`
- Requires Authentication: `4000 0025 0000 3155`
- Decline: `4000 0000 0000 9995`
- Use any future expiry date, any 3-digit CVC, and any postal code

### 4. Configure Plugin Settings

1. In WordPress admin, go to **AI Content → Settings**
2. Enter your **OpenAI API Key**
3. Enter your **Stripe Publishable Key**
4. Enter your **Stripe Secret Key**
5. Configure pricing (optional):
   - Article Package Price (default: $5.00)
   - Articles per Purchase (default: 10)
   - Image Package Price (default: $2.00)
   - Images per Purchase (default: 20)
6. Click **Save Settings**

### 5. Test the Plugin

1. Go to **AI Content → Dashboard** to see your free credits
2. Try generating an article:
   - Go to **AI Content → Generate Article**
   - Enter a prompt like: "Write a short article about the benefits of exercise"
   - Click **Generate Article**
3. Try generating an image:
   - Go to **AI Content → Generate Image**
   - Enter a prompt like: "A serene beach at sunset"
   - Click **Generate Image**

## Verification Checklist

- [ ] Plugin activated successfully
- [ ] OpenAI API key configured
- [ ] Stripe keys configured (test mode for development)
- [ ] Dashboard shows 3 article credits and 5 image credits
- [ ] Successfully generated a test article
- [ ] Successfully generated a test image
- [ ] Article was created as a draft post (if selected)
- [ ] Image was saved to media library (if selected)

## Common Setup Issues

### "OpenAI API key is not configured"
- Ensure you've entered the API key in Settings
- Check for extra spaces or characters
- Verify the key is from the correct OpenAI account

### "Stripe secret key is not configured"
- Enter both publishable and secret keys
- Make sure you're using matching keys (both test or both live)
- Check for extra spaces when copying

### Plugin activation fails
- Check PHP version (7.4+ required)
- Ensure WordPress is 5.0 or higher
- Check server error logs for details

### Database tables not created
- Check database user permissions
- Try deactivating and reactivating the plugin
- Verify MySQL version is 5.6 or higher

## Security Recommendations

1. **Keep API Keys Secure**
   - Never commit API keys to version control
   - Don't share keys in support tickets
   - Rotate keys periodically

2. **Use Test Mode First**
   - Always test with Stripe test keys first
   - Verify everything works before going live

3. **Monitor Usage**
   - Check OpenAI usage dashboard regularly
   - Set up billing alerts in OpenAI
   - Monitor Stripe transactions

4. **WordPress Security**
   - Keep WordPress, plugins, and themes updated
   - Use strong admin passwords
   - Limit admin access to trusted users only

## Going Live

When you're ready to go live:

1. **OpenAI**: No changes needed (same API key works for production)
2. **Stripe**: 
   - Complete Stripe account verification
   - Replace test keys with live keys in Settings
   - Test with a real small payment first
3. **Pricing**: Review and adjust credit package prices if needed
4. **Documentation**: Inform users about the credit system

## Support

If you encounter issues:
1. Check the [README](README.md) for troubleshooting tips
2. Review WordPress and PHP error logs
3. Verify API keys are correct and active
4. Check OpenAI and Stripe status pages for service issues

## Next Steps

After successful installation:
- Customize pricing to match your business model
- Create user documentation for your site
- Set up monitoring for API usage and costs
- Consider adding usage limits if needed

Enjoy generating AI content! 🚀
