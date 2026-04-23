<!DOCTYPE html>
<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['go'])) {
  $hotkey = trim($_POST['hotkey']);
  switch ($hotkey) {
      case "1":
          header("Location: billing.php");
          exit();
      case "2":
          header("Location: billwise_report.php");
          exit();
      case "3":
          header("Location: itemwise_report.php");
          exit();
      case "4":
          header("Location: sale.php");
          exit();
      case "5":
          header("Location: dayclose.php");
          exit();
      case "6":
          header("Location: logout.php");
          exit();
      default:
          $error = "❌ Invalid Hotkey!";
  }
}
?>

<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Restaurant POS System</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content {
      flex: 1;
      background: #fff;
      padding: 20px;
      min-height: 80vh;
      overflow-y: auto;
    }
    iframe {
      width: 100%;
      height: 100%;
      border: none;
      min-height: 80vh;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="top-header">
    <h1>HOTEL NISARGA (2025-2026)</h1>
  </header>

  <!-- Main Container -->
  <div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="top-buttons">
      <div class="menu-title">
      <a href="billing.php" class="kot-btn">KOT BILLING</a>
    </div>


        <!-- Masters -->
        <input type="checkbox" id="menu-master">
        <label class="menu-label" for="menu-master">MASTER</label>
        <ul class="submenu">
          <li><a href="menu_group.php" target="content-frame">Menu Groups</a></li>
          <li><a href="menu_card.php" target="content-frame">Menu Card</a></li>
          
        </ul>

        <!-- Transaction -->
        <input type="checkbox" id="menu-transaction">
        <label class="menu-label" for="menu-transaction">TRANSACTION</label>
        <ul class="submenu">
          <li><a href="change_rate.php" target="content-frame">Change Item Rates</a></li>
        </ul>

        <!-- Reports -->
        <input type="checkbox" id="menu-reports">
        <label class="menu-label" for="menu-reports">REPORTS</label>
        <ul class="submenu">
          <li><a href="#" target="content-frame">Daily Dept. Sale</a></li>
          <li><a href="#" target="content-frame">Billwise Sale</a></li>
          <li><a href="#" target="content-frame">Item Stock Sale</a></li>
        </ul>

        <!-- ROI Reports -->
        <input type="checkbox" id="menu-roi">
        <label class="menu-label" for="menu-roi">ROI REPORTS</label>
        <ul class="submenu">
          <li><a href="#" target="content-frame">Change Password</a></li>
          <li><a href="#" target="content-frame">Day Close Process</a></li>
          <li><a href="#" target="content-frame">Logout</a></li>
        </ul>
      </div>
      <!-- Tables -->
<input type="checkbox" id="menu-tables">
<label class="menu-label" for="menu-tables">TABLES</label>
<ul class="submenu">
  <li><a href="tables.php?type=Restaurant" target="content-frame">Restaurant</a></li>
  <li><a href="tables.php?type=Garden" target="content-frame">Garden</a></li>
  <li><a href="tables.php?type=Parcel" target="content-frame">Parcel</a></li>
  <li><a href="tables.php?type=Zomato" target="content-frame">Zomato</a></li>
  <li><a href="tables.php?type=Swiggy" target="content-frame">Swiggy</a></li>
</ul>


      <!-- Change User button -->
      <div class="bottom-button change-user-btn">
        <button>Change User</button>
      </div>
    </div>

    <!-- ✅ Blank space where pages open -->
    <div class="main-content">
      <iframe name="content-frame"></iframe>
    </div>
  </div>

  <!-- Hot Keys Box -->
<div class="hotkeys-box">
  <form method="POST" style="display:flex; align-items:center; gap:10px;">
    <label for="hotkey-input">Hot Keys Command:</label>
    <input type="text" id="hotkey-input" name="hotkey" placeholder="Enter number (e.g. 1)" required>
    <button type="submit" name="go">Go</button>
    <span class="hotkey-inline">
      1] KOT Billing, 2] Bill Wise Report, 3] Item Wise Report, 
      4] Sale, 5] Day Close, 6] Logout
    </span>
  </form>
</div>


  <!-- Footer -->
  <footer class="footer">
    <div class="footer-left">
      <span>Call Us: 9172081999 / 9890276751 / 9175909744</span> |
      <a href="http://aryansoftwares.in" target="_blank">Visit Us At aryansoftwares.in</a> |
      Change Financial Year
    </div>
    <div class="footer-right">
      <span>© CopyRights Reserved By ARYAN SOFTWARES Version 7.2.9</span><br>
      <span>Developed By ARYAN SOFTWARES | Email: aryansoftpvtltd@gmail.com</span>
    </div>
  </footer>
 </body>
</html>
