# External Dashboard - Mandatory Mode (No Local Fallback)

## What Changed

### ❌ REMOVED: Local Database Fallback
The plugin **NO LONGER** has a backup local database for credits. This was causing the stale data issue you saw.

### ✅ ENFORCED: External Dashboard Only
The plugin now **REQUIRES** connection to the external dashboard. If the connection fails, it shows an error instead of silently falling back to stale data.

### 🚨 ADDED: Clear Error Messages
When the external dashboard is not connected, users see specific error messages like:
- "Dashboard connection failed. Please verify your plugin settings and try again."
- "Failed to fetch credits from dashboard. Please try again or contact support."

## How It Works Now

### Before (with fallback) ❌
```
get_user_credits()
  ↓
Try external API
  ↓
If fails → Fall back to local database (STALE DATA SHOWN!)
```

### After (no fallback) ✅
```
get_user_credits()
  ↓
Try external API
  ↓
If fails → THROW ERROR (user sees error message)
```

## Error Handling by Page

### Dashboard Page
- **When**: User visits Rakubun AI → Dashboard
- **If error**: Shows error box at top: "Dashboard connection failed..."
- **User action**: Check settings, re-register plugin, or contact support

### Generate Article Page
- **When**: User visits Rakubun AI → 記事生成
- **If error**: Shows error box at top: "Dashboard connection failed..."
- **User action**: Cannot generate until fixed

### Generate Image Page
- **When**: User visits Rakubun AI → 画像生成
- **If error**: Shows error box at top: "Dashboard connection failed..."
- **User action**: Cannot generate until fixed

### Buy Credits Page
- **When**: User visits Rakubun AI → クレジット購入
- **If error**: Shows error box at top: "Dashboard connection failed..."
- **User action**: Cannot buy until fixed

### Auto Rewrite Page
- **When**: User visits Rakubun AI → 自動リライト
- **If error**: Shows error box at top: "Dashboard connection failed..."
- **User action**: Cannot use until fixed

## Files Modified

### 1. class-rakubun-ai-credits-manager.php
**Lines 27-65**: `get_user_credits()` method

**Changes**:
- ❌ Removed: Local database fallback
- ✅ Added: `throw new Exception()` if API not connected
- ✅ Added: `throw new Exception()` if API returns no data
- ✅ Added: Comprehensive error logging

**Now throws exception if**:
1. External API is not connected
2. External API doesn't return any credits
3. External API returns error response

### 2. class-rakubun-ai-admin.php
**Multiple methods**: Added error handling to all display pages

**Changes**:
- `display_generate_article_page()`: Added try-catch
- `display_generate_image_page()`: Added try-catch
- `display_purchase_page()`: Added try-catch
- `get_credits_safely()`: Changed to re-throw exceptions

**Result**: If credentials fetch fails, shows error message instead of displaying page

### 3. purchase.php (Checkout verification)
**Lines 100-115**: Fresh credits fetching after payment

**Changes**:
- Wrapped `get_user_credits()` in try-catch
- Catches API errors gracefully
- Still shows success message (credits already added on external dashboard)
- Logs error for debugging

## Testing Steps

### Test 1: Normal Operation ✅
1. Ensure plugin is connected to external dashboard
2. Go to any Rakubun AI page (Dashboard, 記事生成, etc.)
3. Should see credits displayed correctly
4. Should NOT see any error messages

### Test 2: Broken Connection ❌
1. Go to WordPress Settings → Rakubun AI
2. Change the API token to an invalid value
3. Save settings
4. Go to Rakubun AI → Dashboard
5. Should see error: "Dashboard connection failed. Please verify your plugin settings and try again."
6. Should NOT see any credit numbers

