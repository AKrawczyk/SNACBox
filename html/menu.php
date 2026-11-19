<?php
function menu()
{
echo '<!-- Menu for website -->';
echo '<nav class="main-nav">';
echo '<img src="menu-icon.svg" height="32" width="32" alt="" class="burger-menu">';

//Menu Home Screen - Access Rights
echo '<ul class="nav-links">';
echo '<li><a href="index.php">Access</a></li>';

//Menu Settings
echo '<li class="dropdown">';
echo '<a href="#">Settings</a>';
echo '<ul class="dropdown-menu">';
echo '<li><a href="schedule.php">Schedule Access</a></li>';
echo '<li><a href="#">Setup 2</a></li>';
echo '<li><a href="#">Setup 3</a></li>';
echo '<li><a href="#">Setup 4</a></li>';
echo '</ul>';

//Menu Advanced Settings
echo '<li class="dropdown2">';
echo '<a href="#">Advanced</a>';
echo '<ul class="dropdown-menu2">';
echo '<li><a href="http://192.168.10.1:3000" target="_blank">AD Guard Clients</a></li>';
echo '<li><a href="#">Reboot System</a></li>';
echo '</ul>';

//Menu Help
echo '<li class="dropdown3">';
echo '<a href="#">Help</a></li>';
echo '<ul class="dropdown-menu3">';
echo '<li><a href="#">Setup Guide</a></li>';
echo '<li><a href="#">Logs</a></li>';
echo '<li><a href="#">About</a></li>';
echo '</ul>';

echo '<div class="logo">';
echo '<div class="logo1"><img src="web-rated.png" height="32" width="32"></div>';
echo '<div class="logo2">';
echo '<h3 class="logo2_1">Web-Rated </h3>';
echo '</div>';
echo '</div>';
echo '</nav>';
}
?>