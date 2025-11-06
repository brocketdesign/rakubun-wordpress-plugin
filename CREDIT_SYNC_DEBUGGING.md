# Payment Credits Sync Issue - Debugging Instructions

## Current Status

### Dashboard Shows ✅
- External Dashboard: **64 article, 10 image, 3 rewrite credits**
- MongoDB: Credits are correctly stored

### WordPress Shows ❌  
- Dashboard Display: **0 article, 2 image, 0 rewrite credits**
- Local DB: **1 article, 2 image, 0 rewrite credits**
- Problem: Using stale local database instead of external API data

## Root Cause Analysis

The WordPress plugin has **two separate credit systems**:

1. **Primary (External)**: MongoDB on dashboard - has correct values ✅
2. **Fallback (Local)**: WordPress database - has stale values ❌

The issue is that `get_user_credits()` is:
1. Checking if external API is connected ← Likely returning false
2. If not connected, falling back to local database
3. Showing you the stale local values

## Issues Fixed (Just Now)

### 1. Infinite Loop - FIXED ✅
**Problem**: Page reloaded infinitely after payment because URL still had `session_id` parameter
**Solution**: Changed to use `window.history.replaceState()` to clean URL BEFORE reload, so reload only happens once

### 2. Extensive Logging Added ✅
Added detailed logging throughout to identify the exact failure point:
- `Rakubun_AI_Credits_Manager::get_user_credits()` - logs each step
- `Rakubun_AI_External_API::get_user_credits()` - logs API request/response
- `purchase.php` - logs cache clearing and credit fetching

## Next Steps - How to Debug

### Step 1: Check WordPress Logs

```bash
tail -100 /var/www/html/wp-content/debug.log | grep "Rakubun"
```

**What to look for**:

**Expected sequence ✅**:
```
Rakubun_AI_Credits_Manager::get_user_credits() called for user 1
Rakubun: External API is connected
Rakubun: Cache miss, fetching from external API
Rakubun: Fetching credits for user 1 (didier@hatoltd.com)
Rakubun: API Response for /users/credits: {"success":true,"credits":{"article_credits":64,"image_credits":10,"rewrite_credits":3}}
Rakubun: Credits fetched successfully: {"article_credits":64,"image_credits":10,"rewrite_credits":3}
```

**If you see this instead ❌**:
```
Rakubun: External API is NOT connected
Rakubun: Falling back to local database
Rakubun: Local database returned: {"article_credits":1,"image_credits":2,"rewrite_credits":0}
```
→ **Problem**: External API connection isn't working

**If you see this ❌**:
```
Rakubun: API did not return credits data. Response: {"success":false,"error":"..."}
Rakubun: Falling back to local database
```
→ **Problem**: API is connected but returning an error

### Step 2: Make a Fresh Payment

1. Go to WordPress dashboard → Rakubun AI → Buy Credits
2. Select a package (e.g., add 100 article credits)
3. Complete Stripe payment with test card: `4242 4242 4242 4242`
4. Watch what happens

### Step 3: Check Logs Immediately After Payment

While on the success page (before refresh), check:

```bash
tail -200 /var/www/html/wp-content/debug.log | grep "Rakubun"
```

**Show me**:
- All lines with "Rakubun Payment Success"
- All lines with "Rakubun: Clearing transient"
- All lines with "Rakubun: External API is..."
- Any error lines starting with "Rakubun: API did not return"

### Step 4: Verify External API Connection

Test if WordPress can reach the dashboard:

```bash
# Test connection to dashboard
curl -X GET "https://app.rakubun.com/api/v1/health" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "X-Instance-ID: YOUR_INSTANCE_ID" \
  -v

# Test getting credits
curl -X GET "https://app.rakubun.com/api/v1/users/credits?user_id=1&user_email=didier@hatoltd.com" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "X-Instance-ID: YOUR_INSTANCE_ID" \
  -v
```

Replace:
- `YOUR_API_TOKEN` with your plugin's API token from WordPress settings
- `YOUR_INSTANCE_ID` with your plugin's instance ID from WordPress settings

### Step 5: Check WordPress Settings

