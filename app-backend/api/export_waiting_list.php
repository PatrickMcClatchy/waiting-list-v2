<?php
/**
 * Export Waiting List Handler
 * 
 * Creates a nicely formatted PDF export of the waiting list
 * with empty rows at the end for manual additions
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
    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Draw a line
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        // Add some padding
        $this->SetY($this->GetY() + 3);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
        // Date on the right
        $this->Cell(0, 10, 'Generated: ' . date('Y-m-d H:i'), 0, false, 'R');
    }
}

try {
    // Include the database connection helper
    require_once(__DIR__ . '/db_connect.php');
    
    // Connect to the database
    $db = db_connect();
    
    // Query all users in the waiting list, ordered by position
    $results = $db->query('SELECT * FROM waiting_list ORDER BY position ASC');
    
    $waitingList = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $waitingList[] = $row;
    }

    // Create new PDF document using our extended class
    $pdf = new SAGAPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('SAGA Waiting List System');
    $pdf->SetAuthor('SAGA Admin');
    $pdf->SetTitle('Waiting List Export');
    $pdf->SetSubject('Current Waiting List');
    
    // Remove default header
    $pdf->setPrintHeader(false);
    // Enable footer
    $pdf->setPrintFooter(true);
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont('courier');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Set image scale factor
    $pdf->setImageScale(1.25);
    
    // Add a page
    $pdf->AddPage();
    
    // Define colors
    $headerBgColor = [230, 230, 230]; // Light gray
    $headerTextColor = [50, 50, 50]; // Dark gray
    $borderColor = [200, 200, 200]; // Medium gray
    $rowColor1 = [255, 255, 255]; // White
    $rowColor2 = [245, 245, 245]; // Very light gray
    $titleColor = [70, 70, 70]; // Dark gray for title
    
    // Set title with a nice box
    $pdf->SetFillColor($headerBgColor[0], $headerBgColor[1], $headerBgColor[2]);
    $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
    $pdf->RoundedRect(15, 15, 180, 20, 2, '1111', 'DF', [], $headerBgColor);
    
    // Title text
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor($titleColor[0], $titleColor[1], $titleColor[2]);
    $pdf->SetXY(15, 15);
    $pdf->Cell(180, 12, 'SAGA Waiting List', 0, 1, 'C');
    
    // Subtitle with date and count
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(15, 27);
    $pdf->Cell(180, 8, 'Export Date: ' . date('Y-m-d H:i') . ' | Total Entries: ' . count($waitingList), 0, 1, 'C');
    
    // Add some space
    $pdf->Ln(10);
    
    // Define table dimensions
    $posWidth = 15;
    $nameWidth = 60;
    $contactWidth = 40;
    $langWidth = 25;
    $timeWidth = 40;
    $rowHeight = 8;
    
    // Column headers with rounded corners and gradient
    $pdf->SetFillColor($headerBgColor[0], $headerBgColor[1], $headerBgColor[2]);
    $pdf->SetTextColor($headerTextColor[0], $headerTextColor[1], $headerTextColor[2]);
    $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
    $pdf->SetFont('helvetica', 'B', 10);
    
    // Draw header cells
    $pdf->RoundedRect(15, $pdf->GetY(), $posWidth, $rowHeight, 1, '1000', 'DF');
    $pdf->RoundedRect(15 + $posWidth, $pdf->GetY(), $nameWidth, $rowHeight, 1, '0000', 'DF');
    $pdf->RoundedRect(15 + $posWidth + $nameWidth, $pdf->GetY(), $contactWidth, $rowHeight, 1, '0000', 'DF');
    $pdf->RoundedRect(15 + $posWidth + $nameWidth + $contactWidth, $pdf->GetY(), $langWidth, $rowHeight, 1, '0000', 'DF');
    $pdf->RoundedRect(15 + $posWidth + $nameWidth + $contactWidth + $langWidth, $pdf->GetY(), $timeWidth, $rowHeight, 1, '0001', 'DF');
    
    // Header text
    $currentY = $pdf->GetY();
    $pdf->SetXY(15, $currentY);
    $pdf->Cell($posWidth, $rowHeight, 'Pos', 0, 0, 'C');
    $pdf->Cell($nameWidth, $rowHeight, 'Name', 0, 0, 'C');
    $pdf->Cell($contactWidth, $rowHeight, 'Contact', 0, 0, 'C');
    $pdf->Cell($langWidth, $rowHeight, 'Language', 0, 0, 'C');
    $pdf->Cell($timeWidth, $rowHeight, 'Time', 0, 1, 'C');
    
    // Data rows
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    
    $fill = false;
    $startY = $pdf->GetY();
    
    foreach ($waitingList as $user) {
        // Format time
        $timeFormatted = date('Y-m-d H:i', $user['time']);
        
        // Ensure data fits in cells
        $name = mb_strlen($user['name']) > 30 ? mb_substr($user['name'], 0, 27) . '...' : $user['name'];
        $contact = !empty($user['email_or_phone']) ? (mb_strlen($user['email_or_phone']) > 20 ? mb_substr($user['email_or_phone'], 0, 17) . '...' : $user['email_or_phone']) : '';
        
        // Alternate row colors
        $fill = !$fill;
        $rowColor = $fill ? $rowColor2 : $rowColor1;
        $pdf->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]);
        
        // Draw row background
        $pdf->Rect(15, $pdf->GetY(), $posWidth + $nameWidth + $contactWidth + $langWidth + $timeWidth, $rowHeight, 'F');
        
        // Draw cell borders
        $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
        $pdf->Line(15, $pdf->GetY(), 15, $pdf->GetY() + $rowHeight); // Left border
        $pdf->Line(15 + $posWidth, $pdf->GetY(), 15 + $posWidth, $pdf->GetY() + $rowHeight); // After position
        $pdf->Line(15 + $posWidth + $nameWidth, $pdf->GetY(), 15 + $posWidth + $nameWidth, $pdf->GetY() + $rowHeight); // After name
        $pdf->Line(15 + $posWidth + $nameWidth + $contactWidth, $pdf->GetY(), 15 + $posWidth + $nameWidth + $contactWidth, $pdf->GetY() + $rowHeight); // After contact
        $pdf->Line(15 + $posWidth + $nameWidth + $contactWidth + $langWidth, $pdf->GetY(), 15 + $posWidth + $nameWidth + $contactWidth + $langWidth, $pdf->GetY() + $rowHeight); // After language
        $pdf->Line(15 + $posWidth + $nameWidth + $contactWidth + $langWidth + $timeWidth, $pdf->GetY(), 15 + $posWidth + $nameWidth + $contactWidth + $langWidth + $timeWidth, $pdf->GetY() + $rowHeight); // Right border
        
        // Cell content
        $currentY = $pdf->GetY();
        $pdf->SetXY(15, $currentY);
        $pdf->Cell($posWidth, $rowHeight, $user['position'], 0, 0, 'C');
        $pdf->Cell($nameWidth, $rowHeight, $name, 0, 0, 'L');
        $pdf->Cell($contactWidth, $rowHeight, $contact, 0, 0, 'L');
        $pdf->Cell($langWidth, $rowHeight, $user['language'], 0, 0, 'C');
        $pdf->Cell($timeWidth, $rowHeight, $timeFormatted, 0, 1, 'C');
    }
    
    // Draw bottom border for the last data row
    $pdf->Line(15, $pdf->GetY(), 15 + $posWidth + $nameWidth + $contactWidth + $langWidth + $timeWidth, $pdf->GetY());
    
    // Calculate remaining space on the page and add empty rows
    $endY = $pdf->GetY();
    $pageHeight = $pdf->getPageHeight();
    $footerHeight = 15; // Footer height in mm
    $remainingSpace = $pageHeight - $endY - $footerHeight;
    $maxAdditionalRows = floor($remainingSpace / $rowHeight);
    
    // Add empty rows (leave space for at least 5 rows, or add up to 15 rows)
    $emptyRowsToAdd = min(max(5, $maxAdditionalRows), 15);
    
    // If we have a lot of entries already, start a new page for empty rows
    if ($emptyRowsToAdd < 5 && count($waitingList) > 0) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Additional Entries', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Redraw the header
        $pdf->SetFillColor($headerBgColor[0], $headerBgColor[1], $headerBgColor[2]);
        $pdf->SetTextColor($headerTextColor[0], $headerTextColor[1], $headerTextColor[2]);
        $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
        $pdf->SetFont('helvetica', 'B', 10);
        
        // Draw header cells
        $pdf->RoundedRect(15, $pdf->GetY(), $posWidth, $rowHeight, 1, '1000', 'DF');
        $pdf->RoundedRect(15 + $posWidth, $pdf->GetY(), $nameWidth, $rowHeight, 1, '0000', 'DF');
        $pdf->RoundedRect(15 + $posWidth + $nameWidth, $pdf->GetY(), $contactWidth, $rowHeight, 1, '0000', 'DF');
        $pdf->RoundedRect(15 + $posWidth + $nameWidth + $contactWidth, $pdf->GetY(), $langWidth, $rowHeight, 1, '0000', 'DF');
        $pdf->RoundedRect(15 + $posWidth + $nameWidth + $contactWidth + $langWidth, $pdf->GetY(), $timeWidth, $rowHeight, 1, '0001', 'DF');
        
        // Header text
        $currentY = $pdf->GetY();
        $pdf->SetXY(15, $currentY);
        $pdf->Cell($posWidth, $rowHeight, 'Pos', 0, 0, 'C');
        $pdf->Cell($nameWidth, $rowHeight, 'Name', 0, 0, 'C');
        $pdf->Cell($contactWidth, $rowHeight, 'Contact', 0, 0, 'C');
        $pdf->Cell($langWidth, $rowHeight, 'Language', 0, 0, 'C');
        $pdf->Cell($timeWidth, $rowHeight, 'Time', 0, 1, 'C');
        
        // Calculate new number of empty rows for the new page
        $emptyRowsToAdd = 20; // Fill most of the new page
    }
    
    // Add empty rows
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    
    $nextPosition = count($waitingList) > 0 ? $waitingList[count($waitingList) - 1]['position'] + 1 : 1;
    
    for ($i = 0; $i < $emptyRowsToAdd; $i++) {
        // Alternate row colors
        $fill = !$fill;
        $rowColor = $fill ? $rowColor2 : $rowColor1;
        $pdf->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]);
        
        // Draw row background
        $pdf->Rect(15, $pdf->GetY(), $posWidth + $nameWidth + $contactWidth + $langWidth + $timeWidth, $rowHeight, 'F');
        
        // Draw cell borders
        $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
        $pdf->Line(15, $pdf->GetY(), 15, $pdf->GetY() + $rowHeight); // Left border
        $pdf->Line(15 + $posWidth, $pdf->GetY(), 15 + $posWidth, $pdf->GetY() + $rowHeight); // After position
        $pdf->Line(15 + $posWidth + $nameWidth, $pdf->GetY(), 15 + $posWidth + $nameWidth, $pdf->GetY() + $rowHeight); // After name
        $pdf->Line(15 + $posWidth + $nameWidth + $contactWidth, $pdf->GetY(), 15 + $posWidth + $nameWidth + $contactWidth, $pdf->GetY() + $rowHeight); // After contact
        $pdf->Line(15 + $posWidth + $nameWidth + $contactWidth + $langWidth, $pdf->GetY(), 15 + $posWidth + $nameWidth + $contactWidth + $langWidth, $pdf->GetY() + $rowHeight); // After language
        $pdf->Line(15 + $posWidth + $nameWidth + $contactWidth + $langWidth + $timeWidth, $pdf->GetY(), 15 + $posWidth + $nameWidth + $contactWidth + $langWidth + $timeWidth, $pdf->GetY() + $rowHeight); // Right border
        
        // Cell content - just the position number for empty rows
        $currentY = $pdf->GetY();
        $pdf->SetXY(15, $currentY);
        $pdf->Cell($posWidth, $rowHeight, $nextPosition++, 0, 0, 'C');
        $pdf->Cell($nameWidth, $rowHeight, '', 0, 0, 'L');
        $pdf->Cell($contactWidth, $rowHeight, '', 0, 0, 'L');
        $pdf->Cell($langWidth, $rowHeight, '', 0, 0, 'C');
        $pdf->Cell($timeWidth, $rowHeight, '', 0, 1, 'C');
    }
    
    // Draw bottom border for the last empty row
    $pdf->Line(15, $pdf->GetY(), 15 + $posWidth + $nameWidth + $contactWidth + $langWidth + $timeWidth, $pdf->GetY());
    
    // Add comment section if needed
    if (count($waitingList) > 0) {
        // Check if we need a new page for comments
        if ($pdf->GetY() > $pageHeight - 60) {
            $pdf->AddPage();
        } else {
            $pdf->Ln(10);
        }
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Comments', 0, 1);
        $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(2);
        
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($waitingList as $user) {
            if (!empty($user['comment'])) {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(15, 6, $user['position'], 0, 0);
                $pdf->Cell(60, 6, $user['name'], 0, 1);
                $pdf->SetFont('helvetica', 'I', 9);
                $pdf->MultiCell(0, 5, $user['comment'], 0, 'L');
                $pdf->Ln(2);
                $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
                $pdf->Ln(2);
            }
        }
    }
    
    // Close and output PDF document
    $pdf->Output('saga_waiting_list_' . date('Y-m-d') . '.pdf', 'I');

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
