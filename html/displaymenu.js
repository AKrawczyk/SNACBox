// Function to generate the menu HTML
function generateMenu() 
{
    let menuHTML = `
        <nav>
            <img src="/html/menu-icon.svg" height="32" width="32" alt="" class="burger-menu">

            <!-- Menu Home Screen - Home -->
            <ul class="nav-links">
                <li><a href="http://www.web-rated.ie">Home</a></li>

                <!-- Menu Products -->
                <li class="dropdown">
                    <a href="#">Info</a>
                    <ul class="dropdown-menu">
                        <li><a href="index.html">SNACBox Gateway</a></li>
                        <li><a href="system-design.html">Syatem Design</a></li>
                    </ul>
                </li>

                <!-- Menu Github -->
                <li class="dropdown2">
                    <a href="#">Setup</a>
                    <ul class="dropdown-menu2">
                        <li><a href="hardware.html">Compatible Hardware</a></li>
                        <li><a href="device-setup.html">Device Setup</a></li>
                        <li><a href="home-setup.html">Home Setup</a></li>
                    </ul>
                </li>

                <!-- Menu Help -->
                <li class="dropdown3">
                    <a href="#">Help</a>
                    <ul class="dropdown-menu3">
                        <li><a href="info.html">About</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <div class="logo">
            <div class="logo1"><img src="/html/web-rated.png" height="32" width="32"></div>
            <div class="logo2">
                <h3 class="logo2_1">Web Rated - SNACBox</h3>
            </div>
        </div>`;
    return menuHTML;
}
  
// Function to render the menu in the specified container
function renderMenu(containerId) 
{
    const menuHTML = generateMenu();
    document.getElementById(containerId).innerHTML = menuHTML;
}
