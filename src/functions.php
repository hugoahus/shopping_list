<?php
// Skriven av Hugo Larsson Wilhelmsson och Erik Smit
// Hasha lösenord med SHA3-512
function hashPassword($password) {
    return hash('sha3-512', $password);
}

// Verifiera lösenord mot en hash
function verifyPassword($password, $hash) {
    return hashPassword($password) === $hash;
}

function selectPwd($username){
    // Öppna SQLite-databasen
    $db = new SQLite3('../database/account_items.db');

    // Förbered SQL-frågan med en parametiserad fråga
    $sql = "SELECT password FROM users WHERE username = :username";

    // Förbered och bind parametrarna till frågan
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':username', $username);

    // Utför frågan
    $result = $stmt->execute();

    // Hämta raden från resultatet
    $row = $result->fetchArray();

    // Stäng databasanslutningen
    $db->close();

    // Returnera hashvärdet (kan vara null om användarnamnet inte hittades)
    return isset($row['password']) ? $row['password'] : null;
}


function updateSessionId($username, $session_id) {
    // Öppna SQLite-databasen
    $db = new SQLite3('../database/account_items.db');

    // Förbered och bind parametrarna till frågan
    $stmt = $db->prepare('UPDATE users SET session_id = :session_id WHERE username = :username');
    $stmt->bindParam(':session_id', $session_id);
    $stmt->bindParam(':username', $username);

    // Utför frågan
    $stmt->execute();

    // Stäng databasanslutningen
    $db->close();
}

function validateSession() {
    if (isset($_SESSION['logged_in_user']) && isset($_SESSION['session_id'])) {
        $username = $_SESSION['logged_in_user'];
        $session_id = $_SESSION['session_id'];

        // Öppna SQLite-databasen
        $db = new SQLite3('../database/account_items.db');

        // Förbered och bind parametrarna till frågan
        $stmt = $db->prepare('SELECT session_id FROM users WHERE username = :username');
        $stmt->bindParam(':username', $username);

        // Utför frågan
        $result = $stmt->execute();

        // Hämta session_id från resultatet
        $row = $result->fetchArray();

        // Stäng databasanslutningen
        $db->close();

        // Kontrollera om sessions-ID:t matchar
        if ($row['session_id'] == $session_id) {
            return true;
        } else {
            // Ogiltig session, logga ut användaren
            header('Location: logout.php');
            exit();
        }
    } else {
        // Ingen session hittades, logga ut användaren
        header('Location: logout.php');
        exit();
    }
}

?>


