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


// Kontrollera om produkten finns i GET-parametrarna
if(isset($_GET['product'])) {
    $productName = htmlspecialchars($_GET['product']);
} else {
    // Om produkten inte finns i GET-parametrarna, skicka tillbaka användaren
    $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Invalid product.</div>';
    header("Location: modify_db.php");
    exit();
}



// Om knappet har tryckts för att Change relation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["changeRelation"])) {
    // Hämta det nya namnet för produkten från formuläret
    $product = $_POST['product'];

    // Öppna anslutning till SQLite-databasen
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

    // Om produkten inte finns i products så kanske den finns i temp_products
    if (!in_array($product, $productNames)) {
        // Kolla om elementet man vill lägga till en relation till ligger i kolonnen temp_name i temp_products
        $stmt = $db->prepare('SELECT temp_name FROM temp_products WHERE username = :username AND temp_name = :temp_name');
        $stmt->bindParam(':username', $loggedInUser);
        $stmt->bindParam(':temp_name', $productName);
        $result = $stmt->execute();
        $temp_product = $result->fetchArray(SQLITE3_ASSOC);

        // I sådana fall, hämta product_name på denna raden och sätt det som productName
        if ($temp_product) {
            $stmt = $db->prepare('SELECT product_name FROM temp_products WHERE username = :username AND temp_name = :temp_name');
            $stmt->bindParam(':username', $loggedInUser);
            $stmt->bindParam(':temp_name', $productName);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row) {
                    $productName = $row['product_name'];
            }
        }

        // Lägg till den nya produkten man angav i temp_products med $productName som product_name
        $stmt = $db->prepare('INSERT INTO temp_products (username, product_name, temp_name) VALUES (:username, :product_name, :temp_name)');
        $stmt->bindParam(':username', $loggedInUser);
        $stmt->bindParam(':product_name', $productName);
        $stmt->bindParam(':temp_name', $product);
        $result = $stmt->execute();

        // Meddela användaren att produkten har lagts till
        $_SESSION['message'] = '<div class="alert alert-success" role="alert">Relation added!</div>';
    }else {
        // Meddlea användaren att produkten redan finns
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Product already exist in database</div>';
    }

    // Stäng databasen
    $db->close();
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
            <h2 class="form-signin-heading">Change Relation of '<?php echo $productName; ?>'</h2>
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
                placeholder="Enter product name you want to relate to '<?php echo $productName; ?>'"
                required
              />
            </div>
            <button class="btn btn-lg btn-primary btn-block mb-2" type="submit" name="changeRelation">
                Change Relation
            </button>
          </form>
            <form method="get" action="modify_db.php">
                <button class="btn btn-lg btn-primary btn-block" type="submit">
                    Go Back to Modify Database
                </button>
            </form>
        </div>
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