1. Go to WordPress Admin → Rakubun AI → Settings
2. Look for:
   - ✓ API Token: Should be filled (looks like `sk_...`)
   - ✓ Instance ID: Should be filled (looks like `site_...`)
   - ✓ Connection Status: Should show "Connected"

If not connected:
- Try saving the settings again
- Verify the API token and instance ID are correct in the external dashboard

## Most Likely Issues

### Issue #1: Cache Not Clearing (Most Likely)
**Symptom**: Logs show "Rakubun: Cache hit" instead of "Cache miss"
**Solution**: 
```bash
# Manually clear all WordPress transients
wp transient delete --all
```

### Issue #2: External API Not Connected
**Symptom**: Logs show "Rakubun: External API is NOT connected"
**Solution**:
1. Go to WordPress Settings → Rakubun AI
2. Check Connection Status section
3. If not connected, click "Re-register Plugin"
4. Enter the API token from external dashboard (should match exactly)

### Issue #3: API Returning Error
**Symptom**: Logs show `"API did not return credits data. Response: {"success":false,...}"`
**Look at the error in the response**:
- `"error": "not_found"` → User not found in dashboard
- `"error": "authentication_failed"` → API token invalid
- `"error": "database_error"` → MongoDB issue on dashboard

## Temporary Workaround

If external API is still having issues, you can manually update the local database:

```bash
# SSH into WordPress server
wp db query "UPDATE wp_rakubun_user_credits SET article_credits = 64, image_credits = 10, rewrite_credits = 3 WHERE user_id = 1"
```

But this is NOT a permanent fix. The real issue needs to be resolved.

## Summary of Logging Added

### File 1: class-rakubun-ai-external-api.php
- Added logging at every step of `get_user_credits()`
- Shows API request parameters
- Shows full API response (success or error)
- Shows extracted credit values

### File 2: class-rakubun-ai-credits-manager.php
- Added logging for entire flow
- Shows if external API is connected
- Shows if cache hit or miss
- Shows which system provided the credits (external vs local)
- Shows sync results with local usage

### File 3: purchase.php
- Added logging when clearing transient cache
- Shows when fetching fresh credits
- Shows the result (what values were returned)

## What These Logs Tell Us

By analyzing the logs, we can identify:
1. ✓ Is external API connected?
2. ✓ Is cache being used?
3. ✓ Is API being called?
4. ✓ What is API returning?
5. ✓ Why is fallback used?
6. ✓ Where exactly is the failure?

## Next Action

1. **Now**: Make a test Stripe payment and capture the logs
2. **Share with me**:
   - Screenshot of WordPress dashboard showing 0 credits
   - Screenshot of External Dashboard showing 64 credits
   - Last 200 lines of `/var/www/html/wp-content/debug.log`
   - Results of the curl commands above

With this information, I can identify the exact issue and provide the right fix!

## Expected Fix Timeline

Once we know the issue:
- **If API not connected**: 5 minutes (re-register plugin)
- **If cache issue**: 2 minutes (clear cache)
- **If API error**: 15-30 minutes (fix API endpoint or authentication)
- **If database issue**: 10-15 minutes (sync data or reset)

---

## Questions to Answer

1. When you look at WordPress debug.log, what is the FIRST line with "Rakubun"? Copy it exactly.
2. After payment, does the page show a green success box?
3. Does the page reload automatically after 3 seconds, or stay on the same page?
4. Can you access https://app.rakubun.com/dashboard/external/ to see the external dashboard?
5. In WordPress Settings, does it say "✓ Connected" or "✗ Not Connected"?

---

## Don't Do This

❌ Don't manually edit the local database without investigating why external API isn't being used
❌ Don't clear the database transients before checking the logs
❌ Don't refresh the page before capturing logs (they'll be lost)
❌ Don't re-register the plugin without copying the API token first

---

## File References

- **Logs Path**: `/var/www/html/wp-content/debug.log`
- **External API Code**: `includes/class-rakubun-ai-external-api.php` line 77
- **Credits Manager Code**: `includes/class-rakubun-ai-credits-manager.php` line 27
- **Purchase Verification**: `admin/partials/purchase.php` lines 85-135
- **Debug Display**: `admin/partials/dashboard.php` lines 245-290

