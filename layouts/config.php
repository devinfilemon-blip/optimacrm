<?php

/* Database credentials */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'dbcrm_optima');

define('APP_NAME', 'Optima Jobs');
define('APP_TAGLINE', 'Recruitment & Staffing CRM');
define('APP_LOGO', 'assets/images/optima-logo.png');
define('APP_ICON', 'assets/images/optima-logo-mark.png');

/* Billing details printed on generated tax invoices — edit here if these ever change */
define('BILLER_NAME', 'Optima Services');
define('BILLER_ADDRESS_LINE1', '6/1110, Near Mahalaxmi Ceramics, Mahasatta Chowk,');
define('BILLER_ADDRESS_LINE2', 'Ichalkaranji, Dist: Kolhapur 416115');
define('BILLER_CONTACT', '9923968262 / 9960480227');
define('BILLER_EMAIL', 'hr@optimajobs.in');
define('BILLER_GSTIN', '27ARUPB9253D1ZK');
define('BILLER_SAC_CODE', '998312');
define('BILLER_BANK_NAME', 'Union Bank Of India');
define('BILLER_BANK_ACCOUNT_NO', '3770050105p60251');
define('BILLER_BANK_BRANCH', 'Ichalkaranji');
define('BILLER_BANK_IFSC', 'UBIN0537705');
define('BILLER_PAYMENT_TERMS_DAYS', 8);
define('BILLER_JURISDICTION', 'ICHALKARANJI');
define('BILLER_STAMP', 'assets/images/optima-stamp.png');

/* Signatory printed on generated Company Agreement documents */
define('BILLER_SIGNATORY_SHORT_NAME', 'Amit');
define('BILLER_SIGNATORY_NAME', 'Mr. Amit Badave');
define('BILLER_SIGNATORY_DESIGNATION', 'Proprietor');

// PHP 8.1+ made mysqli throw mysqli_sql_exception on error by default: every
// mysqli_stmt_errno()/mysqli_connect_error() check already throughout this
// app (including the "already exists" duplicate-key fallbacks) was written
// assuming the pre-8.1 behavior — a failed call returning false/null rather
// than throwing. Restore that so those checks actually run instead of a raw
// fatal error (with a full stack trace) reaching the client.
mysqli_report(MYSQLI_REPORT_OFF);

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

mysqli_set_charset($link, 'utf8mb4');
