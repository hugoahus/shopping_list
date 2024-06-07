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

$loggedInUser = $_SESSION["logged_in_user"];




// Läs in produkter från formulär och lägg till dem i session array
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["product"])) {
    $product = htmlspecialchars($_POST["product"]);

    // Öppna databasen
    $db = new SQLite3('../database/account_items.db');

    $stmt = $db->prepare('SELECT product_name FROM cart WHERE username = :username AND product_name = :product_name;');
    $stmt->bindParam(':username', $loggedInUser);
    $stmt->bindParam(':product_name', $product);
    $result = $stmt->execute();

    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row != false) {
        $_SESSION['message'] = "<p style='background-color:Tomato;'>Product already exists in list!</p>";
    }else {

        $checkboxValue = 0;

        $stmt = $db->prepare('INSERT INTO cart (username, product_name, mark) VALUES (:username, :product_name, :mark);');
        $stmt->bindParam(':username', $loggedInUser);
        $stmt->bindParam(':product_name', $product);
        $stmt->bindParam(':mark', $checkboxValue);
        $stmt->execute();

        $_SESSION['message'] = "<p class='alert alert-success'>Product added in list!</p>";
    }

    // Stäng databasen
    $db->close();

    // Uppdaterar sidan för att förhindra att POST lägger till samma element flera gånger
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}





// Kontrollera om formuläret har skickats och om det finns en postvariabel med namnet 'selected_product'
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_product'])) {
    // Hämta den valda produkten från postvariabeln
    $selectedProduct = $_POST['selected_product'];

    // Öppna databasen
    $db = new SQLite3('../database/account_items.db');

    $stmt = $db->prepare('SELECT mark FROM cart WHERE username = :username AND product_name = :product_name;');
    $stmt->bindParam(':username', $loggedInUser);
    $stmt->bindParam(':product_name', $selectedProduct);
    $result = $stmt->execute();

    $row = $result->fetchArray(SQLITE3_ASSOC);

    if($row) {
        $markValue = $row['mark'];
    }
    if ($markValue == 1) {
        $markValue = 0;
    }else {
        $markValue = 1;
    }

    $stmt = $db->prepare('UPDATE cart SET mark = :mark WHERE username = :username AND product_name = :product_name;');
    $stmt->bindParam(':username', $loggedInUser);
    $stmt->bindParam(':product_name', $selectedProduct);
    $stmt->bindParam(':mark', $markValue);
    $stmt->execute();

    // Stäng databasen
    $db->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}



