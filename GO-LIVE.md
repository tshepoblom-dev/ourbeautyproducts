# Go-live checklist

Work through this list top to bottom. Tick each box as you go.

---

## 1. Replace the remaining placeholders

**Already filled in:**
- Domain: `ourbeautyproducts.co.za`
- WhatsApp/cell: 082 644 8208
- Avon store: Rheeshop
- Justine store: Lizsbeautyshop
- Both sign-up links
- SMTP host: `mail.ourbeautyproducts.co.za`

- [ ] **Check the sign-up links.** Tap **Sign up with Avon** and **Sign up with Justine**. Both links contain "shopwith", so make sure each one opens the **representative sign-up** page and not a shopping page. The buttons promise sign-up.
- [ ] **Create the `info@ourbeautyproducts.co.za` mailbox** (cPanel → Email Accounts) if it doesn't exist yet. The page shows it as your public email address.

**Still to fill in:**

| Placeholder | Replace with | Where |
|---|---|---|
| `Our Beauty Products` | your trading name | index.html, site.webmanifest |
| `YOUR-FACEBOOK`, `YOUR-INSTAGRAM` | your social handles (or delete those two links if you don't use them) | index.html |
| `YOUR TOWN`, `PROVINCE` | where you're based | index.html (footer and JSON-LD) |
| `G-XXXXXXXXXX` | your GA4 measurement ID (optional; see section 6; the Google Ads tag is already installed) | index.html |

**In `mail-config.php`** (only the host is set):

| Setting | What to enter |
|---|---|
| `smtp_username` | the full mailbox address that sends the mail, e.g. `info@ourbeautyproducts.co.za` |
| `smtp_password` | that mailbox's password |
| `from_email` | the same mailbox as `smtp_username` |
| `to_email` | where enquiries should arrive (can be the same mailbox) |
| `smtp_port` / `smtp_secure` | leave as 587 / `tls`, or change to 465 / `ssl` if your host's mail settings say so (cPanel → Email Accounts → Connect Devices) |

- [ ] Search `index.html` and `site.webmanifest` for `YOUR` afterwards. Nothing should be left.

## 2. Files to add before launch

- [ ] **`og-image.jpg`**, 1200 × 630 px, under 200 KB. This is the image people see when the link is shared on WhatsApp or Facebook. Use your own photo or a simple branded card. Do **not** use Avon or Justine logos.
- [ ] **`/privacy` page.** The footer links to it, and POPIA expects one. It should cover:
  - who you are and how to contact you
  - what the Contact us form collects: name, cellphone, message, topic, and optionally email
  - what the **registration form** collects: full names as on ID, cellphone, email, brand, delivery address, a copy of your ID and a photo of the applicant holding their ID, and why (to confirm identity and process the registration enquiry)
  - that registration documents are kept in a private Google Drive folder and Google Sheet (Google processes them for you), who can open them, and how long you keep them
  - that the site uses Google Ads (and GA4 if you turn it on) and a strictly necessary session cookie on the registration form
  - why you collect it (to reply to the enquiry)
  - where it's stored (`contact-messages.csv` on your hosting, and your email inbox)
  - how long you keep it
  - that it isn't sold or shared
  - Google Analytics, if you turn it on
  - how people can ask you to delete their details
  - that they can complain to the Information Regulator

  Save it as `privacy/index.html` or `privacy.html` (then add a rewrite rule).

## 3. Upload

- [ ] Upload: `index.html`, `contact.php`, `.htaccess`, `robots.txt`, `sitemap.xml`, `site.webmanifest`, `favicon.svg`, `favicon-48.png`, `apple-touch-icon.png`, `icon-192.png`, `icon-512.png`, the `fonts/` folder, `mail-config.php` and the PHPMailer files.
- [ ] **Recommended:** put `PHPMailer.php`, `SMTP.php` and `Exception.php` in a folder called `phpmailer/`. contact.php finds them there or in the root, but `/phpmailer/` is tidier. Either way, `.htaccess` blocks them from the web.
- [ ] Also upload the registration files (see **section 5a**): `register.php`, `register-submit.php`, `thank-you.php`, `register.js`, `site.js`, `styles.css`, `.user.ini`, the `partials/` folder and your private `register-config.php`.
- [ ] You don't need to upload `CHANGELOG.md`, `GO-LIVE.md` or the `setup/` folder. If you do, `.htaccess` blocks them.
- [ ] Make sure PHP 8.0 or newer is selected in cPanel (contact.php uses PHP 8 syntax).
- [ ] Make sure the web folder is writable by PHP, so contact.php can create `contact-messages.csv`. This is the default on most shared hosts.

## 4. Test HTTPS and the redirects

- [ ] Install a free SSL certificate (cPanel → SSL/TLS Status → Run AutoSSL, or Let's Encrypt).
- [ ] Visit `http://ourbeautyproducts.co.za`, `http://www.ourbeautyproducts.co.za` and `https://www.ourbeautyproducts.co.za/index.html`. Each should land on `https://ourbeautyproducts.co.za/`.
- [ ] Visit `https://ourbeautyproducts.co.za/contact-messages.csv` and `https://ourbeautyproducts.co.za/mail-config.php`. Both must show **403 Forbidden**.
- [ ] After two weeks of HTTPS working, uncomment the `Strict-Transport-Security` line in `.htaccess`.

## 5. Test the contact form and SMTP

Do this on your phone, using mobile data rather than Wi-Fi.

**Contact us (contact.php → contact-messages.csv and SMTP)**
1. Check `mail-config.php`. Most SA hosts use port **465 with `ssl`** or port **587 with `tls`**. `from_email` should be a real mailbox on your own domain, or many inboxes will mark the mail as spam.
2. Tap **Contact us** and send a test message **without** an email address, then another **with** one.
3. Both should arrive at `to_email`. On the one with an email address, pressing Reply should go to the visitor.
4. If you see "We couldn't send your message", look in cPanel → **Errors** (or `error_log` in the site folder) for a line starting `Contact form mail error:`.
   - "Could not authenticate" means the wrong username or password.
   - "Connection refused" or a timeout means the wrong host, port or encryption setting.
5. In cPanel File Manager (or FTP), download `contact-messages.csv` and open it in Excel. Both test messages should be there with status **Received**, in South African time. Delete the test rows, or the whole file (contact.php recreates it).
6. Every valid message is saved **before** the email is sent. If the email fails, the visitor sees an error and the file gets a second row for that message with status **EMAIL FAILED**. Filter the Status column for that text to find messages you need to follow up. Check this file regularly, and download it now and then as a backup.

**Spam and limits**
- Each browser can send 5 contact messages an hour. Clearing cookies resets this.
- If spam still gets through, add a cPanel ModSecurity rule or Cloudflare (free) in front of the site.

## 5a. Registration form, Google Sheet/Drive and Google Ads

Full steps are in **`setup/REGISTRATION-SETUP.md`**. Checklist:

- [ ] Create the Google Sheet and the **private** Drive folder, deploy `setup/google-apps-script/Code.gs`, and copy the web app URL.
- [ ] Copy `register-config.example.php` to `register-config.php` and fill in `bridge_url`, `bridge_secret` and `google_ads_conversion_label`. Never commit that file.
- [ ] Test `https://ourbeautyproducts.co.za/register` with fake details: you land on `/thank-you`, a row appears in the Sheet, and 2 files appear in a Drive sub-folder.
- [ ] Open `https://ourbeautyproducts.co.za/register-config.php` and `.../partials/shell.php`: both must show **403 Forbidden**.
- [ ] Create the Google Ads conversion action and put its **label** in `register-config.php`. Confirm in Tag Assistant that the conversion fires **once**, only on the thank-you page.
- [ ] Check your host's PHP upload limits allow 8 MB files (`.user.ini` sets them; otherwise use cPanel → MultiPHP INI Editor: `upload_max_filesize` 8M, `post_max_size` 20M).
- [ ] Enable 2-step verification on the Google account that owns the Sheet and Drive folder.
- [ ] Publish your `/privacy` page (section 2) before sending traffic to the form.

## 6. GA4 (optional)

1. Create a GA4 property at analytics.google.com, set to South Africa and Rand. Copy the **G-…** measurement ID.
2. The Google tag is already installed (for Google Ads) on `index.html` and the PHP pages. **Do not add a second Google tag script.** Instead add one line after `gtag("config","AW-590577903");` in `index.html` and in `partials/shell.php`: `gtag("config","G-XXXXXXXXXX");` using your real ID.
3. In GA4 → Admin → Events, mark **generate_lead** as a **key event**. Optionally mark `whatsapp_click` and `join_click` too.
4. Check that it works in GA4 → Reports → Realtime while you tap a WhatsApp button.
5. Add Google Analytics to your privacy page.

Events the page sends:

| Event | When | Extra details |
|---|---|---|
| `generate_lead` | the contact form is sent, or a registration is confirmed saved (sent from `/thank-you`, once) | `form` (`contact_modal` or `registration`), `topic` or `brand` |
| `register_click` | any Register with us link | `brand`, `location` |
| `whatsapp_click` | any WhatsApp link | `location` (strip, float, footer, lead_success, modal_sent…) |
| `join_click` | any Sign up with Avon/Justine link | `brand`, `location` |
| `store_click` | any Shop link | `brand`, `location` |

## 7. Google Search Console

1. Go to search.google.com/search-console → **Add property → Domain**, and enter `ourbeautyproducts.co.za`.
2. Add the TXT record it gives you in your DNS (cPanel → Zone Editor, or at your domain registrar), then click Verify.
3. **Sitemaps** → submit `https://ourbeautyproducts.co.za/sitemap.xml`.
4. **URL Inspection** → paste your home page URL → **Request indexing**.
5. Run the home page through search.google.com/test/rich-results. It should find **Organization/LocalBusiness** and **FAQ** markup with no errors.
6. After 2–4 weeks, check **Performance** to see which searches show your page.

**What to expect in search results:** Since 2023, Google only shows FAQ rich results for well-known government and health sites, and it retired the sitelinks search box in 2024. So you should **not** expect FAQ drop-downs or other visual extras under your listing. The markup is still worth keeping:
- It helps Google understand who you are (business name, phone, area served).
- It can feed a knowledge panel.
- Other search engines and AI assistants still read it.

For a business like yours, **local results come from Google Business Profile**, not from page markup.

## 8. Google Business Profile

1. Go to business.google.com and add your business using your **real trading name**. Don't add keywords and don't put "Avon" or "Justine" in the name. Google suspends profiles that do, and it would suggest the brands run your business.
2. Choose a **service-area business** if you don't have a shop customers visit. Hide your street address and list the towns or areas you actually serve.
3. **Category:** pick the closest honest match from Google's list (search for "cosmetics" or "beauty" in the category box). Add WhatsApp, phone, website and hours.
4. **Description:** say plainly that you're an independent Avon representative and Justine consultant helping people join and shop.
5. Verify (Google may ask for a video or a postcard).
6. Add your own photos, such as product displays you've set up and your team (with their permission). Post updates when a new brochure comes out.
7. Ask happy customers and team members for honest reviews, and reply to every review. Don't offer rewards for reviews.
8. Keep the name, phone and town **exactly the same** on the website footer, the JSON-LD, Facebook and GBP.

## 9. Photos (for when you add them)

- **Format:** WebP, with AVIF where your editor supports it. Use JPEG only as a fallback. Export at 80% quality.
- **Size:** at most 1600 px wide for full-width images and 800 px for half-width. Keep each under about 150 KB, since visitors are on expensive data.
- **Markup:** always set `width` and `height`, so nothing jumps while loading:
  ```html
  <img src="/img/team-meetup.webp" width="800" height="600"
       alt="Our team sharing the new Avon brochure at a meet-up in YOUR TOWN"
       loading="lazy" decoding="async">
  ```
- **Lazy loading:** use `loading="lazy"` on every image **except** one that appears at the top of the page (in the hero). Give that one `fetchpriority="high"` instead, and don't lazy-load it.
- **Alt text:** say what's in the picture and why it matters, in one sentence. Don't stuff keywords, and use `alt=""` for purely decorative images.
- **Your own photos only.** Don't use Avon or Justine product shots or logos unless the brand's representative guidelines allow it.

## 10. Content plan for ranking (recommended, not built yet)

Each page should be genuinely useful on its own, with at least 400 words of real information, its own FAQ and its own photos. Each new page needs:
- a line in `sitemap.xml`
- a `BreadcrumbList` in its JSON-LD (Home › Page)
- links to it from the home page with descriptive text (for example "how to become an Avon representative")

| URL | Title | H1 | Meta description |
|---|---|---|---|
| `/join-avon/` | How to Become an Avon Representative in South Africa \| Our Beauty Products | How to become an Avon representative | Step-by-step help to sign up as an Avon representative in South Africa, from registration to your first order. Ask us anything on WhatsApp first. |
| `/join-justine/` | Become a Justine Consultant in South Africa \| Our Beauty Products | How to become a Justine consultant | Sign up as a Justine consultant with help from an independent team. We guide you through registration and your first campaign. |
| `/shop/` | Shop Avon and Justine Online in South Africa \| Our Beauty Products | Shop Avon and Justine online | Order Avon and Justine beauty products online from our stores, or WhatsApp us for help with an order. |
| `/faq/` | Avon and Justine Joining Questions Answered \| Our Beauty Products | Questions about selling Avon and Justine | Answers to common questions about becoming an Avon representative or Justine consultant in South Africa. |
| `/privacy/` | Privacy Policy \| Our Beauty Products | Privacy policy | How Our Beauty Products collects, uses and protects your personal information under POPIA. |
| `/avon-representative-[town]/` | Become an Avon Representative in [Town] \| Our Beauty Products | Become an Avon representative in [Town] | Join Avon in [Town] with a local team that helps you sign up, find customers and place your first order. |
| `/justine-consultant-[town]/` | Become a Justine Consultant in [Town] \| Our Beauty Products | Become a Justine consultant in [Town] | Sell Justine in [Town] with support from a local team. Sign up, get your brochure and start selling from home. |

**Town pages: only build them for towns where you or a team member really work.** Each needs genuinely local content: who the local contact is, where you meet, delivery or collection points, and photos from that town. Twenty near-identical pages with only the town name swapped are "doorway pages". Google can demote the whole site for them. Start with your home town and one or two others, then add more as your team grows.
