<?php
  require("config.php");

  session_start();

  //Login method
  if($_SERVER['REQUEST_METHOD'] == "POST" && $_POST["action"] == "login"){
    header('Content-Type: application/json');
    $ldap = ldap_connect(LDAP_SERVER);
    if ($bind = ldap_bind($ldap, $_POST['username'] . '@' . LDAP_DOMAIN, $_POST['password'])) {
      $_SESSION['auth'] = true;
      $_SESSION['username'] = $_POST['username'];
      echo json_encode(array(
        'status' => 'success',
        'message' => 'Login Successful'
      ));
    } else {
      echo json_encode(array(
        'status' => 'fail',
        'message' => 'Login Unsuccessful',
        'more' => ldap_error($ldap)
      ));
    }
    die;
  }
  //add key method
  if($_SERVER['REQUEST_METHOD'] == "POST" && $_POST["action"] == "add-key"){
    header('Content-Type: application/json');

    //check if key is validate
    $path = tempnam(sys_get_temp_dir(), 'key');

    $handle = fopen($path, "w");
    fwrite($handle, $_POST['key']);
    fclose($handle);

    exec("ssh-keygen -lf {$path}", $output, $retval);

    if($retval != 0){
      echo json_encode(array(
        'status' => 'error',
        'message' => 'Error adding key: invalid public key format'
      ));
      die;
    }

    //add key to ldap property
    $ldap = ldap_connect(LDAP_SERVER);

    if ($bind = ldap_bind($ldap, LDAP_QUERY_USER . '@' . LDAP_DOMAIN, LDAP_QUERY_PASSWORD)) {
      $results = ldap_search($ldap, LDAP_BASE_DN, "(samaccountname={$_POST['username']})",array("sshKeys"));
      $entries = ldap_get_entries($ldap, $results);

      unset($entries[0]["sshkeys"]["count"]);

      $entry["sshkeys"] = $_POST['key'];

      $ret = ldap_mod_add($ldap, $entries[0]["dn"], $entry);

      if($ret == FALSE){
        echo json_encode(array(
          'status' => 'error',
          'message' => 'Error adding key: ' . ldap_error($ldap)
        ));
      } else {
        echo json_encode(array(
          'status' => 'success',
          'message' => 'Key Added'
        ));
      }
      die;
    }
    echo json_encode(array(
      'status' => 'error',
      'message' => 'Error adding key: LDAP BIND FAILED'
    ));
    unlink($path);
    die;
  }

  //delete key method
  if($_SERVER['REQUEST_METHOD'] == "POST" && $_POST["action"] == "delete-key"){
    header('Content-Type: application/json');
    $ldap = ldap_connect(LDAP_SERVER);

    if ($bind = ldap_bind($ldap, LDAP_QUERY_USER . '@' . LDAP_DOMAIN, LDAP_QUERY_PASSWORD)) {
      $results = ldap_search($ldap, LDAP_BASE_DN, "(samaccountname={$_POST['username']})",array("sshKeys"));
      $entries = ldap_get_entries($ldap, $results);

      $entry["sshkeys"] = $_POST['key'];

      $ret = ldap_mod_del($ldap, $entries[0]["dn"], $entry);
      if($ret == FALSE){
        echo json_encode(array(
          'status' => 'error',
          'message' => 'Error deleting key: ' . ldap_error($ldap)
        ));
      } else {
        echo json_encode(array(
          'status' => 'success',
          'message' => 'Key Deleted'
        ));
      }

      die;
    }
    echo json_encode(array(
      'status' => 'error',
      'message' => 'Error delete key: LDAP BIND FAILED'
    ));
    die;
  }

  //get list of keys in flat file format
  if($_SERVER['REQUEST_METHOD'] == "GET" && isset($_GET['username'])){
    $ldap = ldap_connect(LDAP_SERVER);
    if ($bind = ldap_bind($ldap, LDAP_QUERY_USER . '@' . LDAP_DOMAIN, LDAP_QUERY_PASSWORD)) {
      $results = ldap_search($ldap, LDAP_BASE_DN, "(samaccountname={$_GET['username']})",array("sshKeys"));
      $entries = ldap_get_entries($ldap, $results);

      unset($entries[0]["sshkeys"]["count"]);

      echo implode("\n", $entries[0]["sshkeys"]);
  }
    die;
  }

  //logout method
  if($_SERVER['REQUEST_METHOD'] == "GET" && isset($_GET['logout'])){
    session_destroy();
    $url = strtok($_SERVER["REQUEST_URI"],'?');
    header("Location: {$url}");

    die;
  }

  if(isset($_SESSION['auth']) && $_SESSION['auth'] == true){
    $ldap = ldap_connect(LDAP_SERVER);
    if ($bind = ldap_bind($ldap, LDAP_QUERY_USER . '@' . LDAP_DOMAIN, LDAP_QUERY_PASSWORD)) {
      $results = ldap_search($ldap, LDAP_BASE_DN, "(samaccountname={$_SESSION['username']})",array("sshKeys"));
      $entries = ldap_get_entries($ldap, $results);

      unset($entries[0]["sshkeys"]["count"]);
      $keys = $entries[0]["sshkeys"];

    }
  }


