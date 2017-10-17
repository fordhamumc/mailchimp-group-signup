<?php
include_once "inc/header.php";
?>

<!doctype html>
<html class="no-js" lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Sign Up</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <style type="text/css">
      .mce-label {
        display: block;
        margin-bottom: 5px;
      }
      .mce-field {
        width: 100%;
        box-sizing: border-box;
      }
      .mce-response {
        margin-top: 1em;
      }
      .mce-error {
        color: #cc0000;
      }
    </style>
    <form id="mce-subscribe" action="submit.php" method="post">
      <label class="mce-label" for="mce-email">Email Address</label>
      <input class="mce-field" type="email" id="mce-email" name="email" required />
      <input type="hidden" name="group[]" value="fordham_magazine" />
      <input type="hidden" name="group[]" value="fordham_news" />
      <div style="position: absolute; left: -5000px;" aria-hidden="true">
        <input type="text" name="b_fu5ju" tabindex="-1" value="">
      </div>
      <input class="subscribe-button" type="submit" name="submit" value="Subscribe" />
      <div id="mce-response" class="mce-response" style="display:none"></div>
    </form>
    <script type="text/javascript">
      (function() {
        document.getElementById('mce-subscribe').addEventListener("submit", function(e){
          e.preventDefault();
          var form = e.target;
          var submitBtn = form.querySelector('input[type=submit]');
          var data = new FormData(form);
          var request = new XMLHttpRequest();
          var responseMsg = document.getElementById('mce-response');

          function btnState(btn, state){
            btn.disabled = !state;
            btn.value = (state) ? 'Subscribe' : 'Sending...';
          }

          request.onreadystatechange = function(){
            responseMsg.innerText = '';
            if (request.readyState < 4) {
              btnState(submitBtn, false);
            }
            if (request.readyState == 4 && request.status == 200) {
              var response = JSON.parse(request.responseText);
              responseMsg.style.display = '';
              if (response.error) {
                responseMsg.classList.add('mce-error');
                responseMsg.classList.remove('mce-success');
              } else {
                responseMsg.classList.remove('mce-error');
                responseMsg.classList.add('mce-success');
              }
              responseMsg.innerText = response.message;
              btnState(submitBtn, true);
            }
          };

          request.open(form.method, form.action);
          request.send(data);
        });
      }());

    </script>
  </body>
</html>