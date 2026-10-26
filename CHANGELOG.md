# Changelog

## Registration enquiry form, Google Sheet/Drive storage and Google Ads tracking (23 October 2026)

**New pages and files:** `register.php` (the form, at `/register`), `register-submit.php` (server side), `thank-you.php` (at `/thank-you`), `register.js`, `partials/` (shared page shell, config and session code), `register-config.example.php`, `mail-config.example.php`, `.gitignore`, `.user.ini`, `setup/REGISTRATION-SETUP.md`, `setup/google-apps-script/Code.gs`.
**Changed:** `index.html` (Google tag, "Register with us" buttons and dropdown entries, CSS and JS moved out), `styles.css` and `site.js` (the home page's CSS and JS, now shared; the home page looks and behaves the same), `.htaccess`, `robots.txt`, `sitemap.xml`, `GO-LIVE.md`.

- **Form:** full names as on ID, contact number, email, brand (Avon/Justine), province, town, suburb, street, postal code, a copy of ID (JPG/PNG/WebP/PDF) and a photo of the applicant holding it (JPG/PNG/WebP). Mobile-first, labelled, with inline errors and an error summary.
- **Consent and disclosure:** a plain explanation of why documents are collected sits above the form. The consent box is required (checked in the browser and again on the server). The consent version and time are recorded with each registration.
- **Official requirements:** the wording makes no claim about what Avon or Justine require. I could not verify a current official ID checklist for South Africa, so the page says they set their own requirements and that you will confirm what applies.
- **Storage:** each submission goes to a Google Apps Script you deploy in your own Google account: a row in your Sheet and the two files in a private sub-folder of your private Drive folder. The site stores no ID files. Nothing is public and applicants never see a Drive link.
- **Uploads:** real file type checked from the content (not the name), size limits, readable-image check, minimum size, photos re-encoded as JPEG (removes GPS/camera data) and shrunk on the phone before upload.
- **Abuse protection:** one-time form token, same-origin check, hidden spam field, minimum fill time, per-session and per-IP limits (strict on saves).
- **Duplicates:** the submit button locks while sending; each form load has a one-time token, so a repeat or double submit never creates a second row; a person applying again in a new session is saved and flagged "Possible duplicate" in the Sheet.
- **Failures:** if Google can't save, the visitor sees an error, nothing is recorded, no conversion fires, the form keeps their data and they can retry.
- **Google tag:** `AW-590577903` is installed once per page on the home page, form and thank-you page. There was no existing Google Ads, GTM or consent code (only a commented-out GA4 placeholder, now replaced by the single tag).
- **Conversion:** fires only on `/thank-you`, only after the server has confirmed the save, once per registration (server flag, per-tab guard, and Google's `transaction_id` = reference). It needs your conversion **label** in `register-config.php`; until then nothing fires.
- **Still to do by you:** see `setup/REGISTRATION-SETUP.md` and GO-LIVE section 5a.

---

## Contact messages are saved before the email is sent (2 October 2026)

Files changed: `contact.php`, `.htaccess`, `GO-LIVE.md` (and this file).

- Every valid Contact us message is first saved as a row in `contact-messages.csv` in the site folder (created automatically; opens in Excel). Columns: Date, Name, Cellphone, Email, Topic, Message, Page, Status.
- Then the email is sent. If the email fails, the visitor still sees the error message, and a second row for the same message is added with Status **EMAIL FAILED** so you can follow up.
- Spam-trap hits and invalid entries are not saved.
- Text starting with `=`, `+`, `-` or `@` is neutralised so Excel can't run it as a formula.
- `.htaccess` now blocks all `.csv` files from the web. Visiting `/contact-messages.csv` must show 403.
- If the file can't be written (folder permissions), the email is still attempted and the problem is written to the server error log.
- `GO-LIVE.md` no longer mentions the removed call-back form. `lead.php` is not referenced anywhere and can be deleted (download any old `leads.csv` first).
- Tested by running `contact.php` with sample messages: saved, spam skipped, formulas neutralised, failed email marked.

---

## Dropdowns under Avon and Justine (2 October 2026)

File changed: `index.html` (and this file).

- **Avon** and **Justine** in the navigation bar each have a dropdown with **Sign up** and **Shop**, as on the old site. They use the same links as the buttons on the page (tracked as `join_click` and `store_click`, location `nav`).
- Desktop: opens on hover, or with the small arrow next to the name (keyboard and touch). Escape closes it.
- Mobile: the two options show indented under Avon and Justine in the menu.
- Clicking the words Avon or Justine still scrolls to that section.

---

## Contact us made more visible (1 October 2026)

File changed: `index.html` (and this file).

- **Navigation bar:** "Contact us" is now a pink button on the right of the sticky bar, so it stays on screen while visitors scroll. In the mobile menu it is a full-width button.
- **Contact band:** a new blue band above the footer ("Questions? Get in touch") with a Contact us button (opens the message form) and a WhatsApp us button. Both are tracked (`contact_click`, `whatsapp_click`).
- Nothing else changed: the message form, floating WhatsApp button and menu work as before.

---

## Page trimmed to match the old site's structure (1 October 2026)

File changed: `index.html` (and this file).

- **Sections now:** header, navigation, intro, Avon, Justine, shop, footer. Same order as the old page.
- **Removed:** the "Not sure which is right for you?" form section, "How joining works", the FAQ, the "Sell both?" badge, the extra "Ask about..." buttons and store links in the Avon and Justine rows, and the "Need help with an order?" line. The footer is shorter.
- **Contact:** visitors reach you through the WhatsApp number in the header, the floating WhatsApp button, and the Contact us link, which still opens the message form (`contact.php`).
- **Search data:** the FAQ block was removed from the structured data, because Google expects it to match visible content.
- **Code:** the script for the removed form was deleted. The mobile menu, contact form, floating button and click tracking are unchanged.
- **Now unused:** `lead.php` (it only served the removed form) and `leads.csv` if one exists. Both can be deleted.

---

## Look brought closer to the old site (1 October 2026)

File changed: `index.html` (only). Forms, scripts, SEO data, PHP files and tracking are untouched.

- **Layout follows the old page:** white masthead with a serif brand name and tagline, WhatsApp number top right, dark navigation bar (sticky on desktop), a centred "Your opportunity starts here" intro, then an Avon row (text left, picture right), a Justine row (picture left, text right) and a shop section where the store pictures link to the stores.
- **Old palette:** blue headings (#006489), charcoal navigation, buttons and footer, pale-blue bands, pink button hover (deepened slightly so white text stays readable).
- **A bit better:** rounded corners, soft shadows, numbered step cards, card-style FAQ, lift on hover, larger tap targets, same mobile menu.
- **Images now used, from `img/`** (relative paths, so they work from any folder and when opened locally):
  - `AVON-REPRESENTATIVE-1.png` in the Avon row
  - `Copy-of-AVON-REPRESENTATIVE-1.png` in the Justine row
  - `Avon-Online-Store-300x300.png` and `JUSTINE-STORE-300x300.jpg` on the shop cards
  - All four are identical to the files on the old site. Width and height are set to stop the page jumping, and the three below the fold load lazily.
- **Fonts:** the Bricolage Grotesque heading font is no longer used (headings are Figtree). `fonts/bricolage-grotesque-800-latin.woff2` can be deleted.
- **Business name:** `YOUR BUSINESS NAME` is now `Our Beauty Products` everywhere (page, title, structured data, footer, form consent text, `site.webmanifest`, `mail-config.php` sender names). The header shows it in the Great Vibes calligraphy script, self-hosted as `fonts/great-vibes-latin.woff2` (SIL Open Font License) and preloaded.
- **Still placeholders:** `YOUR-FACEBOOK`, `YOUR-INSTAGRAM`, `YOUR TOWN`, `PROVINCE`, `G-XXXXXXXXXX`.

---

## Real details filled in (1 October 2026)

- **Domain:** `ourbeautyproducts.co.za`, everywhere it appears. That covers the canonical URL, hreflang, Open Graph, JSON-LD, sitemap.xml and robots.txt. The public email is now `info@ourbeautyproducts.co.za`.
- **WhatsApp and cell number:** 082 644 8208 on screen, `+27826448208` in the call link and JSON-LD, and `27826448208` in the WhatsApp links and the script setting.
- **Stores:** `https://my.avon.co.za/store/Rheeshop` and `https://my.justine.co.za/store/Lizsbeautyshop`.
- **Sign-up links:**
  - Avon: `https://arp.avon.com/prp/ZA/en/shopwithRhee`
  - Justine: `https://arp.avon.com/prp/JT/en/shopwith26064123_04`
- **mail-config.php:** only `smtp_host` was changed, to `mail.ourbeautyproducts.co.za`. Username, password and the from/to emails are still yours to fill in.
- **Still placeholders:** `YOUR BUSINESS NAME`, `YOUR-FACEBOOK`, `YOUR-INSTAGRAM`, `YOUR TOWN`, `PROVINCE`, `G-XXXXXXXXXX`.

---

# Design, experience, copy, accessibility and SEO pass (1 October 2026)

Files changed: `index.html`, `contact.php`, `.htaccess`, `robots.txt`
New files: `lead.php`, `sitemap.xml`, `site.webmanifest`, `favicon.svg`, `favicon-48.png`, `apple-touch-icon.png`, `icon-192.png`, `icon-512.png`, `fonts/bricolage-grotesque-800-latin.woff2`, `fonts/figtree-var-latin.woff2`, `CHANGELOG.md`, `GO-LIVE.md`
Not touched: `PHPMailer.php`, `SMTP.php`, `Exception.php`, `LICENSE`, `mail-config.php`

---

## Fixes to forms that were not working

- **contact.php could not load PHPMailer.** It required `phpmailer/*.php`, but the files sit in the site root, so every Contact us message failed with a PHP fatal error. contact.php now loads PHPMailer from `phpmailer/` if that folder exists, otherwise from the root. Both setups were tested.
- **lead.php was missing**, so the hero form posted to a page that doesn't exist and still said "Details sent". A new lead.php saves each lead to `leads.csv`, which `.htaccess` keeps private.
- **The hero form's WhatsApp step was blocked.** WhatsApp only opened after the save finished, and mobile browsers block that as a popup. The form now saves first, then shows an **Open WhatsApp** button with the person's details already typed in. If saving fails, the same button appears, so the lead isn't lost.

---

## Design (design:design-system + frontend-design)

- **Design tokens.** The CSS was audited and every colour, font size, space, radius, shadow and duration now comes from a named custom property on `:root`. No raw colour values are left in component rules.
  - Font sizes: about 14 one-off sizes are now 7 (`--fs-sm` to `--fs-3xl`).
  - Radii: 7 one-off values are now 4 (`--r-sm`, `--r-md`, `--r-lg`, `--r-pill`).
  - Colours: three loose colours (#D9C3CD, #E8D2DC, #0D403D) became named tokens (`--c-line`, `--c-line-strong`, `--c-justine-deep`).
  - Shadows: four plum-tinted shadow levels.
  - Focus: one focus colour token that switches automatically on dark sections.
- **The Avon/Justine split is the signature element.**
  - **Balanced panels.** Both panels are now deep and equal in weight: plum for Avon, a new deep teal for Justine. Before, the Avon panel was dark and the Justine panel pale, so Avon dominated.
  - **Same layout on both sides:** heading, pitch, three ticked points, **Sign up** (white button), **Ask about…** (outline button) and **Shop … online**.
  - **"Sell both?" badge.** A round badge sits on the seam where the two panels meet. Tapping it ticks "Both" in the form and takes you there.
  - **One echo elsewhere.** The plum and teal pair appears in only one other place: the selected chip in the form (the "Both" chip is half plum, half teal).
- **Calmer surroundings.**
  - The form button is now ink (dark) instead of Avon pink, because the form covers both brands.
  - Step numbers changed from gold to plum.
  - The shop cards are flat, with no shadow.
  - The nav dropdowns are gone.
- **Fonts and icons.**
  - Same direction as before: Bricolage Grotesque for headings, Figtree for text. Bricolage uses one weight (800) and Figtree one variable file, about 42 KB together.
  - A new neutral icon (a circle split plum and teal) replaces the 💄 emoji favicon. It is not based on either brand's logo.

## Experience (frontend-design)

- **Mobile**
  - The header only sticks on screens 761px and wider. On phones it scrolls away, so it never covers form fields.
  - Every button, link, chip and field is at least 44px tall. It was tested at 320px and 390px with no sideways scrolling.
  - The menu closes with Escape, by tapping a link, or by tapping outside it.
- **Floating WhatsApp button.** It hides while someone is typing in a field (so it doesn't sit on top of the keyboard), while the form's consent and submit area is on screen, and while the Contact us window is open. The footer has extra bottom space so the button never covers the last link.
- **Both forms**
  - Each problem is shown under its own field, saying how to fix it, and the cursor moves to the first one.
  - Errors clear as soon as the person fixes the field.
  - Nothing is cleared when sending fails.
  - Cellphone numbers are accepted as `082 123 4567`, `+27 82 123 4567` or `27821234567`.
  - Phones show the number keypad for cellphone fields and the email keyboard for email. Autofill fields are set so the browser fills them in correctly.
  - Sending gives up after 15 seconds on a slow connection and shows a message instead of hanging.
- **Contact us window**
  - Focus stays inside the window. Escape closes it, and focus goes back to the button that opened it.
  - The page behind can't scroll while it's open.
  - Tapping outside only closes it if nothing has been typed.
  - It shows a clear state for each stage: **Sending…**, **Message sent** (with focus moved to the confirmation), and a failure message with WhatsApp and Call links.
- **Topic pre-selection** uses `data-topic`:
  - "Ask about Avon" opens the window with **Joining Avon** selected.
  - "Ask about Justine" opens it with **Joining Justine** selected.
  - "Need help with an order? Send us a message" opens it with **Help with an order** selected.
- **No-JavaScript fallback.** Contact us links are real `mailto:` links, so they still work if JavaScript fails. `#contact-us` still opens the window.
- **Email is optional** in the Contact us form; phone stays required. contact.php only sets Reply-To when an email is given. The data contact.php receives has the same field names as before. Error replies now also say which field to fix (`field`).
- **Pre-selecting the interest from a link.** `?interest=avon|justine|both|buy` in a link (for example in an ad or WhatsApp status) pre-selects that option in the form.
- **Motion.** The only animation is the Contact us window rising in when it opens (180ms). It is turned off when the phone or computer is set to reduce motion.
- **Server side**
  - lead.php checks every field and catches bots (hidden field and speed check). It limits each browser to 5 submissions an hour.
  - It stops anything typed into a field from running as a formula when you open the file in Excel.
  - Leads are written in South African time, in a CSV that Excel opens correctly.

## Copy (design:ux-copy)

- **Buttons say exactly what happens.** "Join Avon" is now **Sign up with Avon** (it opens the official sign-up). "Send my details" is now **Save my details**. **Open WhatsApp**, **Send message**, **Ask about Avon/Justine** and **Shop Avon online** follow the same rule.
- **The hero form now matches what it does.** "Get a call back" (when the reply is actually a WhatsApp) is now **Ask us to WhatsApp you**.
- **Error messages** say what's wrong and how to fix it, for example "Enter a 10-digit cellphone number, for example 082 123 4567."
- **Success and failure states**:
  - **Details saved**, with an Open WhatsApp option.
  - **Message sent**.
  - Failure messages that keep everything typed and offer WhatsApp or a call.
- **FAQ**
  - Rewritten in plain South African English, and the answers now refer to the new button names.
  - A sixth question, "Can I buy products without joining?", was added.
  - The FAQPage JSON-LD matches the visible FAQ character for character (checked by script).
- **POPIA and disclaimer.** The consent and disclaimer wording is unchanged in meaning. "Privacy policy" is now a full link on its own line.
- **No new claims.** No claims about fees, discounts or earnings were added. These claims were already on your page and are kept — please confirm each is true:
  - "Earn on every order you place"
  - "Earn extra income"
  - "Free guidance" (meta description)
  - "usually in less than a week"
  - "within one working day"
  - "have your products delivered"

## Accessibility (design:accessibility-review)

- **Colours fixed to meet WCAG 2.1 AA**:

  | What | Before | After |
  |---|---|---|
  | White on WhatsApp green | 4.31:1 (fail) | 5.42:1 (#0E7A3F) |
  | Focus ring on light sections | gold, 2.45:1 (fail) | ink, 16.8:1; gold-light 6–10:1 on dark sections |
  | Gold step numbers | 2.45:1 (fail) | plum, 12.7:1 |
  | Form field and chip borders | 1.58:1 (fail) | 4.4:1 (#8C6B7B) |
  | Avon button on plum panel (button edge) | 2.04:1 | white button, 13.3:1 |

- **Form errors** are linked to their fields with `aria-invalid` and `aria-describedby`. The cursor moves to the first error, and messages that aren't about one field use `role="alert"`.
- **Floating WhatsApp button.** Its spoken name now matches its visible text ("WhatsApp us"), which fixes WCAG 2.5.3 Label in Name.
- **Keyboard.** All controls, including the nav, work by keyboard. The modal behaviour is described under Experience.
- **Screen readers.** The selected chip shows a tick as well as a colour change. Error messages carry an icon as well as colour.

## SEO

- **Title, description and headings.**
  - Title: "Become an Avon Rep or Justine Consultant in South Africa | YOUR BUSINESS NAME".
  - Meta description: 148 characters.
  - H1: "Become an Avon representative or Justine consultant".
  - Section IDs (`#avon`, `#justine`, `#how-it-works`, `#shop`, `#faq`) give in-page links with descriptive anchor text.
- **Head tags.** Added `hreflang="en-ZA"` and `x-default`, og:image width/height/alt, twitter:image:alt, the favicon set and a web manifest.
- **JSON-LD.** Rebuilt as LocalBusiness (with town and province), WebSite, WebPage and FAQPage, linked together by `@id`. BreadcrumbList was left out, because a home page with one crumb gains nothing. Add it on future subpages (see GO-LIVE.md).
- **Core Web Vitals**
  - **Fonts:** self-hosted and preloaded, with `font-display: swap`. Fallback fonts are size-matched to Arial using measurements from the real font files, so the text doesn't jump when the font arrives.
  - **Third parties:** no third-party requests at all, unless you turn on GA4.
  - **Size:** all CSS is inline, and the whole page is 16.6 KB gzipped.
- **GA4 events.** A `track()` helper sends `generate_lead`, `whatsapp_click`, `join_click` and `store_click` (with `brand` and `location`). It does nothing if GA4 isn't installed. A ready GA4 snippet sits commented out in the `<head>`.
- **.htaccess**
  - Redirects: HTTPS, www to non-www, and `/index.html` to `/`.
  - Compression: Brotli or gzip, whichever the host supports.
  - Caching: fonts are cached for a year, images and icons for a month, and the HTML is always rechecked.
  - Security: basic security headers, and no directory listings.
  - Blocked from public access: the PHPMailer files in the root, LICENSE, `.md` notes, `leads.csv` and `mail-config.php`.
- **robots.txt and sitemap.xml.** robots.txt now just points to the sitemap; the old Disallow lines advertised private file paths that are blocked anyway. sitemap.xml is new.
