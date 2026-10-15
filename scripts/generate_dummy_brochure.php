<?php
/**
 * Creates a minimal one-page PDF for WhatsApp chatbot brochure testing.
 * php scripts/generate_dummy_brochure.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$dir = $root . '/assets/uploads/brochures';
$file = $dir . '/digital-creatorss-brochure.pdf';

if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    fwrite(STDERR, "Could not create directory: {$dir}\n");
    exit(1);
}

$lines = [
    'Digital Creatorss',
    'Dummy company brochure (for testing only)',
    '',
    'Replace this PDF with your real portfolio.',
    'Path: assets/uploads/brochures/digital-creatorss-brochure.pdf',
    'Or set CHATBOT_BROCHURE_PATH in server config.',
    '',
    'Services: Web Development | Mobile Apps',
    'WhatsApp CRM & Automation | Digital Marketing',
];

$stream = "BT\n/F1 16 Tf\n72 720 Td\n";
foreach ($lines as $i => $line) {
    if ($i > 0) {
        $stream .= "0 -22 Td\n";
    }
    $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
    $stream .= "({$safe}) Tj\n";
}
$stream .= "ET\n";

$len = strlen($stream);

$objects = [];
$objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
$objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
$objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] "
    . "/Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
$objects[] = "4 0 obj\n<< /Length {$len} >>\nstream\n{$stream}endstream\nendobj\n";
$objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

$pdf = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $obj) {
    $offsets[] = strlen($pdf);
    $pdf .= $obj;
}

$xrefPos = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
}
$pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
$pdf .= "startxref\n{$xrefPos}\n%%EOF\n";

if (file_put_contents($file, $pdf) === false) {
    fwrite(STDERR, "Could not write {$file}\n");
    exit(1);
}

echo "Created: {$file} (" . filesize($file) . " bytes)\n";
echo "Upload to Meta happens on first successful brochure send (or re-upload on media expiry).\n";
