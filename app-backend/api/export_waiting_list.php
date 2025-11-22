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
        $this->Cell(90, 10, $this->sectionType . ' List - Page ' . $this->sectionPageNumber . '/' . $this->sectionTotalPages, 0, false, 'L');
        $this->Cell(90, 10, 'Generated: ' . date('Y-m-d H:i'), 0, false, 'R');
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
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->setImageScale(1.25);

    // PUBLIC LIST - Two Column Layout
    $posWidth = 25;
    $nameWidth = 60;
    $gapWidth = 10;
    $rowHeight = 7;
    
    // Calculate how many rows fit per page (this determines left column size)
    $headerSpace = 25; // Space for title and spacing
    $footerSpace = 20; // Footer margin
    $topMargin = 15;
    $availableHeight = 297 - $topMargin - $headerSpace - $footerSpace;
    $maxRowsPerColumn = floor($availableHeight / $rowHeight) - 1; // -1 for header row
    $totalItems = count($waitingList);
    
    // Each page can hold maxRowsPerColumn items in EACH column
    // So left column gets maxRowsPerColumn, right column gets maxRowsPerColumn
    $itemsPerPage = $maxRowsPerColumn * 2;
    $publicTotalPages = ceil($totalItems / $itemsPerPage);

    // Public List
    $pdf->setSectionInfo('Public', 1, $publicTotalPages);
    $currentIndex = 0;
    $currentPublicPage = 1;

    while ($currentIndex < $totalItems) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Public Waiting List' . ($currentPublicPage > 1 ? ' (continued)' : ''), 0, 1, 'C');
        $pdf->Ln(5);

        // Header row
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($posWidth, $rowHeight, 'Position', 1, 0, 'C');
        $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 0, 'L');
        $pdf->Cell($gapWidth, $rowHeight, '', 0, 0);
        $pdf->Cell($posWidth, $rowHeight, 'Position', 1, 0, 'C');
        $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        // Fill exactly maxRowsPerColumn rows
        // Left column: positions currentIndex to currentIndex + maxRowsPerColumn - 1
        // Right column: positions currentIndex + maxRowsPerColumn to currentIndex + (maxRowsPerColumn * 2) - 1
        for ($row = 0; $row < $maxRowsPerColumn; $row++) {
            // Left column entry
            $leftIndex = $currentIndex + $row;
            if ($leftIndex < $totalItems) {
                $user = $waitingList[$leftIndex];
                $pdf->Cell($posWidth, $rowHeight, $user['position'], 1, 0, 'C');
                $pdf->Cell($nameWidth, $rowHeight, $user['name'], 1, 0, 'L');
            } else {
                // Empty left column cell
                $pdf->Cell($posWidth, $rowHeight, '', 1, 0, 'C');
                $pdf->Cell($nameWidth, $rowHeight, '', 1, 0, 'L');
            }

            // Gap
            $pdf->Cell($gapWidth, $rowHeight, '', 0, 0);

            // Right column entry (starts after left column is full)
            $rightIndex = $currentIndex + $maxRowsPerColumn + $row;
            if ($rightIndex < $totalItems) {
                $user = $waitingList[$rightIndex];
                $pdf->Cell($posWidth, $rowHeight, $user['position'], 1, 0, 'C');
                $pdf->Cell($nameWidth, $rowHeight, $user['name'], 1, 1, 'L');
            } else {
                // Empty right column cell
                $pdf->Cell($posWidth, $rowHeight, '', 1, 0, 'C');
                $pdf->Cell($nameWidth, $rowHeight, '', 1, 1, 'L');
            }
        }

        // Move to next page's starting index
        $currentIndex += $itemsPerPage;
        $currentPublicPage++;
        
        if ($currentIndex < $totalItems) {
            $pdf->incrementSectionPage();
        }
    }

    // INTERNAL LIST - Single Table Layout
    $posWidth = 18;
    $nameWidth = 38;
    $contactWidth = 48;
    $langWidth = 22;
    $timeWidth = 32;
    $commentWidth = 22;
    $rowHeight = 6;

    // Calculate internal list pages
    $internalHeaderSpace = 25;
    $internalAvailableHeight = 297 - $topMargin - $internalHeaderSpace - $footerSpace;
    $internalMaxRowsPerPage = floor($internalAvailableHeight / $rowHeight);
    $internalTotalPages = ceil($totalItems / $internalMaxRowsPerPage);

    // Internal List
    $pdf->setSectionInfo('Internal', 1, $internalTotalPages);
    $internalCurrentRow = 0;
    $currentInternalPage = 1;

    for ($i = 0; $i < $totalItems; $i++) {
        // Add new page and header when needed
        if ($internalCurrentRow == 0 || $internalCurrentRow % $internalMaxRowsPerPage == 0) {
            if ($i > 0) {
                $pdf->incrementSectionPage();
                $currentInternalPage++;
            }
            
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Internal Waiting List' . ($currentInternalPage > 1 ? ' (continued)' : ''), 0, 1, 'C');
            $pdf->Ln(5);
            
            // Header row
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($posWidth, $rowHeight, 'Pos', 1, 0, 'C');
            $pdf->Cell($nameWidth, $rowHeight, 'Name', 1, 0, 'C');
            $pdf->Cell($contactWidth, $rowHeight, 'Contact', 1, 0, 'C');
            $pdf->Cell($langWidth, $rowHeight, 'Language', 1, 0, 'C');
            $pdf->Cell($timeWidth, $rowHeight, 'Time', 1, 0, 'C');
            $pdf->Cell($commentWidth, $rowHeight, 'Comment', 1, 1, 'C');
            
            $internalCurrentRow = 0;
        }
        
        $user = $waitingList[$i];
        $timeFormatted = date('Y-m-d H:i', $user['time']);
        
        // Use smaller font for data
        $pdf->SetFont('helvetica', '', 7);
        
        // Calculate cell heights for multi-line content
        $nameHeight = $pdf->getStringHeight($nameWidth, $user['name']);
        $contactHeight = $pdf->getStringHeight($contactWidth, $user['email_or_phone'] ?: '-');
        $commentHeight = $pdf->getStringHeight($commentWidth, $user['comment'] ?: '-');
        
        $cellHeight = max($rowHeight, $nameHeight, $contactHeight, $commentHeight);
        
        // Draw cells
        $pdf->MultiCell($posWidth, $cellHeight, $user['position'], 1, 'C', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($nameWidth, $cellHeight, $user['name'], 1, 'L', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($contactWidth, $cellHeight, $user['email_or_phone'] ?: '-', 1, 'L', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($langWidth, $cellHeight, $user['language'], 1, 'C', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($timeWidth, $cellHeight, $timeFormatted, 1, 'C', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
        $pdf->MultiCell($commentWidth, $cellHeight, $user['comment'] ?: '-', 1, 'L', false, 1, '', '', true, 0, false, true, $cellHeight, 'M');
        
        $internalCurrentRow++;
    }

    $pdf->Output('saga_waiting_list_' . date('Y-m-d') . '.pdf', 'I');

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}