/**
 * Our Beauty Products: registration bridge (Google Apps Script web app)
 *
 * What it does
 *   Receives a registration from register-submit.php on your web host, then
 *   1. saves the two ID files into a PRIVATE sub-folder of your Drive folder, and
 *   2. appends one row to your Google Sheet (tab "Registrations").
 *
 * Nothing in here is a secret. The secret, Sheet ID and Folder ID live in
 * Script properties (Project settings > Script properties), never in this code.
 * Setup steps: see setup/REGISTRATION-SETUP.md
 */

var SHEET_NAME = 'Registrations';
var MAX_FILES = 2;
var MAX_FILE_BYTES = 8 * 1024 * 1024;
var ALLOWED_MIME = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp', 'application/pdf': 'pdf' };

var HEADERS = [
  'Received', 'Reference', 'Status', 'Brand', 'Full names (as on ID)', 'Contact number', 'Email',
  'Province', 'Town', 'Suburb', 'Street address', 'Postal code',
  'ID copy (private Drive file)', 'Photo holding ID (private Drive file)', 'Applicant folder (private)',
  'Consent', 'Consent version', 'Possible duplicate', 'Submission ID', 'Source'
];
var COL = {}; HEADERS.forEach(function (h, i) { COL[h] = i; });

/** Health check: opening the web app URL in a browser should show {"ok":true,...}. Reveals nothing else. */
function doGet() {
  return json_({ ok: true, service: 'obp-registration-bridge' });
}

function doPost(e) {
  var lock = LockService.getScriptLock();
  var locked = false;
  try {
    var props = PropertiesService.getScriptProperties();
    var data = JSON.parse((e && e.postData && e.postData.contents) || '{}');

    if (!safeEqual_(String(data.secret || ''), String(props.getProperty('BRIDGE_SECRET') || ''))) {
      return json_({ ok: false, error: 'unauthorized' });
    }
    if (data.action !== 'register') return json_({ ok: false, error: 'bad_request' });

    var f = data.fields || {};
    var sid = String(data.submission_id || '');
    var ref = String(data.ref || '');
    if (!/^[a-f0-9]{32,64}$/.test(sid) || !/^OBP-\d{8}-[A-Z0-9]{5}$/.test(ref)) return json_({ ok: false, error: 'bad_request' });

    var files = data.files || [];
    if (files.length < 1 || files.length > MAX_FILES) return json_({ ok: false, error: 'bad_files' });

    lock.waitLock(30000);
    locked = true;

    var sheet = getSheet_(props);

    // Same submission arriving twice (retry after a slow response): answer with the original, write nothing new.
    var rows = sheet.getLastRow() > 1 ? sheet.getRange(2, 1, sheet.getLastRow() - 1, HEADERS.length).getValues() : [];
    for (var i = 0; i < rows.length; i++) {
      if (rows[i][COL['Submission ID']] === sid) {
        return json_({ ok: true, ref: rows[i][COL['Reference']], duplicate: true });
      }
    }

    // Same person applying again in a new session: still saved, but flagged for you.
    var phone = norm_(f.phone), email = norm_(f.email), brand = norm_(f.brand);
    var dupRef = '';
    for (var j = 0; j < rows.length; j++) {
      var r = rows[j];
      if (norm_(r[COL['Brand']]) === brand && (norm_(r[COL['Contact number']]) === phone || norm_(r[COL['Email']]) === email)) {
        dupRef = String(r[COL['Reference']]); break;
      }
    }

    // Private applicant folder inside your private registrations folder.
    var parent = DriveApp.getFolderById(props.getProperty('FOLDER_ID'));
    var folder = parent.createFolder(ref + ' - ' + safeName_(f.full_name || 'applicant'));
    var links = {};
    try {
      files.forEach(function (file) {
        var ext = ALLOWED_MIME[file.mime];
        if (!ext) throw new Error('bad_mime');
        var bytes = Utilities.base64Decode(file.data);
        if (bytes.length < 1 || bytes.length > MAX_FILE_BYTES) throw new Error('bad_size');
        var blob = Utilities.newBlob(bytes, file.mime, ref + '_' + (file.kind === 'id_selfie' ? 'ID-photo' : 'ID-copy') + '.' + ext);
        var created = folder.createFile(blob);
        try { created.setSharing(DriveApp.Access.PRIVATE, DriveApp.Permission.NONE); } catch (x) { /* domain policy may forbid; default is already private */ }
        links[file.kind] = created.getUrl();
      });
      try { folder.setSharing(DriveApp.Access.PRIVATE, DriveApp.Permission.NONE); } catch (x) {}

      var row = new Array(HEADERS.length).fill('');
      row[COL['Received']] = String(data.received || Utilities.formatDate(new Date(), 'Africa/Johannesburg', 'yyyy-MM-dd HH:mm'));
      row[COL['Reference']] = ref;
      row[COL['Status']] = 'New';
      row[COL['Brand']] = f.brand || '';
      row[COL['Full names (as on ID)']] = f.full_name || '';
      row[COL['Contact number']] = f.phone || '';
      row[COL['Email']] = f.email || '';
      row[COL['Province']] = f.province || '';
      row[COL['Town']] = f.town || '';
      row[COL['Suburb']] = f.suburb || '';
      row[COL['Street address']] = f.street || '';
      row[COL['Postal code']] = f.postal_code || '';
      row[COL['ID copy (private Drive file)']] = links['id_copy'] || '';
      row[COL['Photo holding ID (private Drive file)']] = links['id_selfie'] || '';
      row[COL['Applicant folder (private)']] = folder.getUrl();
      row[COL['Consent']] = 'Yes, ' + (data.consent_at || '');
      row[COL['Consent version']] = data.consent_version || '';
      row[COL['Possible duplicate']] = dupRef ? ('Matches ' + dupRef) : '';
      row[COL['Submission ID']] = sid;
      row[COL['Source']] = data.source || 'Website';

      // Plain-text cells: keeps leading zeros on phone numbers and stops anything typed from running as a formula.
      var target = sheet.getRange(sheet.getLastRow() + 1, 1, 1, HEADERS.length);
      target.setNumberFormat('@');
      target.setValues([row]);
    } catch (inner) {
      // Never leave ID files behind without a matching row.
      try { folder.setTrashed(true); } catch (x) {}
      throw inner;
    }

    return json_({ ok: true, ref: ref, duplicate: false });
  } catch (err) {
    console.error('register failed: ' + (err && err.message));   // no personal data in logs
    return json_({ ok: false, error: 'server' });
  } finally {
    if (locked) { try { lock.releaseLock(); } catch (x) {} }
  }
}

function getSheet_(props) {
  var ss = SpreadsheetApp.openById(props.getProperty('SHEET_ID'));
  var sheet = ss.getSheetByName(SHEET_NAME) || ss.insertSheet(SHEET_NAME);
  if (sheet.getLastRow() === 0) {
    sheet.getRange(1, 1, 1, HEADERS.length).setNumberFormat('@').setValues([HEADERS]).setFontWeight('bold');
    sheet.setFrozenRows(1);
  }
  return sheet;
}

function norm_(v) { return String(v || '').toLowerCase().replace(/[^a-z0-9@.]/g, ''); }
function safeName_(v) { return String(v).replace(/[^\p{L}\p{N} .'-]/gu, '').trim().substring(0, 60) || 'applicant'; }
function json_(obj) { return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON); }

/** Constant-time string compare, so response timing does not reveal the secret. */
function safeEqual_(a, b) {
  if (!a || !b || a.length !== b.length) return false;
  var diff = 0;
  for (var i = 0; i < a.length; i++) diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
  return diff === 0;
}
