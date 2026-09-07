<?php
include 'layouts/session.php';
include 'xlsx-helper.php';

xlsx_download_template('candidate-bulk-upload-template.xlsx', [
    'Name',
    'Mobile No',
    'Email ID',
    'Gender',
    'Address',
    'Education',
    'Suitable For',
    'Current Company',
    'Designation',
    'Experience',
    'Current CTC',
    'Expected CTC',
    'Notice Period',
    'Reference Number',
    'Remark',
    'Date Added (YYYY-MM-DD)',
]);
