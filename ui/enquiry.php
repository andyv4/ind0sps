<?php

  $postmessage = '';
  if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $headers = 'From: admin@indosps.co.id' . "\r\n" .
        'Reply-To: admin@indosps.co.id' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    $sent = mail('andyv4@gmail.com', 'New Enquiry from Andy', 'Hello ' . print_r($_POST, 1), $headers);

    $postmessage = $sent ? 'Mail sent.' : 'Mail not sent.';
  }

?>
<div class="enquiry">
  <div class="wrapper">
asdasdasdsa
    <small>Please fill form below in order to send enquiry to us. We will reply you to the email you filled below.</small>
    <br /><br /><br />

    <form method="post">

      <label>Full Name</label><small>(Required)</small>
      <br />
      <input name="fullname" type="text" style="width:300px" />

      <br /><br />

      <label>Email</label><small>(Required)</small>
      <br />
      <input name="email" type="text" style="width:300px" />

      <br /><br />

      <label>Company Name</label><small>(Required)</small>
      <br />
      <input name="companyname" type="text" style="width:300px" />

      <br /><br />

      <label>Message</label>
      <br />
      <textarea name="message" style="width:300px;height:100px"></textarea>

      <br /><br />

      <button>SUBMIT</button>

    </form>

    <h3><?=$postmessage?></h3>

  </div>
</div>