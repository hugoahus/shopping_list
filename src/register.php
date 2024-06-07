<?php
// Skriven av Hugo Larsson Wilhelmsson och Erik Smit
require('functions.php');

function insertAccount($username, $password) {
    // Öppna SQLite-databasen
    $db = new SQLite3('../database/account_items.db');

    // Fråga för att kolla om användarnamnet redan finns
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->bindParam(':username', $username);
    $result = $stmt->execute();

    // Om användarnamnet inte finns i databasen
    if (!$result->fetchArray()) {

        // Förbered och bind parametrarna till frågan
        $stmt = $db->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
        $stmt->bindParam(':username', $username);

        $hashedPassword = hashPassword($password);
        $stmt->bindParam(':password', $hashedPassword);
        
        // Utför frågan
        $result = $stmt->execute();

        // Stäng databasanslutningen
        $db->close();

        // Hoppa till menu.php
        header("Location: menu.php");
        return True;
        exit();
    } else {
        // Stäng databasanslutningen
        $db->close();
        return False;
    }
}
?>
