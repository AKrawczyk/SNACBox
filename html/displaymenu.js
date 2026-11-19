// Function to generate the menu HTML
function generateMenu() 
{
    let menuHTML = `
        <nav>
            <img src="menu-icon.svg" height="32" width="32" alt="" class="burger-menu">

            <!-- Menu Home Screen - Home -->
            <ul class="nav-links">
                <li><a href="index.html">Home</a></li>

                <!-- Menu Products -->
                <li class="dropdown">
                    <a href="#">Products</a>
                    <ul class="dropdown-menu">
                        <li><a href="https://snacbox.web-rated.ie" target="_blank" rel="noopener noreferrer"">SNACBox</a></li>
                        <li><a href="https://passwordtools.web-rated.ie" target="_blank" rel="noopener noreferrer"">Password Tools</a></li>
                        <li><a href="https://passwordtools.web-rated.ie/PassGame/passwordGame.html" target="_blank" rel="noopener noreferrer"">Password Mastery Game</a></li>
                    </ul>
                </li>

                <!-- Menu Github -->
                <li class="dropdown2">
                    <a href="#">Github</a>
                    <ul class="dropdown-menu2">
                        <li><a href="https://github.com/AKrawczyk/SNACBox" target="_blank" rel="noopener noreferrer"">SNACBox</a></li>
                        <li><a href="https://github.com/AKrawczyk/Memorable-Password-Generators" target="_blank" rel="noopener noreferrer"">Memorable Password Generators</a></li>
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
            <div class="logo1"><img src="web-rated.png" height="32" width="32"></div>
            <div class="logo2">
                <h3 class="logo2_1">Web-Rated Internet Safty Tools</h3>
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
