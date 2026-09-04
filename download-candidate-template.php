<?php
include 'layouts/session.php';
include 'xlsx-helper.php';

xlsx_download_template('candidate-bulk-upload-template.xlsx', [
    'Candidate Name',
    'Mobile Number',
    'Post',
    'Company Name',
    'Type (T/NT)',
    'Education',
    'Experience',
    'Current Company',
    'Address',
    'Annual CTC',
    'Joining Date (YYYY-MM-DD)',
    'Joining Status',
    'Source',
    'Worked By',
    'Remark',
]);
