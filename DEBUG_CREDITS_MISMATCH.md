# Debugging Credit Mismatch (0 vs 64)

## The Problem

- External Dashboard shows: **64 article credits** ✓
- WordPress shows: **0 article credits** ❌
- API returning: `{"article_credits":0,"image_credits":2,"rewrite_credits":0}`

This means the `/users/credits` endpoint is finding a **different user** than the one with 64 credits.

## Most Likely Cause: User_ID Mismatch

The WordPress user_id being sent might not match the user_id in the database.

Example:
- WordPress sends: `user_id=1, user_email=didier@hatoltd.com`
- Dashboard has user: `user_id=1, user_email=didier@hatoltd.com` with 64 credits
- But the query looks for `site_id + user_id` combo
- If it doesn't find it, `getOrCreateUser()` creates a NEW user with 0 credits

## Steps to Debug

### Step 1: Check Dashboard Logs

After you make a test payment, check the dashboard logs:

```bash
tail -100 /path/to/app/logs.txt | grep "\[Users/Credits\]"
```

**Look for lines like**:
```
[Users/Credits] Request received:
  site_id: 507f1f77bcf86cd799439011
  user_id: 1
  user_email: didier@hatoltd.com
  site_url: https://hattonihongo.com

[Users/Credits] Searching for user with site_id=507f1f77bcf86cd799439011 and user_id=1
[Users/Credits] Found existing user:
  user_id: 1
  user_email: didier@hatoltd.com
  article_credits: 64
```

**OR if NOT found**:
```
[Users/Credits] Searching for user with site_id=507f1f77bcf86cd799439011 and user_id=1
[Users/Credits] No existing user found. Checking all users for this site:
[Users/Credits] All users for this site:
  user_id: 2, user_email: didier@hatoltd.com, article_credits: 64
```

### Step 2: Check WordPress Logs

```bash
tail -100 /var/www/html/wp-content/debug.log | grep "Rakubun"
```

**Look for what user_id is being sent**:
```
Rakubun: Fetching credits for user 1 (didier@hatoltd.com)
```

### Step 3: Check Database Directly

If you have MongoDB access:

```javascript
// Check what users exist for your site
db.external_users.find({ site_id: ObjectId("YOUR_SITE_ID") }).pretty()

// Should show something like:
{
  _id: ObjectId(...),
  site_id: ObjectId("YOUR_SITE_ID"),
  user_id: 1,
  user_email: "didier@hatoltd.com",
  article_credits: 64,
  image_credits: 10,
  rewrite_credits: 3,
  created_at: ISODate(...)
}
```

### Step 4: Identify the Mismatch

After logging, answer these questions:

1. **What user_id is WordPress sending?** (should be in WordPress logs)
2. **What user_id exists in database?** (should be in dashboard logs or MongoDB query)
3. **Do they match?**

### Possible Scenarios

**Scenario A: User_ID is wrong**
```
WordPress sends: user_id=2 (wrong!)
Database has: user_id=1 (with 64 credits)
Result: Creates new user_id=2 with 0 credits ❌
Solution: Fix WordPress to send user_id=1
```

**Scenario B: Multiple users created**
```
Database has:
  user_id=1, user_email=didier@hatoltd.com, article_credits=0
  user_id=2, user_email=didier@hatoltd.com, article_credits=64
Result: PHP sends user_id=1, API returns 0 ❌
Solution: Delete duplicate user_id=1, keep only user_id=2 with credits
```

**Scenario C: Site_ID mismatch** (less likely)
```
WordPress registered one site
User created under different site_id
Result: Can't find user (no site_id match) ❌
Solution: Re-register plugin
```

## Quick Fix (If Scenario B)

If you have **two users** and need to keep the one with credits:

**Delete the user with 0 credits**:
```javascript
db.external_users.deleteOne({
  site_id: ObjectId("YOUR_SITE_ID"),
  user_id: 1,
  article_credits: 0
})
```

**Verify deletion**:
```javascript
db.external_users.find({ site_id: ObjectId("YOUR_SITE_ID") }).pretty()
```

**Then refresh WordPress dashboard** - should now show 64 credits

## What I Fixed

1. ✅ **Removed page redirect to wrong page**: Changed `rakubun-purchase` → `rakubun-ai-purchase`
   - Files: class-rakubun-ai-admin.php (2 places)
   - File: purchase.php (JavaScript redirect)

2. ✅ **Added extensive logging to /users/credits endpoint**:
   - Shows all parameters received
   - Shows what user_id is being searched
   - Shows all existing users for the site
   - Shows which user was returned

## Next Steps

1. **Run a test payment** to trigger the credit fetch
2. **Check both logs** (WordPress + Dashboard)
3. **Share the log output** showing:
   - What user_id WordPress sends
   - What users exist in dashboard database
   - Why they don't match
4. **I'll help you fix** the mismatch

---

## Example Log Output I Need

```
WordPress Debug Log:
Rakubun: Fetching credits for user [?] (email?)

Dashboard Application Log:
[Users/Credits] Request received:
  site_id: [?]
  user_id: [?]
  user_email: [?]
[Users/Credits] Searching for user with site_id=[?] and user_id=[?]
[Users/Credits] All users for this site:
[?]
```

Please share these exact lines after your next test payment!

