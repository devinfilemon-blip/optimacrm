<?php
// Minimal, dependency-free .xlsx reader/writer for the Candidate bulk-upload
// feature. This project has no Composer/PhpSpreadsheet, so this file only
// implements exactly what that feature needs (a single flat sheet of text
// and number cells) rather than the full OOXML spec.

function xlsx_col_letters_to_index($colLetters) {
    $col = 0;
    $len = strlen($colLetters);
    for ($i = 0; $i < $len; $i++) {
        $col = $col * 26 + (ord($colLetters[$i]) - ord('A') + 1);
    }
    return $col - 1;
}

function xlsx_index_to_col_letters($index) {
    $index++;
    $col = '';
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $col = chr(65 + $mod) . $col;
        $index = intdiv($index - 1, 26);
    }
    return $col;
}

/**
 * Reads the workbook's first worksheet into a 0-indexed array of rows, each
 * row a 0-indexed array of string cell values (blank cells included so
 * column positions line up with the header row). Throws RuntimeException
 * with a user-facing message if the file isn't a readable .xlsx.
 */
function xlsx_read_first_sheet($filePath) {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Bulk upload is not available on this server (missing PHP zip extension). Please contact your administrator.');
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new RuntimeException('The uploaded file is not a valid Excel (.xlsx) file.');
    }

    $sharedStrings = [];
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss !== false) {
        $sxml = @simplexml_load_string($ss);
        if ($sxml !== false) {
            foreach ($sxml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } else {
                    $text = '';
                    foreach ($si->r as $r) { $text .= (string) $r->t; }
                    $sharedStrings[] = $text;
                }
            }
        }
    }

    $sheetPath = 'xl/worksheets/sheet1.xml';
    $wb = $zip->getFromName('xl/workbook.xml');
    $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($wb !== false && $rels !== false) {
        $wbXml = @simplexml_load_string($wb);
        $relsXml = @simplexml_load_string($rels);
        if ($wbXml && $relsXml && isset($wbXml->sheets->sheet[0])) {
            $rIdAttrs = $wbXml->sheets->sheet[0]->attributes('r', true);
            $rId = isset($rIdAttrs['id']) ? (string) $rIdAttrs['id'] : '';
            foreach ($relsXml->Relationship as $rel) {
                if ((string) $rel['Id'] === $rId) {
                    $target = ltrim((string) $rel['Target'], '/');
                    $sheetPath = strpos($target, 'xl/') === 0 ? $target : 'xl/' . $target;
                    break;
                }
            }
        }
    }

    $sheetXmlStr = $zip->getFromName($sheetPath);
    $zip->close();
    if ($sheetXmlStr === false) {
        throw new RuntimeException('The uploaded file is not a valid Excel (.xlsx) file.');
    }

    $sheetXml = @simplexml_load_string($sheetXmlStr);
    if ($sheetXml === false || !isset($sheetXml->sheetData)) {
        throw new RuntimeException('The uploaded file is not a valid Excel (.xlsx) file.');
    }

    $rows = [];
    foreach ($sheetXml->sheetData->row as $rowXml) {
        $rowIndex = (int) $rowXml['r'];
        if ($rowIndex <= 0) $rowIndex = count($rows) + 1;
        $rowData = [];
        foreach ($rowXml->c as $c) {
            $ref = (string) $c['r'];
            preg_match('/^([A-Z]+)/', $ref, $m);
            $colIndex = xlsx_col_letters_to_index($m[1] ?? 'A');
            $type = (string) $c['t'];
            if ($type === 's') {
                $idx = isset($c->v) ? (int) $c->v : -1;
                $value = $sharedStrings[$idx] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = isset($c->is->t) ? (string) $c->is->t : '';
            } else {
                $value = isset($c->v) ? (string) $c->v : '';
            }
            $rowData[$colIndex] = $value;
        }
        if (!empty($rowData)) {
            $maxCol = max(array_keys($rowData));
            $normalized = [];
            for ($i = 0; $i <= $maxCol; $i++) { $normalized[] = $rowData[$i] ?? ''; }
            $rows[$rowIndex] = $normalized;
        }
    }
    ksort($rows);
    return array_values($rows);
}

/** Accepts either a plain YYYY-MM-DD-ish string or a raw Excel date serial number. */
function xlsx_parse_maybe_date($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') return null;
    if (preg_match('/^\d+(\.\d+)?$/', $raw)) {
        $serial = (float) $raw;
        if ($serial > 20000 && $serial < 60000) {
            $unixDays = $serial - 25569; // Excel's 1900-01-00 epoch to Unix epoch
            return gmdate('Y-m-d', (int) round($unixDays * 86400));
        }
    }
    $ts = strtotime($raw);
    if ($ts === false) return null;
    return date('Y-m-d', $ts);
}

/** Builds a minimal one-sheet .xlsx with a single header row and streams it for download. */
function xlsx_download_template($filename, array $headers) {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('Bulk upload is not available on this server (missing PHP zip extension). Please contact your administrator.');
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    $zip->open($tmpFile, ZipArchive::OVERWRITE);

    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
        '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
        '</Types>');

    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
        '</Relationships>');

    $zip->addFromString('xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
        '</Relationships>');

    $zip->addFromString('xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
        '<sheets><sheet name="Candidates" sheetId="1" r:id="rId1"/></sheets>' .
        '</workbook>');

    $cells = [];
    foreach ($headers as $i => $h) {
        $colLetter = xlsx_index_to_col_letters($i);
        $escaped = htmlspecialchars((string) $h, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $cells[] = '<c r="' . $colLetter . '1" t="inlineStr"><is><t xml:space="preserve">' . $escaped . '</t></is></c>';
    }
    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
        '<sheetData><row r="1">' . implode('', $cells) . '</row></sheetData>' .
        '</worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    @unlink($tmpFile);
    exit;
}
