<?php
// ============================================================
// SMTP SETTINGS: fill these in. Never commit real passwords to Git.
// Common ports: 587 with 'tls' (STARTTLS), or 465 with 'ssl'.
// ============================================================
return [
  'smtp_host'     => 'mail.ourbeautyproducts.co.za',          // e.g. mail.YOURDOMAIN.co.za or smtp.office365.com
  'smtp_port'     => 587,
  'smtp_secure'   => 'tls',                // 'tls' or 'ssl'
  'smtp_username' => 'info@ourbeautyproducts.co.za',      // usually the full mailbox address
  'smtp_password' => 'OurBeautyProducts@2026',      // the mailbox password

  'from_email'    => 'info@ourbeautyproducts.co.za',         // must be a mailbox your SMTP account may send as
  'from_name'     => 'Our Beauty Products Website',

  'to_email'      => 'rethabilemahlathe23@gmail.com',    // where enquiries should arrive
  'to_name'       => 'Our Beauty Products',

  'subject_prefix'=> 'Website enquiry',
];
