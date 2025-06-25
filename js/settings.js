(function() {
  const config3 = document.querySelector(".tailwind3config_row");
  const config4 = document.querySelector(".tailwind4config_row");

  let versionSelect = document.querySelector("#tailwind10f_version");

  console.log(config3)
  console.log(config4)
  console.log(versionSelect)

  if (config3 && config4 && versionSelect) {
    config3.style.display = 'none';
    config4.style.display = 'none';

    const toggleConfigVisibility = function() {
      if (versionSelect.value == '4') {
        config3.style.display = 'none';
        config4.style.display = 'table-row';
      } else {
        config3.style.display = 'table-row';
        config4.style.display = 'none';
      }
    }

    toggleConfigVisibility();
    versionSelect.addEventListener("change", () => {
      toggleConfigVisibility();
    });

  }


})();
