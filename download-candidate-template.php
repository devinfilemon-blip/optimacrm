<?php
include 'layouts/session.php';
include 'xlsx-helper.php';

xlsx_download_template('candidate-bulk-upload-template.xlsx', [
    'Candidate Name',
    'Mobile Number',
    'Type (T/NT)',
    'Education',
    'Experience',
    'Current Company',
    'Address',
    'Source',
    'Remark',
]);
