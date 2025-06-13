<?php
/**
 * Export Waiting List Handler
 * 
 * Creates a nicely formatted PDF export of the waiting list
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

// Extend TCPDF to create custom footer
class MYPDF extends TCPDF {
    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
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
    $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

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
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Set title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'SAGA Waiting List', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Export Date: ' . date('Y-m-d H:i'), 0, 1, 'C');
    $pdf->Cell(0, 6, 'Total Entries: ' . count($waitingList), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Column headers
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(15, 7, 'Pos', 1, 0, 'C', 1);
    $pdf->Cell(60, 7, 'Name', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Contact', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Language', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Time', 1, 1, 'C', 1);
    
    // Data rows
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(255, 255, 255);
    
    $fill = false;
    foreach ($waitingList as $user) {
        // Format time
        $timeFormatted = date('Y-m-d H:i', $user['time']);
        
        // Ensure data fits in cells
        $name = mb_strlen($user['name']) > 30 ? mb_substr($user['name'], 0, 27) . '...' : $user['name'];
        $contact = !empty($user['email_or_phone']) ? (mb_strlen($user['email_or_phone']) > 20 ? mb_substr($user['email_or_phone'], 0, 17) . '...' : $user['email_or_phone']) : '';
        
        // Alternate row colors
        $fill = !$fill;
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
        
        $pdf->Cell(15, 6, $user['position'], 'LR', 0, 'C', 1);
        $pdf->Cell(60, 6, $name, 'LR', 0, 'L', 1);
        $pdf->Cell(40, 6, $contact, 'LR', 0, 'L', 1);
        $pdf->Cell(25, 6, $user['language'], 'LR', 0, 'C', 1);
        $pdf->Cell(40, 6, $timeFormatted, 'LR', 1, 'C', 1);
    }
    
    // Closing line
    $pdf->Cell(180, 0, '', 'T', 1);
    
    // Add comment section if needed
    if (count($waitingList) > 0) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'Comments', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($waitingList as $user) {
            if (!empty($user['comment'])) {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(15, 6, $user['position'], 0, 0);
                $pdf->Cell(60, 6, $user['name'], 0, 1);
                $pdf->SetFont('helvetica', 'I', 9);
                $pdf->MultiCell(0, 5, $user['comment'], 0, 'L');
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
