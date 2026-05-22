<?php
function addNotification($conn, $notifType, $user_id, $title, $message) {
    // Use UTC
    date_default_timezone_set("UTC");
    $date_time = date("Y-m-d H:i:s");

    $is_read = 0;

    $query = "
        INSERT INTO notifications 
        (notif_type, user_id, date_time, title, message, is_read)
        VALUES ($1, $2, $3, $4, $5, $6)
        RETURNING notification_id
    ";

    $result = pg_query_params($conn, $query, [
        $notifType,
        $user_id,
        $date_time,
        $title,
        $message,
        $is_read
    ]);

    if ($result) {
        $row = pg_fetch_assoc($result);

        return [
            "status" => "success",
            "notification_id" => $row['notification_id']
        ];
    } else {
        return [
            "status" => "error",
            "error" => pg_last_error($conn)
        ];
    }
}
?>
