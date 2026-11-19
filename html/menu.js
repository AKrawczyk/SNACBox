const navLinks = document.querySelector('.nav-links');
const dropdown = document.querySelector('.dropdown');
const dropdownMenu = document.querySelector('.dropdown-menu');
const dropdown2 = document.querySelector('.dropdown2');
const dropdownMenu2 = document.querySelector('.dropdown-menu2');
const dropdown3 = document.querySelector('.dropdown3');
const dropdownMenu3 = document.querySelector('.dropdown-menu3');
// Hamburger Menu Display
document.addEventListener('DOMContentLoaded', nav)

// Sub Menu 1 Display
dropdown.addEventListener('click', () => {
  dropdown.classList.toggle('active');
  dropdownMenu.style.display = dropdown.classList.contains('active') ? 'block' : 'none';
  navLinks.classList.remove('active');
});

// Sub Menu 1 Display
dropdown2.addEventListener('click', () => {
  dropdown2.classList.toggle('active');
  dropdownMenu2.style.display = dropdown2.classList.contains('active') ? 'block' : 'none';
  navLinks.classList.remove('active');
});

// Sub Menu 1 Display
dropdown3.addEventListener('click', () => {
  dropdown3.classList.toggle('active');
  dropdownMenu3.style.display = dropdown3.classList.contains('active') ? 'block' : 'none';
  navLinks.classList.remove('active');
});

// Sub Menus remove display
document.addEventListener('click', (event) => {
  if (!event.target.matches('.nav-links, .nav-links *')) {
    navLinks.classList.remove('active');
  }
  
  if (!event.target.matches('.dropdown, .dropdown *')) {
    dropdown.classList.remove('active');
    dropdownMenu.style.display = 'none';
  }

  if (!event.target.matches('.dropdown2, .dropdown2 *')) {
    dropdown2.classList.remove('active');
    dropdownMenu2.style.display = 'none';
  }

  
  if (!event.target.matches('.dropdown3, .dropdown3 *')) {
    dropdown3.classList.remove('active');
    dropdownMenu3.style.display = 'none';
  }
});

// Close the dropdown if the user clicks outside of it
window.onclick = function(event) {
  if (!event.target.matches('.burger-menu, .dropdown, .dropdown *, .dropdown2, .dropdown2 *, .dropdown3, .dropdown3 *')) {
    BrowserResize();
  }
}

// Close the dropdown if the user resizes browser
function BrowserResize() {
    var dropdowns = document.getElementsByClassName("main-nav");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('show')) {
        openDropdown.classList.remove('show');
      }
    }
}

// Show the main menu if the hamburger icon is clicked
function nav(){
  burgermenu = document.querySelector('.burger-menu');
  mainnav = document.querySelector('.main-nav');
  burgermenu.addEventListener('click', ()=>{
      mainnav.classList.toggle('show')
  })
}
