<?php
/**
 * Export Backup List Handler
 * 
 * Creates a nicely formatted PDF export of a backup waiting list with both Public and Internal lists.
 */

session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if a backup file is specified
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No backup file specified']);
    exit;
}

// Sanitize the filename to prevent directory traversal
$backupFile = basename($_GET['file']);
$backupPath = __DIR__ . '/../backups/' . $backupFile;

// Check if the file exists
if (!file_exists($backupPath)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Backup file not found']);
    exit;
}

try {
    // Open the backup database
    $backupDb = new SQLite3($backupPath);
    $results = $backupDb->query('SELECT * FROM waiting_list ORDER BY position ASC');
    
    $waitingList = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $waitingList[] = $row;
    }

    // Create PDF
    require_once(__DIR__ . '/../lib/tcpdf/tcpdf.php');
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('SAGA Waiting List System');
    $pdf->SetAuthor('SAGA Admin');
    $pdf->SetTitle('Backup Waiting List Export');
    $pdf->SetSubject('Backup Export');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->SetDefaultMonospacedFont('courier');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->setImageScale(1.25);

    // Public List
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Public Waiting List (Backup)', 0, 1, 'C');
    $pdf->Ln(5);

    // Define table dimensions for Public List
    $posWidth = 30;
    $nameWidth = 60;
    $gapWidth = 5;
    $rowHeight = 8;

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($posWidth, $rowHeight, 'Position', 1, 0, 'C');
    $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 0, 'C');
    $pdf->Cell($gapWidth, $rowHeight, '', 0, 0); // Gap between columns
    $pdf->Cell($posWidth, $rowHeight, 'Position', 1, 0, 'C');
    $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 1, 'C');

    $pdf->SetFont('helvetica', '', 9);

    $itemsPerColumn = floor(($pdf->getPageHeight() - 30) / $rowHeight);
    $currentIndex = 0;

    while ($currentIndex < count($waitingList)) {
        for ($i = 0; $i < $itemsPerColumn && $currentIndex < count($waitingList); $i++) {
            $leftUser = $waitingList[$currentIndex] ?? ['position' => '', 'name' => ''];
            $rightUser = $waitingList[$currentIndex + $itemsPerColumn] ?? ['position' => '', 'name' => ''];

            $pdf->Cell($posWidth, $rowHeight, $leftUser['position'], 1, 0, 'C');
            $pdf->Cell($nameWidth, $rowHeight, $leftUser['name'], 1, 0, 'L');
            $pdf->Cell($gapWidth, $rowHeight, '', 0, 0); // Gap between columns
            $pdf->Cell($posWidth, $rowHeight, $rightUser['position'], 1, 0, 'C');
            $pdf->Cell($nameWidth, $rowHeight, $rightUser['name'], 1, 1, 'L');
            $currentIndex++;
        }

        if ($currentIndex < count($waitingList)) {
            $pdf->AddPage();
        }
    }

    // Internal List
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Internal Waiting List (Backup)', 0, 1, 'C');
    $pdf->Ln(5);

    // Define table dimensions for Internal List
    $posWidth = 15;
    $nameWidth = 35;
    $contactWidth = 45;
    $langWidth = 20;
    $timeWidth = 35;
    $commentWidth = 30;
    $rowHeight = 6;

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell($posWidth, $rowHeight, 'Pos', 1, 0, 'C');
    $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 0, 'C');
    $pdf->Cell($contactWidth, $rowHeight, 'Contact', 1, 0, 'C');
    $pdf->Cell($langWidth, $rowHeight, 'Lang', 1, 0, 'C');
    $pdf->Cell($timeWidth, $rowHeight, 'Time', 1, 0, 'C');
    $pdf->Cell($commentWidth, $rowHeight, 'Comment', 1, 1, 'C');

    $pdf->SetFont('helvetica', '', 8);

    foreach ($waitingList as $user) {
        $timeFormatted = date('Y-m-d H:i', $user['time']);
        $nameHeight = $pdf->getStringHeight($nameWidth, $user['name']);
        $contactHeight = $pdf->getStringHeight($contactWidth, $user['email_or_phone']);
        $commentHeight = $pdf->getStringHeight($commentWidth, $user['comment']);
        $cellHeight = max($rowHeight, $nameHeight, $contactHeight, $commentHeight);

        $pdf->MultiCell($posWidth, $cellHeight, $user['position'], 1, 'C', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($nameWidth, $cellHeight, $user['name'], 1, 'L', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($contactWidth, $cellHeight, $user['email_or_phone'], 1, 'L', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($langWidth, $cellHeight, $user['language'], 1, 'C', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($timeWidth, $cellHeight, $timeFormatted, 1, 'C', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($commentWidth, $cellHeight, $user['comment'], 1, 'L', false, 1, '', '', true, 0, false, true, $cellHeight, 'M');
    }

    $pdf->Output('saga_backup_' . date('Y-m-d') . '.pdf', 'I');

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}