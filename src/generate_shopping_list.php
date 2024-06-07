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

// Skapa en tom array om $_SESSION['products'] inte är satt
if (!isset($_SESSION['products'])) {
    $_SESSION['products'] = [];
}

// Läs in produkten från formulär och lägg till dem i session array (med ENTER)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["product"])) {
    $product = htmlspecialchars($_POST["product"]);

    // Kolla ifall produkten redan finns
    if (in_array($product, $_SESSION['products'])) {
        $_SESSION['message'] = "<p style='background-color:Tomato;'>Product already exists in list!</p>";
    }else {
        $_SESSION['products'][] = $product;
        $_SESSION['message'] = "<p class='alert alert-success'>Product added in list!</p>";
    }
    // Uppdaterar sidan för att förhindra att POST lägger till samma element flera gånger
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Läs in produkten vid knapptrycket "Add" från formulär och lägg till dem i session array (samma som ovan)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["addProduct"])) {
    $product = htmlspecialchars($_POST["addProduct"]);
    if (in_array($product, $_SESSION['products'])) {
      $_SESSION['message'] = "<p style='background-color:Tomato;'>Product already exists in list!</p>";
    }else {
      $_SESSION['products'][] = $product;
      $_SESSION['message'] = "<p class='alert alert-success'>Product added in list!</p>";
    }
    // Uppdaterar sidan för att förhindra att POST lägger till samma element flera gånger
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Om formuläret för att spara listan i databasen har körts
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['saveIntoDatabase'])) {
    $db = new SQLite3('../database/account_items.db');
    // Tömmer tabellen "cart". exec() kan användas när databasen inte ska returnera något
    $stmt = $db->prepare("DELETE FROM cart WHERE username = :username");
    $stmt->bindParam(':username', $loggedInUser);
    $stmt->execute();
    foreach ($_SESSION['products'] as $product) {
      // Sätter Checkboxen till 0 (icke itryckt)
      $checkboxValue = 0;
      // Lägger till datan från inköpslistan i databasen
      $stmt = $db->prepare('INSERT INTO cart (username, product_name, mark) VALUES (:username, :product_name, :mark)');
      $stmt->bindParam(':username', $loggedInUser);
      $stmt->bindParam(':product_name', $product);
      $stmt->bindParam(':mark', $checkboxValue);
      $stmt->execute();
    }
    // Stäng databasen
    $db->close();
    header("Location: cart.php");
}


