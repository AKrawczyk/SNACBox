<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <meta http-equiv="Content-Script-Type" content="text/javascript" />
   <meta name="viewport" content="width=device-width, initial-scale=1" />
   <meta http-equiv="x-dns-prefetch-control" content="off">
   <meta name="mobile-web-app-capable" content="yes"/>
   <meta name="apple-mobile-web-app-capable" content="yes"/>
   <meta name="apple-mobile-web-app-status-bar-style" content="default">
   <link rel="stylesheet" type="text/css" media="screen" href="cascade.css" />
   
   <!-- Standard favicon -->
   <link rel="icon" href="favicon.ico" type="image/x-icon"> 

   <!-- Apple Touch Icon -->
   <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">

   <!-- Android Chrome Icon -->
   <link rel="icon" sizes="192x192" href="android-chrome-icon.png">

   <!-- Other sizes for different devices -->
   <link rel="icon" sizes="512x512" href="android-chrome-512x512.png">
   <title>SNAC Box</title>
   <style>
    /* (A) SHARED CLASS */
    .box2 {
      width: auto;
      margin: 10px;
      padding: 10px;
      font-size: 18px;
      text-align: center;
    }
    
    /* (B) BOX VARIATIONS */
    /* (B1) INFORMATION BOX */
    .info {
      border-radius: 10px;
      color: brown;
      background: cornsilk;
      border: 1px solid burlywood;
    }
    
    /* (B2) WARNING BOX */
    .warn {
      border-radius: 10px;
      color: darkmagenta;
      background: lightpink;
      border: 1px solid darkred;
    }
    
    /* (B3) SUCCESS */
    .ok {
      color: darkgreen;
      background: greenyellow;
      border: 1px solid darkgreen;
    }
    * {
        box-sizing: border-box;
    }
    .title-banner {
        background-color: blue;
        color: white;
        text-align: center;
        font-size: 30px;
        padding: 20px;
    }
    .wrapper
    {
        width: 800px;
        margin: 0 auto;
    }
    table
    {
      font-family: arial, sans-serif;
      border-collapse: collapse;
    }

    td, th
    {
      border: 1px solid #dddddd;
      text-align: left;
        vertical-align: top;
      padding: 8px;
      padding-right: 40px;
    }

    tr:nth-child(even)
    {
      background-color: #dddddd;
    }
  </style>
</head>
<body>
<div id="menubar" class="menu-nav">	
   <div class="logo1">
      <span class="hostname"><a href="index.php">Web Guard</a></span>
      <h3 class="logo2_1">by Web-Rated</h3>
   </div>
   <div class="logo3">
      <span id="indicators"></span>
   </div>
   <div class="logo2">
      <h3 class="logo2_1">Device Information</h3>
   </div>
</div>
  <p>
<?php
//<div class="wrapper">
    //<div class="container-fluid">
        //<div class="row">
            //<div class="col-md-12">
?>
<?php
// Define variables and initialize with empty values

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

$client_ip = $_SERVER['REMOTE_ADDR'];
?>
<!-- (B1) INFORMATION -->
<div class="box2 info">
  <h2>&#9432; Device IP</h2>

<p><h4><i><?php echo $client_ip ?></i></h4></p>
<b>This information is required by the Administrator to allow this device access to the internet.</b>
</div>
 
<!-- (B2) WARNING -->
<div class="box2 warn">
  <h2>&#9888; WARNING</h2>
<b>No internet or network access until the Administrator has granted you access.</b>
</br>
</div>
<?php
                //</div>
            //</div>
        //</div>
    //</div>
?>
</body>
</html>
