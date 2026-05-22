<?php

$conn = pg_connect("
    host=dpg-d866p5f7f7vs739nobng-a
    port=5432
    dbname=db_myapp1
    user=db_myapp1_user
    password=TM0bqXSIftYyad5Wu1g45DcrqgUo8sID
");

if (!$conn) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]));
}
