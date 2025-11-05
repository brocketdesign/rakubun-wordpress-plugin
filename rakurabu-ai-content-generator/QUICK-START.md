# Quick Start Guide

Get up and running with Rakurabu AI Content Generator in 5 minutes!

## Step 1: Install the Plugin (1 minute)

1. Upload `rakurabu-ai-content-generator` folder to `/wp-content/plugins/`
2. Go to **Plugins** in WordPress admin
3. Click **Activate** on "Rakurabu AI Content Generator"

## Step 2: Get API Keys (2 minutes)

### OpenAI API Key
1. Visit [OpenAI Platform](https://platform.openai.com/api-keys)
2. Click **Create new secret key**
3. Copy the key (save it somewhere safe!)

### Stripe Keys (for payments)
1. Visit [Stripe Dashboard](https://dashboard.stripe.com/test/apikeys)
2. Copy **Publishable key** (pk_test_...)
3. Copy **Secret key** (sk_test_...)

💡 **Tip**: Use test keys first to try it out!

## Step 3: Configure Plugin (1 minute)

1. Go to **AI Content → Settings** in WordPress
2. Paste your OpenAI API key
3. Paste your Stripe keys
4. Click **Save Settings**

## Step 4: Generate Your First Article (1 minute)

1. Go to **AI Content → Generate Article**
2. Enter a prompt like: "Write a blog post about morning routines"
3. Click **Generate Article**
4. Wait 30-60 seconds
5. Your article appears! 🎉

## Step 5: Generate Your First Image (30 seconds)

1. Go to **AI Content → Generate Image**
2. Enter a prompt like: "A peaceful mountain landscape at sunset"
3. Select image size
4. Click **Generate Image**
5. Wait 20-40 seconds
6. Your image appears! 🖼️

---

## Your Free Credits

✨ You start with:
- **3 free article credits**
- **5 free image credits**

Check your balance anytime on the **Dashboard**!

---

## Common First-Time Issues

### "OpenAI API key is not configured"
→ Make sure you pasted the key correctly in Settings (no extra spaces!)

### Generation takes too long or times out
→ This is normal for the first request! GPT-4 can take up to 60 seconds.

### "Insufficient credits"
→ You've used your free credits! Go to **Purchase Credits** to buy more.

### Payment not working
→ Make sure you're using test mode keys from Stripe during testing.

---

## Writing Better Prompts

### For Articles ✍️

**Good Prompt**:
```
Write a comprehensive guide about starting a vegetable garden for beginners. 
Include sections on choosing the right location, preparing soil, selecting 
vegetables, and maintenance tips. Make it friendly and encouraging.
```

**Not So Good**:
```
gardening
```

### For Images 🎨

**Good Prompt**:
```
A modern home office with a large window, wooden desk, comfortable chair, 
laptop, plants, and natural lighting. Minimalist style, warm tones.
```

**Not So Good**:
```
office
```

💡 **Tip**: Be specific! The more details you provide, the better the results.

---

## Next Steps

1. ✅ Try generating a few articles with different prompts
2. ✅ Test image generation with various descriptions
3. ✅ Create a real blog post using generated content
4. ✅ Adjust pricing in settings if needed
5. ✅ Switch to live Stripe keys when ready for production

---

## Need Help?

- 📖 Read the [full README](README.md)
- 🔧 Check [INSTALL.md](INSTALL.md) for detailed setup
- 🚀 See [FEATURES.md](FEATURES.md) for all capabilities
- 🐛 Found a bug? Report it on [GitHub](https://github.com/brocketdesign/rakurabu-wordpress-plugin)

---

## Quick Tips

1. **Save Money**: Start with specific, detailed prompts to get better results on first try
2. **Test First**: Always test in a development environment before going live
3. **Monitor Usage**: Check your OpenAI usage dashboard regularly
4. **Backup**: Generated content is logged in the database, but always backup important content
5. **Be Patient**: AI generation takes time - don't refresh the page!

---

Happy content creating! 🚀
