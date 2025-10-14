<?php
/**
 * Scheduled Waiting List Opener
 *
 * Cron-safe version: ensures the waiting list is open after scheduled times.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/db_connect.php');

try {
    $db = db_connect();

    // Load current settings
    $result = $db->query("SELECT key, value FROM settings");
    $settings = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }

    // Current day and time
    $now = new DateTime();
    $today = $now->format('l');  // e.g., "Tuesday"
    $current_time = $now->format('H:i'); // e.g., "09:05"

    // Parse scheduled open times
    $scheduled_open_times = array_map('trim', explode(',', $settings['scheduled_open_times']));
    $should_be_open = false;

    foreach ($scheduled_open_times as $schedule) {
        if (empty($schedule)) continue;
        [$day, $time] = explode(' ', $schedule);

        if ($day === $today) {
            // If the scheduled time is earlier than now, we should open
            $scheduled_datetime = DateTime::createFromFormat('H:i', $time);
            $current_datetime = DateTime::createFromFormat('H:i', $current_time);

            if ($current_datetime >= $scheduled_datetime) {
                $should_be_open = true;
                break; // Only need one match
            }
        }
    }

    if ($should_be_open && $settings['waiting_list_open'] !== '1') {
        $db->exec("UPDATE settings SET value = '1' WHERE key = 'waiting_list_open'");
        echo "[" . $now->format('Y-m-d H:i:s') . "] Waiting list opened.\n";
    } elseif ($should_be_open) {
        echo "[" . $now->format('Y-m-d H:i:s') . "] Waiting list already open.\n";
    } else {
        echo "[" . $now->format('Y-m-d H:i:s') . "] No scheduled opening yet.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
