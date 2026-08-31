<?php

namespace App\Services;

/**
 * Minimal XLSX (Office Open XML) builder without PhpSpreadsheet.
 */
class SimpleXlsxBuilder
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function build(array $headers, array $rows, string $sheetName = 'Sheet1'): string
    {
        $sheetName = $this->safeSheetName($sheetName);
        $sheetRows = [];
        $sheetRows[] = $this->xlsxRow(1, $headers, true);
        foreach ($rows as $i => $row) {
            $sheetRows[] = $this->xlsxRow($i + 2, $row, false);
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'</worksheet>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8').'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';

        $tmp = tempnam(sys_get_temp_dir(), 'yfdxlsx');
        if ($tmp === false) {
            throw new \RuntimeException('Tidak bisa membuat file sementara untuk export Excel.');
        }

        $zip = new \ZipArchive;
        if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new \RuntimeException('Tidak bisa menulis file Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);
        if ($binary === false) {
            throw new \RuntimeException('Gagal membaca hasil export Excel.');
        }

        return $binary;
    }

    /**
     * @param  list<string|int|float|null>  $cells
     */
    private function xlsxRow(int $rowNumber, array $cells, bool $asText): string
    {
        $xml = '<row r="'.$rowNumber.'">';
        foreach ($cells as $index => $value) {
            $ref = $this->xlsxColumnLetter($index + 1).$rowNumber;
            if (! $asText && is_int($value)) {
                $xml .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';

                continue;
            }
            if (! $asText && is_float($value)) {
                $xml .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';

                continue;
            }
            $text = htmlspecialchars(str_replace(["\r\n", "\r", "\n"], ' ', (string) ($value ?? '')), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.$text.'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private function xlsxColumnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private function safeSheetName(string $name): string
    {
        $clean = trim(preg_replace('/[\\\\\/\\?\\*\\[\\]:]/', '', $name) ?? 'Sheet1');
        if ($clean === '') {
            $clean = 'Sheet1';
        }

        return mb_substr($clean, 0, 31);
    }
}
