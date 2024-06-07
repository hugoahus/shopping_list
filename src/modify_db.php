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

// Kontrollera om en produkt ska tas bort
if (isset($_GET['remove'])) {
    $productToRemove = $_GET['remove'];
    
    // Öppna anslutning till SQLite-databasen
    $db = new SQLite3('../database/account_items.db');

    // Kolla om elementet finns i products
    $stmt = $db->prepare('SELECT product_name FROM products WHERE username = :username AND product_name = :product_name;');
    $stmt->bindParam(':username', $loggedInUser);
    $stmt->bindParam(':product_name', $productToRemove);
    $result = $stmt->execute();

    $row_products_table = $result->fetchArray(SQLITE3_ASSOC);

    if($row_products_table) {
        // Om elementet finns, ta bort det
        $stmt = $db->prepare('DELETE FROM products WHERE username = :username AND product_name = :product_name');
        $stmt->bindParam(':username', $loggedInUser);
        $stmt->bindParam(':product_name', $productToRemove);
        $stmt->execute();

        // Kolla om något element i temp_products bygger på elementet som togs bort
        $stmt = $db->prepare('SELECT product_name FROM temp_products WHERE username = :username AND product_name = :product_name;');
        $stmt->bindParam(':username', $loggedInUser);
        $stmt->bindParam(':product_name', $productToRemove);
        $result = $stmt->execute();
        $product = $result->fetchArray(SQLITE3_ASSOC);

        if (!$product) {
            // Om inget i temp_products bygger på elementet, ta bort alla datum
            $stmt = $db->prepare('DELETE FROM buy_dates WHERE username = :username AND product_name = :product_name');
            $stmt->bindParam(':username', $loggedInUser);
            $stmt->bindParam(':product_name', $productToRemove);
            $stmt->execute();
        }
    }else {
        // Kolla om elementet finns i temp_products
        $stmt = $db->prepare('SELECT temp_name FROM temp_products WHERE username = :username AND temp_name = :temp_name;');
        $stmt->bindParam(':username', $loggedInUser);
        $stmt->bindParam(':temp_name', $productToRemove);
        $result = $stmt->execute();

        $row_products_table = $result->fetchArray(SQLITE3_ASSOC);

        if($row_products_table) {
            // Om det finns, hämta elementet det bygger på och ta bort temp_product

            $stmt = $db->prepare('SELECT product_name FROM temp_products WHERE username = :username AND temp_name = :temp_name;');
            $stmt->bindParam(':username', $loggedInUser);
            $stmt->bindParam(':temp_name', $productToRemove);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row) {
                $product_name = $row['product_name'];
            }

            $stmt = $db->prepare('DELETE FROM temp_products WHERE username = :username AND temp_name = :temp_name');
            $stmt->bindParam(':username', $loggedInUser);
            $stmt->bindParam(':temp_name', $productToRemove);
            $stmt->execute();

            // Kolla om detta element finns i som product_name i products eller product_name i temp_products

            $stmt = $db->prepare('SELECT product_name FROM products WHERE username = :username AND product_name = :product_name;');
            $stmt->bindParam(':username', $loggedInUser);
            $stmt->bindParam(':product_name', $product_name);
            $result = $stmt->execute();
            $products_row = $result->fetchArray(SQLITE3_ASSOC);

            $stmt = $db->prepare('SELECT product_name FROM temp_products WHERE username = :username AND product_name = :product_name;');
            $stmt->bindParam(':username', $loggedInUser);
            $stmt->bindParam(':product_name', $product_name);
            $result = $stmt->execute();
            $temp_row = $result->fetchArray(SQLITE3_ASSOC);

            if (!$products_row && !$temp_row) {
                // Om inget i de andra tabellerna bygger på elementet, ta bort alla datum
                $stmt = $db->prepare('DELETE FROM buy_dates WHERE username = :username AND product_name = :product_name');
                $stmt->bindParam(':username', $loggedInUser);
                $stmt->bindParam(':product_name', $product_name);
                $stmt->execute();
            }

        }
    }

    // Stäng databasen
    $db->close();
    
    // Omdirigera för att undvika att samma produkt tas bort igen vid sidladdning
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
        <title>Modify database</title>
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
    <div class="container">
      <div class="row">
        <div class="col-12 text-left">
            <a href="logout.php" class="btn btn-secondary btn-sm active" role="button" aria-pressed="true">Logout</a>
        </div>
      </div>
      <div class="row">
        <div class="col-12 text-center mt-4">
          <h2>Products in database</h2>
          <ul class="list-group">
            <?php
                $db = new SQLite3('../database/account_items.db');

                // Hämtar alla produkter från products och lägger till i listan "productNames"
                $stmt = $db->prepare('SELECT product_name FROM products WHERE username = :username');
                $stmt->bindParam(':username', $loggedInUser);
                $result = $stmt->execute();
                
                // Initialisera en array för att lagra samtliga product_name
                $productNames = [];

                // Iterera genom resultatet och lägg till varje product_name i arrayen
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $productNames[] = $row['product_name'];
                }

                // Hämtar alla produkter från temp_products och lägger till i listan "productNames"
                $stmt = $db->prepare('SELECT temp_name FROM temp_products WHERE username = :username');
                $stmt->bindParam(':username', $loggedInUser);
                $result = $stmt->execute();

                // Iterera genom resultatet och lägg till varje product_name i arrayen
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $productNames[] = $row['temp_name'];
                }

                // Printar listan med knappar
                foreach ($productNames as $product_name) {
                    echo '<li class="list-group-item d-flex justify-content-between align-items-center">' . 
                            '<a href="change_relation.php?product=' . urlencode($product_name) . '" class="btn btn-primary btn-sm">Change Relation</a>' . 
                            htmlspecialchars($product_name) . 
                            '<a href="?remove=' . urlencode($product_name) . '" class="btn btn-danger btn-sm ml-2">Remove</a>' . 
                        '</li>';
                }
                // Stäng databasen
                $db->close();
            ?>
          </ul>
        </div>
      </div>
      <form method="get" action="menu.php">
        <button class="btn btn-lg btn-primary btn-block mt-2" type="submit">
            Go Back to Menu
        </button>
    </form>
    </div>
  </body>
</html>
