(() => {
"use strict";

// Registration enquiry form. The conversion is NOT fired here: this script only sends the visitor to /thank-you
// after the server has confirmed the registration was saved. /thank-you fires the conversion (once).

const ENDPOINT   = "/register-submit.php";
const MAX_BYTES  = 6 * 1024 * 1024;    // keep in step with max_file_bytes in register-config.php
const MAX_SIDE   = 2000;               // photos are shrunk to this many pixels on the long edge
const TIMEOUT_MS = 120000;

const form    = document.getElementById("reg-form");
if (!form) return;
const submit  = document.getElementById("reg-submit");
const status  = document.getElementById("reg-status");
const summary = document.getElementById("reg-summary");
const $ = (sel, el = document) => el.querySelector(sel);

const processed = { id_copy: null, id_selfie: null };   // File objects ready to send
let submitting = false;

// ---------- helpers ----------
const fieldEl = name => form.elements.namedItem(name);
const errEl   = name => document.getElementById("f-" + name + "-err");

function setError(name, text) {
  const out = errEl(name);
  if (out) out.textContent = text || "";
  const el = fieldEl(name);
  const controls = el && el.length !== undefined && el.tagName !== "SELECT" ? Array.from(el) : [el];   // radio group
  controls.forEach(c => { if (!c) return; if (text) c.setAttribute("aria-invalid", "true"); else c.removeAttribute("aria-invalid"); });
}
function clearErrors() { Object.keys(rules).forEach(n => setError(n, "")); summary.textContent = ""; }

function saPhone(v) {
  let d = String(v).replace(/\D/g, "");
  if (d.length === 11 && d.startsWith("27")) d = "0" + d.slice(2);
  return /^0\d{9}$/.test(d) ? d : "";
}
const words = s => s.trim().split(/\s+/).filter(Boolean);
const fmtSize = n => n >= 1048576 ? (n / 1048576).toFixed(1) + " MB" : Math.max(1, Math.round(n / 1024)) + " KB";

// ---------- validation (the server re-checks everything) ----------
const place = /^[\p{L}\p{N}][\p{L}\p{N}\p{M} .,'’()\/&-]{1,79}$/u;
const rules = {
  full_name: v => (/^[\p{L}][\p{L}\p{M} .'’-]{1,118}$/u.test(v.trim()) && words(v).length >= 2) ? "" : "Enter your full names exactly as they appear on your ID (at least two names).",
  phone:     v => saPhone(v) ? "" : "Enter a 10-digit South African number, for example 082 123 4567.",
  email:     v => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) ? "" : "Enter a valid email address, for example name@example.co.za.",
  brand:     () => form.querySelector('input[name="brand"]:checked') ? "" : "Choose Avon or Justine.",
  province:  v => v ? "" : "Choose your province.",
  town:      v => place.test(v.trim()) ? "" : "Enter your town or city.",
  suburb:    v => place.test(v.trim()) ? "" : "Enter your suburb.",
  street:    v => (v.trim().length >= 3 && /\p{L}/u.test(v)) ? "" : "Enter your house number and street name, for example 12 Jacaranda Street.",
  postal_code: v => /^\d{4}$/.test(v.trim()) ? "" : "Enter your 4-digit postal code.",
  id_copy:   () => processed.id_copy   ? "" : "Add a clear copy or photo of your ID.",
  id_selfie: () => processed.id_selfie ? "" : "Add a photo of yourself holding your ID.",
  consent:   () => fieldEl("consent").checked ? "" : "You need to tick the box before we can accept your documents."
};
const valueOf = name => {
  if (name === "brand") return "";
  const el = fieldEl(name);
  return el && el.value !== undefined ? el.value : "";
};

function validateAll() {
  const bad = [];
  for (const name of Object.keys(rules)) {
    const msg = rules[name](valueOf(name));
    setError(name, msg);
    if (msg) bad.push(name);
  }
  return bad;
}
function focusField(name) {
  let el = fieldEl(name);
  if (name === "brand") el = form.querySelector('input[name="brand"]');
  if (el && el.focus) { el.focus({ preventScroll: true }); el.scrollIntoView({ block: "center", behavior: "smooth" }); }
}
function showSummary(names, text) {
  summary.textContent = text || (names.length === 1 ? "Please fix the 1 item marked below." : "Please fix the " + names.length + " items marked below.");
}

// live feedback once a field has been touched
Object.keys(rules).forEach(name => {
  const el = fieldEl(name);
  if (!el || name === "id_copy" || name === "id_selfie" || name === "brand" || name === "consent") return;
  el.addEventListener("blur", () => setError(name, rules[name](el.value)));
  el.addEventListener("input", () => { if (el.getAttribute("aria-invalid")) setError(name, rules[name](el.value)); });
});
form.querySelectorAll('input[name="brand"]').forEach(r => r.addEventListener("change", () => setError("brand", "")));
fieldEl("consent").addEventListener("change", e => setError("consent", e.target.checked ? "" : rules.consent()));
fieldEl("postal_code").addEventListener("input", e => { e.target.value = e.target.value.replace(/\D/g, "").slice(0, 4); });

// ---------- files: check, shrink photos on the phone, preview ----------
async function shrinkImage(file) {
  if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return file;
  const url = URL.createObjectURL(file);
  try {
    const img = new Image();
    img.src = url;
    await img.decode();                                   // browsers apply the phone's rotation automatically
    const long = Math.max(img.naturalWidth, img.naturalHeight);
    if (long <= MAX_SIDE && file.size <= 1.5 * 1048576) return file;
    const scale = Math.min(1, MAX_SIDE / long);
    const c = document.createElement("canvas");
    c.width = Math.round(img.naturalWidth * scale); c.height = Math.round(img.naturalHeight * scale);
    const ctx = c.getContext("2d");
    ctx.fillStyle = "#fff"; ctx.fillRect(0, 0, c.width, c.height);
    ctx.drawImage(img, 0, 0, c.width, c.height);
    const blob = await new Promise(res => c.toBlob(res, "image/jpeg", 0.85));
    if (!blob || blob.size >= file.size) return file;
    return new File([blob], file.name.replace(/\.[^.]+$/, "") + ".jpg", { type: "image/jpeg" });
  } catch (e) {
    return file;                                          // could not shrink: send as chosen, the server will check it
  } finally {
    URL.revokeObjectURL(url);
  }
}

function renderPreview(name, file) {
  const box = document.getElementById("f-" + name + "-preview");
  box.textContent = "";
  if (!file) { box.hidden = true; return; }
  let thumb;
  if (file.type.startsWith("image/")) {
    thumb = document.createElement("img");
    thumb.alt = ""; thumb.src = URL.createObjectURL(file);
    thumb.addEventListener("load", () => URL.revokeObjectURL(thumb.src), { once: true });
  } else {
    thumb = document.createElement("span"); thumb.className = "pdf-icon"; thumb.textContent = "PDF"; thumb.setAttribute("aria-hidden", "true");
  }
  const meta = document.createElement("div"); meta.className = "file-meta";
  const nm = document.createElement("strong"); nm.textContent = file.name;
  const sz = document.createElement("span"); sz.textContent = fmtSize(file.size) + " ready to upload";
  meta.append(nm, sz);
  const rm = document.createElement("button"); rm.type = "button"; rm.className = "link file-remove"; rm.textContent = "Remove";
  rm.setAttribute("aria-label", "Remove " + file.name);
  rm.addEventListener("click", () => { processed[name] = null; fieldEl(name).value = ""; renderPreview(name, null); fieldEl(name).focus(); });
  box.append(thumb, meta, rm);
  box.hidden = false;
}

async function onFile(name, allowPdf) {
  const input = fieldEl(name), file = input.files && input.files[0];
  processed[name] = null; renderPreview(name, null); setError(name, "");
  if (!file) return;
  const okType = /^image\/(jpeg|png|webp)$/.test(file.type) || (allowPdf && file.type === "application/pdf");
  if (!okType) { input.value = ""; setError(name, allowPdf ? "Upload a JPG, PNG, WebP or PDF file. (iPhone photos in HEIC format: choose a different photo, or take a new one.)" : "Upload a photo (JPG, PNG or WebP)."); return; }
  const ready = await shrinkImage(file);
  if (ready.size > MAX_BYTES) { input.value = ""; setError(name, "That file is too large. Choose a smaller photo (under " + Math.round(MAX_BYTES / 1048576) + " MB)."); return; }
  processed[name] = ready;
  renderPreview(name, ready);
}
fieldEl("id_copy").addEventListener("change", () => onFile("id_copy", true));
fieldEl("id_selfie").addEventListener("change", () => onFile("id_selfie", false));

// ---------- submit ----------
function setBusy(on, text) {
  submitting = on;
  submit.disabled = on;
  submit.setAttribute("aria-busy", on ? "true" : "false");
  status.textContent = text || "";
}

async function newToken() {
  const res = await fetch(ENDPOINT + "?token=1", { credentials: "same-origin", cache: "no-store" });
  const j = await res.json();
  if (!j.ok || !j.token) throw new Error("token");
  document.getElementById("reg-token").value = j.token;
  return Math.max(0, Number(j.wait) || 0);
}
const sleep = ms => new Promise(r => setTimeout(r, ms));

async function send(retryToken) {
  const fd = new FormData(form);
  fd.delete("id_copy"); fd.delete("id_selfie");
  fd.set("id_copy", processed.id_copy, processed.id_copy.name);
  fd.set("id_selfie", processed.id_selfie, processed.id_selfie.name);

  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), TIMEOUT_MS);
  let res, j = {};
  try {
    res = await fetch(ENDPOINT, { method: "POST", body: fd, credentials: "same-origin", cache: "no-store", signal: ctrl.signal });
    j = await res.json().catch(() => ({}));
  } finally { clearTimeout(timer); }

  if (res.ok && j.ok === true && typeof j.redirect === "string" && j.redirect.startsWith("/")) return { done: true, redirect: j.redirect };
  if (j.code === "token" && retryToken) {
    // The page was open long enough for the session to expire: get a fresh token, wait out the anti-bot delay, resend.
    status.textContent = "Reconnecting. One moment…";
    const wait = await newToken();
    await sleep(wait * 1000);
    status.textContent = "Uploading your details and documents. Please keep this page open…";
    return send(false);
  }
  return { done: false, status: res.status, message: j.message || "", errors: j.errors || null };
}

form.addEventListener("submit", async e => {
  e.preventDefault();
  if (submitting) return;                               // blocks double taps and double clicks
  summary.textContent = "";
  const bad = validateAll();
  if (bad.length) { showSummary(bad); focusField(bad[0]); return; }

  setBusy(true, "Uploading your details and documents. Please keep this page open…");
  try {
    const r = await send(true);
    if (r.done) { status.textContent = "Saved. Taking you to the next page…"; window.location.assign(r.redirect); return; }
    if (r.errors) {
      const names = Object.keys(r.errors).filter(n => n in rules);
      names.forEach(n => setError(n, r.errors[n]));
      showSummary(names, r.message);
      if (names.length) focusField(names[0]);
    } else {
      summary.textContent = r.message || "Something went wrong and nothing was sent. Please try again, or WhatsApp us on 082 644 8208.";
      summary.focus();
    }
    setBusy(false, "");
  } catch (err) {
    summary.textContent = "We couldn't reach the server, so nothing was sent. Your details are still here: check your connection and tap Submit again, or WhatsApp us on 082 644 8208.";
    summary.focus();
    setBusy(false, "");
  }
});

// Coming back with the Back button after success: allow the form to be used again (a repeat is safely ignored by the server)
window.addEventListener("pageshow", e => { if (e.persisted) setBusy(false, ""); });
})();
