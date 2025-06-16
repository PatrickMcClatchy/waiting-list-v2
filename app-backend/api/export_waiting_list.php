<?php
/**
 * Export Waiting List Handler
 * 
 * Creates two lists in the PDF:
 * - Public List: Position and Name only, displayed as two columns per page.
 * - Internal List: Full metadata with all variables as columns, including comments.
 */

session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include TCPDF library
require_once(__DIR__ . '/../lib/tcpdf/tcpdf.php');

// Extend TCPDF to create custom header and footer
class SAGAPDF extends TCPDF {
    private $sectionType = 'Public';
    private $sectionPageNumber = 1;
    private $sectionTotalPages = 1;
    
    public function setSectionInfo($type, $pageNumber, $totalPages) {
        $this->sectionType = $type;
        $this->sectionPageNumber = $pageNumber;
        $this->sectionTotalPages = $totalPages;
    }
    
    public function incrementSectionPage() {
        $this->sectionPageNumber++;
    }
    
    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->SetY($this->GetY() + 3);
        $this->Cell(0, 10, $this->sectionType . ' List - Page ' . $this->sectionPageNumber . '/' . $this->sectionTotalPages, 0, false, 'C');
        $this->Cell(0, 10, 'Generated: ' . date('Y-m-d H:i'), 0, false, 'R');
    }
}