// Ta bort en produkt om en knapp klickas
if (isset($_GET['remove'])) {
    $index = $_GET['remove'];
    unset($_SESSION['products'][$index]);
    $_SESSION['products'] = array_values($_SESSION['products']); // Omindexera arrayen
    // Uppdaterar sidan för att ladda om arrayen
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
        <title>Product List</title>
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
        <div class="col-12 text-center mt-4">
          <form method="post" action="#">
            <h2 class="form-signin-heading">Add Product</h2>
            <div class="form-group">
                <?php
                if (isset($_SESSION['message'])) {
	                echo $_SESSION['message'];
    		          unset($_SESSION['message']); // Ta bort meddelandet från sessionen så det inte visas igen
                }
                ?>
              <label for="product">Product</label>
              <input
                type="text"
                id="product"
                name="product"
                class="form-control"
                placeholder="Enter product"
                required
              />
            </div>
            <button class="btn btn-lg btn-primary btn-block" type="submit">
              Add Product
            </button>
          </form>
        </div>
      </div>
      <div class="row">
        <div class="col-12 text-center mt-4">
          <h2>Product List</h2>
          <ul class="list-group">
            <?php
            // Visa produkterna i en lista med knappar för att ta bort dem
            if (isset($_SESSION['products'])) {
                foreach ($_SESSION['products'] as $index => $product) {
                    echo '<li class="list-group-item d-flex justify-content-between align-items-center">' . $product . '<a href="?remove=' . $index . '" class="btn btn-danger btn-sm">Remove</a></li>';
                }
            }
            ?>
          </ul>
        </div>
      </div>
      <div class="row">
        <div class="col-12 text-center mt-4">
          <h2>Recommended products</h2>
          <ul class="list-group">
            <?php
              // Öppna SQLite-databasen
              $db = new SQLite3('../database/account_items.db');
              // Hämta alla produkter för den inloggade användaren i tablet "products" och spara i en array
              $stmt = $db->prepare('SELECT product_name FROM products WHERE username = :username');
              $stmt->bindParam(':username', $loggedInUser);
              $stmt->execute();
              $result = $stmt->execute();
              $products = [];
              while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                  $products[] = $row['product_name'];
              }

              // Hämta alla produkter för den inloggade användaren i tablet "temp_products" och spara i samma array som tidigare
              $stmt = $db->prepare('SELECT temp_name FROM temp_products WHERE username = :username');
              $stmt->bindParam(':username', $loggedInUser);
              $stmt->execute();
              $result = $stmt->execute();
              while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                  $products[] = $row['temp_name'];
              }

              // Loopa igenom varje produktnamn
              foreach ($products as $productName) {
                $diffDatesArray = [];  // Nollställning av diffDatesArray
                $stmt = $db->prepare('SELECT buy_date FROM buy_dates WHERE username = :username AND product_name = :product_name');
                $stmt->bindParam(':username', $loggedInUser);
                $stmt->bindParam(':product_name', $productName);
                $result = $stmt->execute();

                // Spara datumen i en lista
                $buyDates = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                  $buyDates[] = $row['buy_date'];
                }

                $stmt = $db->prepare('SELECT product_name FROM temp_products WHERE username = :username AND temp_name = :temp_name');
                $stmt->bindParam(':username', $loggedInUser);
                $stmt->bindParam(':temp_name', $productName);
                $result = $stmt->execute();
                $product_name_row  = $result->fetchArray(SQLITE3_ASSOC);

                // Om product_name_row inte är tom
                if ($product_name_row) {
                  $product_name = $product_name_row['product_name'];

                  $stmt = $db->prepare('SELECT buy_date FROM buy_dates WHERE username = :username AND product_name = :product_name');
                  $stmt->bindParam(':username', $loggedInUser);
                  $stmt->bindParam(':product_name', $product_name);
                  $result = $stmt->execute();

                  // Spara datumen i en lista
                  $buyDates = [];
                  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $buyDates[] = $row['buy_date'];
                  }
                }

                  // Beräkna tidsskillnaden mellan varje par av köpdatum
                $numDates = count($buyDates);
                if ($numDates > 1) {
                    for ($i = 1; $i < $numDates; $i++) {
                        $diffDatesArray[] = strtotime($buyDates[$i]) - strtotime($buyDates[$i-1]);
                    }
                }
                // Beräkna medelvärdet av tidsskillnaderna
                $desiredDays = 0;
                if (!empty($diffDatesArray)) {
                $desiredDays = array_sum($diffDatesArray) / count($diffDatesArray);
                $desiredDays = floor($desiredDays / (60 * 60 * 24)); // Konvertera till dagar
                }
                // Om det fler än ett köpdatum för produkten
                if ($numDates > 1) {
                    // Hämta det senaste köpet för produkten
                    $latestBuyDate = end($buyDates);
                    
                    // Skapa ett DateTime-objekt för det senaste köpet
                    $latestBuyDateTime = DateTime::createFromFormat('Y-m-d', $latestBuyDate);
                    
                    // Skapa ett DateTime-objekt för dagens datum
                    $currentDateTime = new DateTime();
                    
                    // Beräkna skillnaden i dagar mellan det senaste köpet och dagens datum
                    $daysSinceLatestBuy = $latestBuyDateTime->diff($currentDateTime)->days;
                    
                    // Om det har gått tillräckligt med dagar sedan det senaste köpet
                    if ($daysSinceLatestBuy >= $desiredDays) {
                        // Visa formuläret för att lägga till produkten
                        echo '<form method="post" action="#">';
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">' . $productName . '<button type="submit" name="addProduct" value="' . $productName . '" class="btn btn-success btn-sm">Add</button></li>';
                        echo '</form>';
                    }
                }
              }

              // Stänger databasanslutningen
              $db->close();
            ?>
          </ul>
        </div>
      </div>
        <div class="container">
          <form method="post" action="">
            <button type="submit" name="saveIntoDatabase" class="btn btn-lg btn-primary btn-block">Save Shopping List</button>
          </form>
          <form method="get" action="menu.php">
            <button class="btn btn-lg btn-primary btn-block mt-2" type="submit">
              Go Back to Menu
            </button>
          </form>
        </div>
    </div>
    <script>
      // Fokusera på textrutan när sidan laddas
      window.onload = function() {
        document.getElementById('product').focus();
      };
    </script>
  </body>
</html>
