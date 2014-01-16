<?php
   $email = "dwalton76@gmail.com";
   $subject = "New password for www.wallofbricks.com";
   $message = "Your new password is FOO";
  /* $headers = 'From: help@wallofbricks.com' . "\r\n" .
              'Reply-To: help@wallofbricks.com' . "\r\n" .
              'X-Mailer: PHP/' . phpversion();
   mail($email, $subject, $message, $headers);
   */
   mail($email, $subject, $message);
?>
