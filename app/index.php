<?php
include_once "inc/header.php";
?>

<!doctype html>
<html class="no-js" lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Set Your Email Preferences</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <form action="submit.php" method="post">
      <label>
        Email:
        <input type="email" name="email" />
      </label>
      <input type="hidden" name="group[]" value="fordham_magazine" />
      <input type="hidden" name="group[]" value="fordham_news" />
      <div style="position: absolute; left: -5000px;" aria-hidden="true">
        <input type="text" name="b_fu5ju" tabindex="-1" value="">
      </div>
      <input type="submit" name="submit" value="Submit" />
    </form>
  </body>
</html>