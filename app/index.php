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
  <div style="position: absolute; left: -5000px;" aria-hidden="true">
    <input type="text" name="b_fu5ju" tabindex="-1" value="">
  </div>
  <input type="submit" name="submit" value="Submit" />
</form>
<?php
include_once "inc/footer.php";
?>