### Test 3: Payment with Error
1. Disconnect plugin from dashboard (use invalid API token)
2. Try to make a Stripe payment
3. Payment completes on Stripe side
4. Should see success message: "クレジットが正常に追加されました！"
5. Should show error: "Dashboard connection failed..."
6. (Credits ARE added on external dashboard, just can't display them)

### Test 4: Reconnect and Recovery
1. Fix the plugin settings (enter correct API token)
2. Save settings
3. Go to Dashboard
4. Should now see correct credits again
5. Error message should disappear

## Expected Behavior

### Scenario 1: Plugin Connected ✅
```
✓ Dashboard loads
✓ All pages load
✓ Credits display correctly
✓ Article/Image generation works
✓ Payment processing works
```

### Scenario 2: Plugin Disconnected ❌
```
✗ Dashboard shows error
✗ All pages show error
✗ Credits cannot be fetched
✗ Article/Image generation blocked
✗ Buy Credits page shows error
✓ Settings page works (for fixing connection)
```

### Scenario 3: Network Issue (Temporary)
```
✗ Brief error on page load
✓ Refresh page
✓ If network fixed, displays normally
✓ If network still broken, shows error again
```

## Troubleshooting

### "Dashboard connection failed" Error

**Possible Causes**:
1. **Plugin not registered** - API token missing or invalid
2. **Network issue** - WordPress can't reach dashboard
3. **Dashboard down** - External dashboard server not responding
4. **Wrong credentials** - API token doesn't match any registered plugin

**How to Fix**:
1. Go to WordPress Admin → Settings → Rakubun AI
2. Check "Connection Status" section
3. If not connected:
   - Verify API Token matches the one in external dashboard
   - Try clicking "Re-register Plugin"
   - Check network connectivity: `ping app.rakubun.com`
4. Save settings and test again

### Credits Show Incorrectly After Payment

**Since we removed local fallback**:
- Credits will ONLY update if external dashboard connection works
- If connection broken during payment, you'll see error (credits ARE added on external dashboard though)
- Fix connection and refresh page to see updated credits

### Page Won't Load at All

**If entire page shows error**:
- This is intentional! External API is required.
- Check your plugin connection settings
- Verify API token is correct
- Verify network connectivity to `https://app.rakubun.com`

## Migration Notes

### For Users Upgrading

⚠️ **Important**: After this update:
1. Plugin REQUIRES active external dashboard connection
2. No more local database credits (they're ignored)
3. If disconnected, you'll see error messages
4. This forces visibility of connection issues

### Benefits

✅ **No More Stale Data**: Always shows current credits from dashboard
✅ **Immediate Error Detection**: Connection problems are obvious
✅ **Simpler Logic**: No fallback confusion
✅ **Better Debugging**: Clear error messages pinpoint issues
✅ **Honest UI**: Shows reality (connected or not)

## Backward Compatibility

❌ **NOT backward compatible with local-only mode**

If you had:
- Old local database with credits
- Disconnected external dashboard

Then after update:
- Old local credits are ignored
- You'll see "Dashboard connection failed" error
- **Solution**: Re-register plugin with correct API token

## Log Examples

### Success Case (in wp-content/debug.log)
```
Rakubun_AI_Credits_Manager::get_user_credits() called for user 1
Rakubun: External API is connected
Rakubun: Cache miss, fetching from external API
Rakubun: External API returned: {"article_credits":64,"image_credits":10,"rewrite_credits":3}
Rakubun: Returning credits from external API: {"article_credits":64,"image_credits":10,"rewrite_credits":3}
```

### Error Case (in wp-content/debug.log)
```
Rakubun_AI_Credits_Manager::get_user_credits() called for user 1
Rakubun: CRITICAL - External API is NOT connected. Cannot fetch credits.
Exception thrown: Dashboard connection failed. Please verify your plugin settings and try again.
```

## Configuration

No configuration needed. The change is automatic.

All you need to do is ensure:
1. ✓ Plugin API token is set correctly
2. ✓ Plugin Instance ID is set correctly  
3. ✓ External dashboard is running
4. ✓ Network connectivity exists between WordPress and dashboard

## Rollback (If Needed)

If you need to go back to the old version with local fallback:
```bash
git checkout HEAD~1 includes/class-rakubun-ai-credits-manager.php
```

But we don't recommend this since the local data was stale.

## Summary

**The plugin is now honest about its state:**
- Connected → Works perfectly ✅
- Disconnected → Shows error immediately ❌

**No more surprises with stale credit data!**

