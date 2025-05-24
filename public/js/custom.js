// set default theme to dark
const defaultTheme = "dark";

// function to toggle between light and dark themes
function toggleTheme() {

  let theme;

  if (localStorage.getItem("getcontact_web#theme") == null) {
    theme = document.documentElement.dataset.bsTheme;
    if (theme === null || theme === undefined || theme === "") {
      theme = defaultTheme;
    }
    document.documentElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem("getcontact_web#theme", theme);
  } else {
    theme = localStorage.getItem("getcontact_web#theme");
  }

  if (theme === "light") {
    document.querySelectorAll(".btn-outline-light").forEach(function (element) {
      element.classList.remove("btn-outline-light");
      element.classList.add("btn-outline-dark");
    });
  } else {
    document.querySelectorAll(".btn-outline-dark").forEach(function (element) {
      element.classList.remove("btn-outline-dark");
      element.classList.add("btn-outline-light");
    });
  }

  document.querySelectorAll("[data-bs-theme]").forEach(function (element) {
    element.setAttribute("data-bs-theme", theme);
  });

}

// add event listener to call toggleTheme() after the entire page is loaded
window.addEventListener("load", function () {

  toggleTheme();

});

// add event listener to toggle theme when the button is clicked
document.querySelectorAll("[data-bs-theme-value]").forEach(value => {

  value.addEventListener("click", () => {
    const theme = value.getAttribute("data-bs-theme-value");
    localStorage.setItem("getcontact_web#theme", theme);
    toggleTheme();
  });

});

const notyfOption = {
  duration: 2500,
  position: {
    x: "center",
    y: "top",
  },
  // types: [
  //   {
  //     type: "warning",
  //     background: "orange",
  //     icon: {
  //       className: "material-icons",
  //       tagName: "i",
  //       text: "warning",
  //     },
  //   },
  //   {
  //     type: "error",
  //     background: "indianred",
  //     duration: 2000,
  //     dismissible: true,
  //   },
  // ],
};