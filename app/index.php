<?php
include_once "inc/header.php";
?>
<form action="submit.php" method="post">
  <label>
    Email:
    <input type="email" name="email" />
  </label>
  <input type="hidden" name="group[]" value="fordham_magazine" />
  <input type="hidden" name="group[]" value="fordham_news" />
  <input type="submit" name="submit" value="Submit" />
</form>


<?php
include_once "inc/footer.php";