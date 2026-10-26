(() => {
"use strict";

// ---- Settings ----
const WHATSAPP_NUMBER  = "27826448208";   // international format, no +
const WHATSAPP_DISPLAY = "082 644 8208";
const CONTACT_ENDPOINT = "contact.php";   // emails the Contact us form through SMTP
const TIMEOUT_MS       = 15000;           // give up on slow connections after 15 seconds

const $  = (sel, el = document) => el.querySelector(sel);
const $$ = (sel, el = document) => Array.from(el.querySelectorAll(sel));
const waLink = text => `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(text)}`;

// ---- GA4: only sends if gtag is installed, never breaks the page ----
function track(name, params) {
  try { if (typeof window.gtag === "function") window.gtag("event", name, params || {}); } catch (e) {}
}
document.addEventListener("click", e => {
  const el = e.target.closest && e.target.closest("[data-track]");
  if (!el) return;
  const params = { location: el.dataset.location || "" };
  if (el.dataset.brand) params.brand = el.dataset.brand;
  track(el.dataset.track, params);
});

// ---- Helpers ----
function saPhone(value) {
  let d = String(value).replace(/\D/g, "");
  if (d.length === 11 && d.startsWith("27")) d = "0" + d.slice(2);
  return /^0\d{9}$/.test(d) ? d : "";
}

async function postJSON(url, data) {
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), TIMEOUT_MS);
  try {
    const res  = await fetch(url, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data), signal: ctrl.signal });
    const json = await res.json().catch(() => ({}));
    return { ok: res.ok && json.ok === true, message: json.message || "", field: json.field || "" };
  } catch (err) {
    return { ok: false, message: "", field: "" };
  } finally {
    clearTimeout(timer);
  }
}

function setError(input, text) {
  const out = document.getElementById(input.id + "-err");
  if (out) out.textContent = text || "";
  if (text) input.setAttribute("aria-invalid", "true");
  else input.removeAttribute("aria-invalid");
}

// rules: { fieldName: value => "error text" or "" }. Returns the first invalid control, or null.
function validate(form, rules) {
  let first = null;
  for (const name of Object.keys(rules)) {
    const el  = form.elements.namedItem(name);
    const msg = rules[name](el.type === "checkbox" ? el.checked : el.value);
    setError(el, msg);
    if (msg && !first) first = el;
  }
  return first;
}

// Once a field has been flagged, re-check it as the person fixes it
function liveRevalidate(form, rules) {
  const recheck = e => {
    const el = e.target;
    if (el.getAttribute("aria-invalid") === "true" && rules[el.name]) {
      setError(el, rules[el.name](el.type === "checkbox" ? el.checked : el.value));
    }
  };
  form.addEventListener("input", recheck);
  form.addEventListener("change", recheck);
}

function showAlert(box, text, waText) {
  box.textContent = "";
  const p = document.createElement("p");
  p.textContent = text;
  box.append(p);
  const row = document.createElement("div");
  row.className = "cta-row";
  const wa = document.createElement("a");
  wa.className = "link";
  wa.href = waText ? waLink(waText) : `https://wa.me/${WHATSAPP_NUMBER}`;
  wa.textContent = waText ? "Open WhatsApp" : "WhatsApp us";
  wa.dataset.track = "whatsapp_click";
  wa.dataset.location = box.id;
  const call = document.createElement("a");
  call.className = "link";
  call.href = `tel:+${WHATSAPP_NUMBER}`;
  call.textContent = `Call ${WHATSAPP_DISPLAY}`;
  row.append(wa, call);
  box.append(row);
}
const clearAlert = box => { box.textContent = ""; };

function setBusy(form, btn, busy, label) {
  if (busy) { btn.dataset.label = btn.textContent; btn.textContent = label; }
  else if (btn.dataset.label) { btn.textContent = btn.dataset.label; }
  btn.setAttribute("aria-disabled", busy ? "true" : "false");
  form.setAttribute("aria-busy", busy ? "true" : "false");
  $$("input, textarea", form).forEach(el => { if (el.type !== "radio" && el.type !== "checkbox") el.readOnly = busy; });
}

// ---- Mobile menu ----
const nav = $("#nav");
const menuBtn = $(".menu-btn", nav);
function closeMenu(returnFocus) {
  if (!nav.classList.contains("open")) return;
  nav.classList.remove("open");
  menuBtn.setAttribute("aria-expanded", "false");
  if (returnFocus) menuBtn.focus();
}
menuBtn.addEventListener("click", () => {
  const open = nav.classList.toggle("open");
  menuBtn.setAttribute("aria-expanded", String(open));
});
// Dropdowns (Avon / Justine): open on hover (CSS) or via the caret button for keyboard and touch
const subItems = $$("#menu li.has-sub");
function closeSubs(returnFocus) {
  subItems.forEach(li => {
    if (!li.classList.contains("open")) return;
    li.classList.remove("open");
    const t = $(".sub-toggle", li);
    t.setAttribute("aria-expanded", "false");
    if (returnFocus && li.contains(document.activeElement)) t.focus();
  });
}
subItems.forEach(li => {
  const t = $(".sub-toggle", li);
  t.addEventListener("click", () => {
    const wasOpen = li.classList.contains("open");
    closeSubs(false);
    if (!wasOpen) { li.classList.add("open"); t.setAttribute("aria-expanded", "true"); }
  });
});
nav.addEventListener("keydown", e => { if (e.key === "Escape") { closeSubs(true); closeMenu(true); } });
$$("#menu a").forEach(a => a.addEventListener("click", () => closeMenu(false)));
document.addEventListener("click", e => { if (!nav.contains(e.target)) { closeMenu(false); closeSubs(false); } });