// Om formuläret för "Done Shopping" har körts
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['doneShopping'])) {
    $db = new SQLite3('../database/account_items.db');

    $markValue = 1;

    $stmt = $db->prepare('SELECT product_name FROM cart WHERE username = :username AND mark = :markValue;');
    $stmt->bindParam(':username', $loggedInUser);
    $stmt->bindParam(':markValue', $markValue);
    $result = $stmt->execute();
    
    // Initialisera en array för att lagra samtliga product_name
    $productNames = [];

    // Iterera genom resultatet och lägg till varje product_name i arrayen
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $productNames[] = $row['product_name'];
    }

    // Börja en transaktion för att endast utföra följande SQL-insertions om samtliga insertions genomförs
    $db->exec('BEGIN');
    try {
        // Loopar igenom varje produkt i listan
        foreach ($productNames as $product_name) {

            // Kollar om produkten finns i tablet products
            $stmt = $db->prepare('SELECT product_name FROM products WHERE username = :username AND product_name = :product_name;');
            $stmt->bindParam(':username', $loggedInUser);
            $stmt->bindParam(':product_name', $product_name);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            // Om produkten inte finns i tablet
            if (!$row) {

                // Kolla om produkten finns i temp_products
                $stmt = $db->prepare('SELECT temp_name FROM temp_products WHERE username = :username AND temp_name = :product_name;');
                $stmt->bindParam(':username', $loggedInUser);
                $stmt->bindParam(':product_name', $product_name);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);

                // Om produkten inte finns i tablet
                if (!$row) {
                    // Lägg till produkten i products
                    $stmt = $db->prepare('INSERT INTO products (username, product_name) VALUES (:username, :product_name);');
                    $stmt->bindParam(':username', $loggedInUser);
                    $stmt->bindParam(':product_name', $product_name);
                    $stmt->execute();
                // Annars, hämta vilken produkt som den aktuella produkten relaterar till
                } else {
                    $stmt = $db->prepare('SELECT product_name FROM temp_products WHERE username = :username AND temp_name = :product_name;');
                    $stmt->bindParam(':username', $loggedInUser);
                    $stmt->bindParam(':product_name', $product_name);
                    $result = $stmt->execute();
                    $row = $result->fetchArray(SQLITE3_ASSOC);
                    // Om det finns en produkt som relaterar till den aktuella produkten
                    if ($row) {
                        $product_name = $row['product_name']; // Hämta produktens namn från arrayen
                    }
                }

        }
            $currentDate = date('Y-m-d');

            // Lägg till produkten med dagens datum i buy_dates tablet
            $stmt = $db->prepare('INSERT INTO buy_dates (username, product_name, buy_date) VALUES (:username, :product_name, :buy_date);');
            $stmt->bindParam(':username', $loggedInUser);
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':buy_date', $currentDate);
            $result = $stmt->execute();
        }

        // Utför commit för att bekräfta transaktionen
        $db->exec('COMMIT');
    } catch (Exception $e) {
        // Om det uppstår ett fel, gör en rollback för att ångra alla ändringar
        $db->exec('ROLLBACK');
    }

    // Stäng databasen
    $db->close();
    header("Location: menu.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Shopping cart</title>
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
    <style>
        .checkbox-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .checkbox-label span {
            margin-right: auto;
        }

        .checkbox-label input[type="checkbox"] {
            width: 30px;
            height: 30px;
        }
    </style>
</head>
<body>
  <div class="container">
    <div class="row">
        <div class="col-12 text-left">
            <a href="logout.php" class="btn btn-secondary btn-sm active" role="button" aria-pressed="true">Logout</a>
        </div>
        <div class="col-12 text-center mt-4">
            <form method="post">
                <h2 class="form-signin-heading">Add Product to Shopping List</h2>
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
                    Add Product to Shopping List
                </button>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-12 text-center mt-4">
            <h2>Shopping List</h2>
            <form id="shoppingListForm" method="post">
                <ul class="list-group">
                    <?php
                    // Öppna databasen
                    $db = new SQLite3('../database/account_items.db');

                    $stmt = $db->prepare('SELECT product_name, mark FROM cart WHERE username = :username;');
                    $stmt->bindParam(':username', $loggedInUser);
                    $result = $stmt->execute();

                    // Loop genom resultaten och skapa checkboxar
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        $productName = htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8');
                        $mark = $row['mark']; // Anta att mark är antingen 0 eller 1

                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                        echo '<label class="checkbox-label">';
                        echo '<span>' . $productName . '</span>';
                        echo '<input type="checkbox" name="products[]" value="' . $productName . '"';
                        
                        // Om mark är 1, lägg till checked-attributet
                        if ($mark == 1) {
                            echo ' checked';
                        }
                        
                        echo '>';
                        echo '</label>';
                        echo '</li>';
                    }
                    // Stäng databasen
                    $db->close();
                    ?>

                </ul>
            </form>
        </div>
    </div>
</div>
</div>
    <div class="container">
      <form method="post" action="">
        <button type="submit" name="doneShopping" class="btn btn-lg btn-primary btn-block">Done Shopping</button>
      </form>
      <form method="get" action="menu.php">
        <button class="btn btn-lg btn-primary btn-block mt-2" type="submit">
            Go Back to Menu
        </button>
    </form>
    </div>
</div>
<script>
    // Lyssna på förändringar i checkboxarna
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Hämta värdet på den checkbox som ändrades
            const productName = this.value;
            
            // Skapa ett dolt input-fält för att skicka produktens namn
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'selected_product';
            hiddenInput.value = productName;
            
            // Lägg till det dolda input-fältet till formuläret
            document.getElementById('shoppingListForm').appendChild(hiddenInput);
            
            // Skicka formuläret
            document.getElementById('shoppingListForm').submit();
        });
    });
</script>

</body>
</html>
