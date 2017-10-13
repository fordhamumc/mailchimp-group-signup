<?php
include_once "inc/header.php";
?>

<pre>
<?php

use Email\User;

$postModel = array(
  'email'   => FILTER_SANITIZE_EMAIL,
  'group'   => array( 'filter'  => FILTER_SANITIZE_STRING,
                      'flags'   => FILTER_FORCE_ARRAY )
);
$inputs = filter_input_array(INPUT_POST, $postModel);

if (file_exists(dirname(__FILE__) . '/data-qa.ini')) {
  $credentials = parse_ini_file(__DIR__ . "/data-qa.ini", true);
} else if (file_exists(dirname(__FILE__) . '/data.ini')) {
  $credentials = parse_ini_file(__DIR__ . "/data.ini", true);
} else {
  exit("unable to open credentials file");
}
foreach ($inputs['group'] as $group) {
  if (!array_key_exists($group, $credentials["newsletters"])) exit("group key not found: {$group}");
}
$user = new User($credentials, $inputs['email']);
print_r($inputs);
print_r($user);
?>

</pre>


<?php
include_once "inc/footer.php";