try {
    require_once(__DIR__ . '/db_connect.php');
    $db = db_connect();
    $results = $db->query('SELECT * FROM waiting_list ORDER BY position ASC');
    
    $waitingList = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $waitingList[] = $row;
    }

    $pdf = new SAGAPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('SAGA Waiting List System');
    $pdf->SetAuthor('SAGA Admin');
    $pdf->SetTitle('Waiting List Export');
    $pdf->SetSubject('Current Waiting List');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->SetDefaultMonospacedFont('courier');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->setImageScale(1.25);

    // Calculate Public List pages needed
    $posWidth = 30;
    $nameWidth = 60;
    $gapWidth = 5;
    $rowHeight = 8;
    
    $availableHeight = 297 - 30 - 35 - 20; // A4 height minus margins and header/footer space
    $maxRowsPerPage = floor($availableHeight / $rowHeight) - 1; // -1 for header row
    $itemsPerPage = $maxRowsPerPage * 2; // Two columns per page
    $totalItems = count($waitingList);
    $publicTotalPages = ceil($totalItems / $itemsPerPage);

    // Public List
    $pdf->setSectionInfo('Public', 1, $publicTotalPages);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Public Waiting List', 0, 1, 'C');
    $pdf->Ln(5);

    $currentIndex = 0;

    while ($currentIndex < $totalItems) {
        // Add header row
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($posWidth, $rowHeight, 'Position', 1, 0, 'C');
        $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 0, 'C');
        $pdf->Cell($gapWidth, $rowHeight, '', 0, 0); // Gap between columns
        $pdf->Cell($posWidth, $rowHeight, 'Position', 1, 0, 'C');
        $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);

        // Store starting position for this page
        $pageStartIndex = $currentIndex;
        $itemsForThisPage = min($itemsPerPage, $totalItems - $currentIndex);
        $itemsPerColumn = ceil($itemsForThisPage / 2);

        // Fill rows on current page
        for ($row = 0; $row < $maxRowsPerPage; $row++) {
            // Left column entry (first half of items for this page)
            $leftIndex = $pageStartIndex + $row;
            if ($leftIndex < $pageStartIndex + $itemsPerColumn && $leftIndex < $totalItems) {
                $user = $waitingList[$leftIndex];
                $pdf->Cell($posWidth, $rowHeight, $user['position'], 1, 0, 'C');
                $pdf->Cell($nameWidth, $rowHeight, $user['name'], 1, 0, 'L');
            } else {
                // Empty left column
                $pdf->Cell($posWidth, $rowHeight, '', 1, 0, 'C');
                $pdf->Cell($nameWidth, $rowHeight, '', 1, 0, 'L');
            }

            // Gap between columns
            $pdf->Cell($gapWidth, $rowHeight, '', 0, 0);

            // Right column entry (second half of items for this page)
            $rightIndex = $pageStartIndex + $itemsPerColumn + $row;
            if ($rightIndex < $pageStartIndex + $itemsForThisPage && $rightIndex < $totalItems) {
                $user = $waitingList[$rightIndex];
                $pdf->Cell($posWidth, $rowHeight, $user['position'], 1, 0, 'C');
                $pdf->Cell($nameWidth, $rowHeight, $user['name'], 1, 1, 'L');
            } else {
                // Empty right column
                $pdf->Cell($posWidth, $rowHeight, '', 1, 0, 'C');
                $pdf->Cell($nameWidth, $rowHeight, '', 1, 1, 'L');
            }
        }

        // Move index forward by the number of items we processed on this page
        $currentIndex += $itemsForThisPage;

        // Add new page if there are more entries
        if ($currentIndex < $totalItems) {
            $pdf->incrementSectionPage();
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Public Waiting List (continued)', 0, 1, 'C');
            $pdf->Ln(5);
        }
    }

    // Calculate Internal List pages needed
    $internalRowHeight = 6;
    $internalAvailableHeight = 297 - 30 - 35 - 20; // A4 height minus margins and header/footer space
    $internalMaxRowsPerPage = floor($internalAvailableHeight / $internalRowHeight) - 1; // -1 for header row
    $internalTotalPages = ceil($totalItems / $internalMaxRowsPerPage);

    // Internal List
    $pdf->setSectionInfo('Internal', 1, $internalTotalPages);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Internal Waiting List', 0, 1, 'C');
    $pdf->Ln(5);

    // Define table dimensions - use full width available (180mm usable width)
    $posWidth = 15;
    $nameWidth = 35;
    $contactWidth = 45;
    $langWidth = 20;
    $timeWidth = 35;
    $commentWidth = 30; // Reduced to fit exactly
    $rowHeight = 6;

    // Total width: 15+35+45+20+35+30 = 180mm (fills available width)

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell($posWidth, $rowHeight, 'Pos', 1, 0, 'C');
    $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 0, 'C');
    $pdf->Cell($contactWidth, $rowHeight, 'Contact', 1, 0, 'C');
    $pdf->Cell($langWidth, $rowHeight, 'Lang', 1, 0, 'C');
    $pdf->Cell($timeWidth, $rowHeight, 'Time', 1, 0, 'C');
    $pdf->Cell($commentWidth, $rowHeight, 'Comment', 1, 1, 'C');

    $pdf->SetFont('helvetica', '', 8);
    
    $internalCurrentRow = 0;
    foreach ($waitingList as $user) {
        // Check if we need a new page
        if ($internalCurrentRow > 0 && $internalCurrentRow % $internalMaxRowsPerPage == 0) {
            $pdf->incrementSectionPage();
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Internal Waiting List (continued)', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Add header row on new page
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($posWidth, $rowHeight, 'Pos', 1, 0, 'C');
            $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 0, 'C');
            $pdf->Cell($contactWidth, $rowHeight, 'Contact', 1, 0, 'C');
            $pdf->Cell($langWidth, $rowHeight, 'Lang', 1, 0, 'C');
            $pdf->Cell($timeWidth, $rowHeight, 'Time', 1, 0, 'C');
            $pdf->Cell($commentWidth, $rowHeight, 'Comment', 1, 1, 'C');
            $pdf->SetFont('helvetica', '', 8);
        }
        
        $timeFormatted = date('Y-m-d H:i', $user['time']);
        
        // Calculate the height needed for each cell based on text content
        $nameHeight = $pdf->getStringHeight($nameWidth, $user['name']);
        $contactHeight = $pdf->getStringHeight($contactWidth, $user['email_or_phone']);
        $commentHeight = $pdf->getStringHeight($commentWidth, $user['comment']);
        
        // Use the maximum height needed, but at least the minimum row height
        $cellHeight = max($rowHeight, $nameHeight, $contactHeight, $commentHeight);
        
        // Store current Y position
        $currentY = $pdf->GetY();
        
        // Draw all cells with the same calculated height
        $pdf->MultiCell($posWidth, $cellHeight, $user['position'], 1, 'C', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($nameWidth, $cellHeight, $user['name'], 1, 'L', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($contactWidth, $cellHeight, $user['email_or_phone'], 1, 'L', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($langWidth, $cellHeight, $user['language'], 1, 'C', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($timeWidth, $cellHeight, $timeFormatted, 1, 'C', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($commentWidth, $cellHeight, $user['comment'], 1, 'L', false, 1, '', '', true, 0, false, true, $cellHeight, 'M');
        
        $internalCurrentRow++;
    }

    $pdf->Output('saga_waiting_list_' . date('Y-m-d') . '.pdf', 'I');

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}