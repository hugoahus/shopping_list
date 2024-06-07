<?php
// Skriven av Hugo Larsson Wilhelmsson och Erik Smit
session_start();
// Hämta den ursprungliga sidan, dvs index.php
$actual_url = dirname($_SERVER[REQUEST_URI]);

// Förstör all aktuell data
session_destroy();
// Byt hemsida
header("Location: ".$actual_url."/../");
exit();
?>