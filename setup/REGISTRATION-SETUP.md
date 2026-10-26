# Registration form: one-time setup

This connects the registration form (`/register`) to **your** Google account, so each submission becomes a row in a Google Sheet and the ID photos go into a **private** Google Drive folder. About 15 minutes. No coding.

Do **not** upload the `setup/` folder to your website. It is for you only. (`.htaccess` blocks it from the web anyway.)

## How it works

```
Visitor's phone ──> /register  (form on your website)
                       │  photos are shrunk on the phone, then sent over HTTPS
                       ▼
              register-submit.php  (your web host; checks everything again, cleans the photos)
                       │  sends to Google with a secret key that only your host and your script know
                       ▼
        Google Apps Script (runs as YOU, in your Google account)
              ├─ Google Sheet "Registrations": one row per applicant
              └─ Google Drive private folder: one sub-folder per applicant with the two ID files
                       ▲
        only after Google confirms, the visitor goes to /thank-you, and only there the Ads conversion fires
```

Why an Apps Script and not a Google "service account"? A service account cannot own files in a normal Google Drive (uploads fail with a storage-quota error). A script running as you stores files in your own Drive, with no keys to look after.

---

## Part 1: Google Sheet and Drive folder

1. Go to **sheets.google.com** (signed in as the Google account that should own the data) and create a blank spreadsheet called **Our Beauty Products registrations**.
   - Copy its **ID** from the address bar: `https://docs.google.com/spreadsheets/d/`**`THIS_PART`**`/edit`.
   - You don't need to add headings: the script creates the **Registrations** tab and headings on the first submission.
2. Go to **drive.google.com** and create a folder called **Registrations (private)**.JH
   - Open it and copy its **ID** from the address bar: `https://drive.google.com/drive/folders/`**`THIS_PART`**.
   - **Do not share this folder or the Sheet with anyone** unless you intend them to see ID documents. Never use "Anyone with the link".

## Part 2: The script

1. Go to **script.google.com** → **New project**. Name it **Registration bridge**.
2. Delete the sample code and paste in the whole of `setup/google-apps-script/Code.gs`. Save.
3. **Project Settings** (cog icon) → **Script properties** → **Add script property**, three times:

   | Property | Value |
   |---|---|
   | `BRIDGE_SECRET` | a long random password, 40+ characters (letters and numbers). Make it up or use a password manager. **Keep a copy**, you need it in Part 3. |
   | `SHEET_ID` | the Sheet ID from Part 1 |
   | `FOLDER_ID` | the Drive folder ID from Part 1 |

4. **Deploy** → **New deployment** → cog icon → **Web app**:
   - Description: `v1`
   - **Execute as: Me**
   - **Who has access: Anyone** (the script refuses anything without your secret; this setting only lets your website reach it)
   - **Deploy**, then **Authorize access** and approve the permissions (Drive and Sheets). Google shows an "unverified app" warning because *you* wrote it. Click **Advanced** → **Go to Registration bridge (unsafe)**; that's normal for your own script.
5. Copy the **Web app URL** (ends in `/exec`).
6. Test it: paste the URL into a browser. You should see `{"ok":true,"service":"obp-registration-bridge"}`. Nothing else is shown.

> When you change `Code.gs` later: **Deploy → Manage deployments → pencil → Version: New version → Deploy**. The URL stays the same.

## Part 3: Your website

1. In the site folder, copy `register-config.example.php` to **`register-config.php`**.
2. Fill in:
   - `bridge_url`: the Web app URL from Part 2
   - `bridge_secret`: exactly the same value as `BRIDGE_SECRET`
   - `google_ads_conversion_label`: see Part 4
3. Upload to your host: `register.php`, `register-submit.php`, `thank-you.php`, `register.js`, `site.js`, `styles.css`, `.user.ini`, the `partials/` folder, `register-config.php` and the updated `index.html`, `.htaccess`, `robots.txt`, `sitemap.xml`.
4. `register-config.php` holds your secret. It is blocked from the web by `.htaccess` and listed in `.gitignore`. **Never put it in Git, email it, or paste it into chat.**
5. If you use Git: commit `register-config.example.php` only.

## Part 4: Google Ads conversion label

The Google tag (`AW-590577903`) is already on every page. To count registrations as conversions you also need the **conversion label**:

1. Google Ads → **Goals** → **Conversions** → **+ New conversion action** → **Website**.
2. Category **Submit lead form** (or **Sign-up**). Value: your choice. Count: **One** (one conversion per registration).
3. Choose **Set up manually** / **Use Google tag**, then **Event snippet**. You'll see something like  
   `gtag('event', 'conversion', {'send_to': 'AW-590577903/`**`AbCdEfGhIjK`**`'});`
4. Copy **only** the part after the `/` into `google_ads_conversion_label` in `register-config.php`.
5. Do **not** paste Google's snippet into any page. The site already fires the event, once, from `/thank-you`, after a successful registration. It also sends `transaction_id` (the registration reference), so Google ignores duplicates.
6. Verify with the **Tag Assistant** Chrome extension (or Google Ads → Conversions → status "Recording conversions" after a real test).

## Part 5: Test it (with fake details first)

1. Open `https://ourbeautyproducts.co.za/register` on your phone.
2. Submit with fake details and two test photos (not real ID).
3. You should land on the **Thank you** page with a reference like `OBP-20261024-C52DA`.
4. Check: the **Registrations** tab has a new row, and the Drive folder has a new sub-folder containing 2 files.
5. Refresh the thank-you page: no second conversion is counted. Visit `/thank-you` directly in a new private window: it should send you back to the form.
6. Delete the test row and the test folder.

If you see "Registrations are temporarily unavailable", `bridge_url` or `bridge_secret` isn't set. If you see "We could not save your registration", the script isn't reachable or the secret doesn't match: check Part 2 step 3 and the Apps Script **Executions** page.

---

## Privacy and security notes

- Files are stored only in your private Drive folder. Links in the Sheet open only for you (anyone else gets "Request access"). The applicant never sees any Drive link.
- The website never stores the ID files: they pass through memory and are sent straight to Drive. Photos are re-encoded (this removes GPS and camera data) before they are sent.
- The Sheet and Drive hold personal information and ID documents. Protect that Google account with **2-step verification**, and share the Sheet and folder only with people who need them.
- Add to your privacy policy: what you collect (details, ID copy, photo holding ID), why, that it is kept in Google Drive/Sheets (Google processes it for you), how long you keep it, how to ask for deletion, and that Google Ads/Analytics cookies are used. The form says people can ask for deletion, so be ready to do it: delete their Sheet row and their Drive sub-folder.
- If you ever suspect the secret leaked: change `BRIDGE_SECRET` in Script properties **and** in `register-config.php`.
