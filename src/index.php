<?php
// Skriven av Hugo Larsson Wilhelmsson och Erik Smit
// Starta en session
session_start();

// Inkluderar functions.php-filens innehåll index.php
require('functions.php');
// isset används för att kolla om $_SESSION["logged_in_user"] har ett värde
if (isset($_SESSION["logged_in_user"])) {
  // Validera sessions-ID
  validateSession();
  // "Hoppa" till menu.php  
  header("Location: menu.php");
  exit();
}


// Om HTTP-förfrågan är av typen POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Hämta användarnamn från POST-data
    $username = $_POST["username"];
    // Hämta lösenord från POST-data
    $password = $_POST["password"];
    // Hämtar lösenordet till ett användarnamn
    $row = selectPwd($username);
    // Kollar om lösenordet finns, och om det är lika finns i hash-kod i databsen
    if ($row !== null && verifyPassword($password, $row)) {
      // Generera ett unikt sessions-ID
      $session_id = session_id();

      // Uppdatera sessions-ID i databasen
      updateSessionID($username, $session_id);
      // Spara användarnamn och sessions-ID i sessionen
      $_SESSION["logged_in_user"] = $username;
      $_SESSION["session_id"] = $session_id;
      // Hoppa till menyn
	    header("Location: menu.php");
	    exit();
    } else {
        // Felmeddelande om fel inloggningsuppgifter angavs
        $errorMessage = "<p style='background-color:Tomato;'>Wrong username or password</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1, shrink-to-fit=no"
    />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Please sign in</title>
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
        <div class="col-12 text-center">
          <a
            href="registration.php"
            class="btn btn-secondary btn-sm active"
            role="button"
            aria-pressed="true"
          >
            Registration
          </a>
        </div>
      </div>
      <div class="row">
        <div class="col-12 text-center mt-4">
          <form class="form-signin" method="post" action="index.php">
	  <?php
	  if(isset($errorMessage))
		print($errorMessage);
		unset($errorMessage);
	  ?>
	  <h2 class="form-signin-heading">Please sign in</h2>
	    
            <div id="error" class="alert alert-danger" role="alert">felllll</div>
            <div id="success" class="alert alert-success" role="alert">success</div>
            <p>
              <label for="username" class="sr-only">Username</label>
              <input
                type="text"
                id="username"
                name="username"
                class="form-control"
                placeholder="Username"
                required
                autofocus
              />
            </p>
            <p>
              <label for="password" class="sr-only">Password</label>
              <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                placeholder="Password"
                required
              />
            </p>
            <button class="btn btn-lg btn-primary btn-block" type="submit">
              Sign in
            </button>
          </form>
        </div>
      </div>
    </div>
    <script type="module" src="../public/js/index.js"></script>
  </body>
</html>