// ---- Floating WhatsApp button: hides while typing and while the modal is open ----
const waFloat = $(".wa-float");
const waState = { typing: false, modal: false };
const syncWa = () => waFloat.classList.toggle("is-hidden", waState.typing || waState.modal);
document.addEventListener("focusin",  e => { waState.typing = e.target.matches("input, select, textarea"); syncWa(); });
document.addEventListener("focusout", () => { waState.typing = false; syncWa(); });

// ---- Contact us modal: sends through contact.php -> SMTP ----
const modal  = $("#contact-modal");
const cForm  = $("#contact-form");
const cAlert = $("#cm-alert");
const cSent  = $("#cm-sent");
const cBtn   = $("button[type=submit]", cForm);
let lastFocus = null, startedAt = 0, cBusy = false;

const cRules = {
  name:    v => v.trim() ? "" : "Enter your name so we know who to reply to.",
  phone:   v => saPhone(v) ? "" : (v.trim() ? "Enter a 10-digit cellphone number, for example 082 123 4567." : "Enter your cellphone number, for example 082 123 4567."),
  email:   v => !v.trim() || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) ? "" : "Check your email address, for example name@example.co.za, or leave it empty.",
  message: v => v.trim() ? "" : "Type your message so we know how to help.",
  consent: v => v ? "" : "Tick the box so we may contact you about your message."
};
liveRevalidate(cForm, cRules);

const isDirty = () => ["name", "phone", "email", "message"].some(n => cForm.elements.namedItem(n).value.trim());

function openContact(topic) {
  if (typeof modal.showModal !== "function") return false;   // very old browsers: let the mailto link work
  lastFocus = document.activeElement;
  cForm.hidden = false;
  cSent.hidden = true;
  clearAlert(cAlert);
  const sel = cForm.elements.namedItem("topic");
  if (topic && Array.from(sel.options).some(o => o.value === topic)) sel.value = topic;
  startedAt = Date.now();
  closeMenu(false);
  document.documentElement.classList.add("modal-open");
  waState.modal = true; syncWa();
  modal.showModal();
  cForm.elements.namedItem("name").focus();
  return true;
}
function closeContact() { if (!cBusy && modal.open) modal.close(); }

modal.addEventListener("close", () => {
  document.documentElement.classList.remove("modal-open");
  waState.modal = false; syncWa();
  if (lastFocus && document.contains(lastFocus)) lastFocus.focus();
});
modal.addEventListener("cancel", e => { if (cBusy) e.preventDefault(); });              // Escape
modal.addEventListener("click", e => { if (e.target === modal && !isDirty()) closeContact(); });   // backdrop tap
modal.querySelectorAll("[data-close]").forEach(el => el.addEventListener("click", closeContact));

// Keep Tab and Shift+Tab inside the dialog in every browser
modal.addEventListener("keydown", e => {
  if (e.key !== "Tab") return;
  const items = $$('a[href], button, input:not([tabindex="-1"]), select, textarea', modal)
    .filter(el => !el.disabled && el.getClientRects().length && !el.closest("[hidden]"));
  if (!items.length) return;
  const first = items[0], last = items[items.length - 1];
  if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
  else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
});

$$("[data-open-contact]").forEach(el => el.addEventListener("click", e => {
  if (openContact(el.dataset.topic)) e.preventDefault();
}));
const hashOpen = () => { if (location.hash === "#contact-us") openContact(); };
window.addEventListener("hashchange", hashOpen);
hashOpen();

cForm.addEventListener("submit", async e => {
  e.preventDefault();
  if (cBusy) return;
  clearAlert(cAlert);
  const bad = validate(cForm, cRules);
  if (bad) { bad.focus(); return; }

  const f = cForm.elements;
  const data = {
    name: f.namedItem("name").value.trim(),
    email: f.namedItem("email").value.trim(),
    phone: f.namedItem("phone").value.trim(),
    topic: f.namedItem("topic").value,
    message: f.namedItem("message").value.trim(),
    consent: f.namedItem("consent").checked,
    website: f.namedItem("website").value,
    started: startedAt
  };

  cBusy = true;
  setBusy(cForm, cBtn, true, "Sending…");
  const res = await postJSON(CONTACT_ENDPOINT, data);
  setBusy(cForm, cBtn, false);
  cBusy = false;

  if (res.ok) {
    track("generate_lead", { form: "contact_modal", topic: data.topic });
    cForm.reset();
    cForm.hidden = true;
    cSent.hidden = false;
    $("#cm-sent-title").focus();
    return;
  }
  const field = res.field && f.namedItem(res.field);
  if (field && field.id) { setError(field, res.message); field.focus(); return; }
  showAlert(cAlert, res.message || `We couldn't send your message. Check your connection and try again, or WhatsApp us on ${WHATSAPP_DISPLAY}.`);
});
})();
