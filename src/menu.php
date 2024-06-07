<?php
// Skriven av Hugo Larsson Wilhelmsson och Erik Smit
require('functions.php');
session_start();
if (!isset($_SESSION["logged_in_user"])) {
    header("Location: index.php"); 
    exit();
} else {
  // Kontrollera om användaren är inloggad och om sessionen är giltig
  validateSession();
}

// Om användaren är inloggad, hämta användarnamnet från sessionen
$loggedInUser = $_SESSION["logged_in_user"];
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <title>Menu page</title>
    <link
        rel="stylesheet"
        href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
        crossorigin="anonymous"
    />
    <link
        href="https://getbootstrap.com/docs/4.0/examples/signin/signin.css"
        rel="stylesheet"
        crossorigin="anonymous"
    />
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-auto">
                <a href="logout.php" class="btn btn-secondary btn-sm active" role="button" aria-pressed="true">Logout</a>
            </div>
        </div>
    </div>
    <h1>Menu</h1>
    What do you want to do?<br>
    <ol type="1">
        <li><a href="generate_shopping_list.php">Create Shopping List</a></li>
        <li><a href="cart.php">Generated Shopping List</a></li>
        <li><a href="modify_db.php">Modify Database</a></li>
    </ol>
</body>
</html>