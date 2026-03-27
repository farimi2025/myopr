<?php
// ============================================================
// DocxHelper — Pure PHP DOCX Generator (OpenXML + ZipArchive)
// Tidak memerlukan Composer atau library luaran
// ============================================================

class DocxHelper {
    private string $title = 'Dokumen';
    private array $body = [];
    private array $rels = [];

    public function setTitle(string $title): void {
        $this->title = $title;
    }

    public function addHeading(string $text, int $level = 1): void {
        $style = 'Heading' . min($level, 4);
        $this->body[] = '<w:p><w:pPr><w:pStyle w:val="' . $style . '"/></w:pPr><w:r><w:t>' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addParagraph(string $text, bool $bold = false): void {
        if ($text === '') {
            $this->body[] = '<w:p/>';
            return;
        }
        $rPr = $bold ? '<w:rPr><w:b/></w:rPr>' : '';
        $this->body[] = '<w:p><w:r>' . $rPr . '<w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addTable(array $rows, bool $hasHeader = false): void {
        $xml = '<w:tbl>
            <w:tblPr>
                <w:tblStyle w:val="TableGrid"/>
                <w:tblW w:w="9360" w:type="dxa"/>
                <w:tblBorders>
                    <w:top w:val="single" w:sz="4" w:color="CBD5E1"/>
                    <w:left w:val="single" w:sz="4" w:color="CBD5E1"/>
                    <w:bottom w:val="single" w:sz="4" w:color="CBD5E1"/>
                    <w:right w:val="single" w:sz="4" w:color="CBD5E1"/>
                    <w:insideH w:val="single" w:sz="4" w:color="CBD5E1"/>
                    <w:insideV w:val="single" w:sz="4" w:color="CBD5E1"/>
                </w:tblBorders>
                <w:tblCellMar>
                    <w:top w:w="80" w:type="dxa"/>
                    <w:left w:w="120" w:type="dxa"/>
                    <w:bottom w:w="80" w:type="dxa"/>
                    <w:right w:w="120" w:type="dxa"/>
                </w:tblCellMar>
            </w:tblPr>';

        foreach ($rows as $ri => $row) {
            $isHeader = $hasHeader && $ri === 0;
            $xml .= '<w:tr>';
            if ($isHeader) {
                $xml .= '<w:trPr><w:tblHeader/></w:trPr>';
            }
            foreach ($row as $ci => $cell) {
                $bold = ($isHeader || (!$hasHeader && $ci === 0));
                $shading = $isHeader ? '<w:shd w:val="clear" w:color="auto" w:fill="DBEAFE"/>' : ($ci === 0 ? '<w:shd w:val="clear" w:color="auto" w:fill="F8FAFC"/>' : '');
                $bTag = $bold ? '<w:b/>' : '';
                $cellVal = is_array($cell) ? implode(', ', $cell) : (string)$cell;
                // Handle multiline
                $lines = explode("\n", $cellVal);
                $cellContent = '';
                foreach ($lines as $li => $line) {
                    if ($li > 0) $cellContent .= '<w:br/>';
                    $cellContent .= '<w:t xml:space="preserve">' . $this->esc(trim($line)) . '</w:t>';
                }
                $xml .= '<w:tc><w:tcPr>' . $shading . '</w:tcPr>
                    <w:p><w:r><w:rPr>' . $bTag . '<w:sz w:val="20"/></w:rPr>' . $cellContent . '</w:r></w:p>
                </w:tc>';
            }
            $xml .= '</w:tr>';
        }
        $xml .= '</w:tbl>';
        $this->body[] = $xml;
        $this->body[] = '<w:p/>';
    }

    public function download(string $filename): never {
        $content = $this->build();

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        echo $content;
        exit;
    }

    private function esc(string $text): string {
        return htmlspecialchars($text, ENT_XML1, 'UTF-8');
    }

    private function build(): string {
        if (!class_exists('ZipArchive')) {
            die('ZipArchive extension diperlukan untuk export DOCX. Sila pasang php-zip.');
        }

        $bodyXml = implode("\n", $this->body);

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>';

        // _rels/.rels
        $dotRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
</Relationships>';

        // word/_rels/document.xml.rels
        $wordRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';

        // word/document.xml
        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
    xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
    xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
    xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math">
    <w:body>
        ' . $bodyXml . '
        <w:sectPr>
            <w:pgSz w:w="11906" w:h="16838"/>
            <w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="708" w:footer="708" w:gutter="0"/>
        </w:sectPr>
    </w:body>
</w:document>';

        // word/styles.xml (minimal)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
    xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <w:docDefaults>
        <w:rPrDefault><w:rPr>
            <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>
            <w:sz w:val="22"/><w:szCs w:val="22"/>
        </w:rPr></w:rPrDefault>
    </w:docDefaults>
    <w:style w:type="paragraph" w:styleId="Normal">
        <w:name w:val="Normal"/>
    </w:style>
    <w:style w:type="paragraph" w:styleId="Heading1">
        <w:name w:val="heading 1"/>
        <w:pPr><w:spacing w:after="160"/></w:pPr>
        <w:rPr><w:b/><w:color w:val="1D4ED8"/><w:sz w:val="32"/></w:rPr>
    </w:style>
    <w:style w:type="paragraph" w:styleId="Heading2">
        <w:name w:val="heading 2"/>
        <w:pPr><w:spacing w:after="120" w:before="200"/></w:pPr>
        <w:rPr><w:b/><w:color w:val="2563EB"/><w:sz w:val="26"/></w:rPr>
    </w:style>
    <w:style w:type="paragraph" w:styleId="Heading3">
        <w:name w:val="heading 3"/>
        <w:pPr><w:spacing w:after="80" w:before="160"/></w:pPr>
        <w:rPr><w:b/><w:color w:val="374151"/><w:sz w:val="24"/></w:rPr>
    </w:style>
    <w:style w:type="paragraph" w:styleId="Heading4">
        <w:name w:val="heading 4"/>
        <w:rPr><w:b/><w:sz w:val="22"/></w:rPr>
    </w:style>
    <w:style w:type="table" w:styleId="TableGrid">
        <w:name w:val="Table Grid"/>
    </w:style>
</w:styles>';

        // docProps/core.xml
        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:dcterms="http://purl.org/dc/terms/">
    <dc:title>' . $this->esc($this->title) . '</dc:title>
    <dc:creator>Sistem Pengurusan Sekolah</dc:creator>
    <dcterms:created xsi:type="dcterms:W3CDTF" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . date('Y-m-d\TH:i:s\Z') . '</dcterms:created>
</cp:coreProperties>';

        // Build ZIP
        $tmpFile = tempnam(sys_get_temp_dir(), 'docx_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $dotRels);
        $zip->addFromString('word/_rels/document.xml.rels', $wordRels);
        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/styles.xml', $styles);
        $zip->addFromString('docProps/core.xml', $core);
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $content;
    }
}