?>
<html>
  <head>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootswatch/3.3.7/flatly/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <link href='//fonts.googleapis.com/css?family=Varela+Round' rel='stylesheet' type='text/css'>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.13.1/jquery.validate.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <link rel="stylesheet" href="style.css">
  </head>

  <body>
    <?php if(!isset($_SESSION['auth'])): ?>
      <div class="text-center" style="padding:50px 0">
        <div class="logo">Login</div>
        <div class="login-form-1">
          <form id="login-form" class="text-left">
            <div class="login-form-main-message"></div>
            <div class="main-login-form">
              <div class="login-group">
                <div class="form-group">
                  <label for="username" class="sr-only">Username</label>
                  <input type="text" class="form-control" id="username" name="username" placeholder="username">
                </div>
                <div class="form-group">
                  <label for="password" class="sr-only">Password</label>
                  <input type="password" class="form-control" id="password" name="password" placeholder="password">
                </div>
                <input type="hidden" name="action" value="login">
              </div>
              <button type="submit" class="login-button"><i class="fa fa-chevron-right"></i></button>
            </div>
            <div class="etc-login-form">
              <p>My Company Inc.</p>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['auth']) && $_SESSION['auth'] == true): ?>
      <nav class="navbar navbar-default">
        <div class="container-fluid">
          <!-- Brand and toggle get grouped for better mobile display -->
          <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
              <span class="sr-only">Toggle navigation</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">My Company - <?php echo $_SESSION['username']; ?></a>
          </div>

          <!-- Collect the nav links, forms, and other content for toggling -->
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
              <li><a href="?logout">Logout</a></li>
            </ul>
          </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
      </nav>
      <div class="container">
        <div class="span12 text-center">
          <button id="addKey" type="button" class="btn btn-default btn-md" data-username="<?php echo $_SESSION['username']; ?>">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add Key
          </button>
        </div>
        <br/>
        <table class="table table-striped table-hover">
          <thead class="thead-default">
            <tr>
              <th>Key</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if(count($keys) <= 0): ?>
              <tr>
                <td>No</td>
                <td>Keys</td>
              </tr>
            <?php endif; ?>
            <?php foreach ($keys as $key): ?>
              <tr>
                <td><?php echo explode(' ', $key)[2]; ?></td>
                <td>
                  <button type="button" class="btn btn-default btn-sm viewKey" data-key='<?php echo $key; ?>'>View</button>&nbsp;
                  <button type="button" class="btn btn-danger btn-sm deleteKey" data-key='<?php echo $key; ?>' data-username="<?php echo $_SESSION['username']; ?>">Delete</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </body>

  <script src="script.js"></script>
</html>
