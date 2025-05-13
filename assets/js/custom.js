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

// function to download gtc result as image
function downloadResultTagsToImage(button) {

  const captureElement = document.getElementById('result-tags');
  const censoredPhoneNumber = button.getAttribute("data-censored-phone-number");

  Swal.fire({
    title: 'Confirmation',
    text: "Are you sure you want to download the result as an image?",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      html2canvas(captureElement).then(function (canvas) {

        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0'); // month starts from 0
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const currentLocalDateTime = `${year}${month}${day}_${hours}${minutes}${seconds}`;

        const imageData = canvas.toDataURL('image/png');

        const link = document.createElement('a');
        link.href = imageData;
        if (censoredPhoneNumber) {
          link.download = `gtc_result_${censoredPhoneNumber}_${currentLocalDateTime}.png`;
        } else {
          link.download = `gtc_result_${currentLocalDateTime}.png`;
        }

        link.click();
      });
    }
  });

};

// validate form before submit
function submitForm() {
  let isPhoneNumberValid = true;
  const $phoneInput = $("#phone-number");
  const phoneNumber = $phoneInput.val();
  const $phoneFeedback = $phoneInput.next("div.invalid-feedback");

  $phoneInput.removeClass("is-invalid is-valid");

  if (!phoneNumber) {
    $phoneInput.addClass("is-invalid");
    $phoneFeedback.text("Phone number is invalid");
    isPhoneNumberValid = false;
  } else {
    const digitsOnly = str => /^[0-9]+$/.test(str);

    if (!digitsOnly(phoneNumber)) {
      $phoneInput.addClass("is-invalid");
      $phoneFeedback.text("Only digits are allowed in phone number!");
      isPhoneNumberValid = false;
    } else if (!(phoneNumber.startsWith("0") || phoneNumber.startsWith("62"))) {
      $phoneInput.addClass("is-invalid");
      $phoneFeedback.text("Phone number prefix is invalid");
      isPhoneNumberValid = false;
    }
  }

  if (isPhoneNumberValid) {
    $phoneInput.addClass("is-valid");
    $phoneFeedback.text("");
  } else {
    $phoneInput.removeClass("is-valid");
  }

  // Final Key Validation
  let isFinalKeyValid = true;
  const $finalKeyInput = $("#final-key");
  const finalKey = $finalKeyInput.val();
  const $finalKeyFeedback = $finalKeyInput.next("div.invalid-feedback");

  $finalKeyInput.removeClass("is-invalid is-valid");

  if (!finalKey) {
    $finalKeyInput.addClass("is-invalid");
    $finalKeyFeedback.text("Final key is invalid");
    isFinalKeyValid = false;
  }

  if (isFinalKeyValid) {
    $finalKeyInput.addClass("is-valid");
    $finalKeyFeedback.text("");
  } else {
    $finalKeyInput.removeClass("is-valid");
  }

  // Token Validation
  let isTokenValid = true;
  const $tokenInput = $("#token");
  const token = $tokenInput.val();
  const $tokenFeedback = $tokenInput.next("div.invalid-feedback");

  $tokenInput.removeClass("is-invalid is-valid");

  if (!token) {
    $tokenInput.addClass("is-invalid");
    $tokenFeedback.text("Token is invalid");
    isTokenValid = false;
  }

  if (isTokenValid) {
    $tokenInput.addClass("is-valid");
    $tokenFeedback.text("");
  } else {
    $tokenInput.removeClass("is-valid");
  }

  // Source Type Validation
  let isSourceTypeValid = true;
  const $sourceTypeInput = $("input[name='source_type']");
  const sourceType = $sourceTypeInput.val();
  const $sourceTypeFeedback = $sourceTypeInput.nextAll("div.invalid-feedback").first();

  $sourceTypeInput.removeClass("is-invalid is-valid");

  if (!sourceType) {
    $sourceTypeInput.addClass("is-invalid");
    $sourceTypeFeedback.text("Source type is invalid");
    isSourceTypeValid = false;
  }

  if (!$sourceTypeInput.is(":checked")) {
    $sourceTypeInput.addClass("is-invalid");
    $sourceTypeFeedback.text("Source type is not checked");
    isSourceTypeValid = false;
  }

  if (isSourceTypeValid) {
    $sourceTypeInput.addClass("is-valid");
    $sourceTypeFeedback.text("");
  } else {
    $sourceTypeInput.removeClass("is-valid");
  }

  // Final Decision
  if (!isPhoneNumberValid || !isFinalKeyValid || !isTokenValid || !isSourceTypeValid) {
    console.log("false");
    new Notyf(notyfOption).error("Please check the form");
    return false;
  }

  Swal.fire({
    title: 'Are you sure?',
    text: 'You are about to submit the form.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes'
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById("form").submit();
      return false;
    }
  });
}

$(document).ready(function () {

  // fetch getcontact credentials data from API
  $.ajax({
    url: "api/credentials.php",
    type: "GET",
    dataType: "json",
    success: function (response) {
      let selectElement = $("#final-key-token");
      selectElement.empty();
      selectElement.append('<option value="">-- Choose Final Key & Token --</option>');
      $.each(response.data, function (index, item) {
        console.log(item);
        selectElement.append(`<option value="${item.finalKey}[delim]${item.token}">${item.account}</option>`);
      });
    },
    error: function (xhr, status, error) {
      console.error(xhr);
      console.error(status);
      console.error("Error fetching data: " + error);
    }
  });

  // event listener for input phone number
  $("#phone-number").on("input", function (e) {
    $(this).val(function (index, value) {
      return value.replace(/\D/g, "");
    });
  });

  // event listener for paste phone number
  $("#phone-number").on("paste", function (event) {
    // get data copied from clipboard
    let clipboardData = event.originalEvent.clipboardData || window.clipboardData;
    let pastedData = clipboardData.getData("text");

    // clean data from non-numeric characters
    let cleanedData = pastedData.replace(/\D/g, "");

    // place the cleaned data back into the input
    $(this).val(cleanedData);

    event.preventDefault();
  });

  // event listener for change event on select final key & token
  $('#final-key-token').on('change', function () {
    $("#display-view-profile-limit").text(`Remaining: -/-`);
    $("#display-view-tags-limit").text(`Remaining: -/-`);
    $("#display-expired-at").text(`Expired at: -`);

    const [finalKey, token] = $("#final-key-token").val().split("[delim]");

    console.log(finalKey);
    console.log(token);

    $("#final-key").val(finalKey);
    $("#token").val(token);

    const formData = {
      finalKey: finalKey,
      token: token,
    };

    Swal.fire({
      title: 'Please wait...',
      text: 'Getting subscription data...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: "api/subscription.php",
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify(formData),
      dataType: "json",
      success: function (response) {
        console.log(response);

        Swal.close();

        const searchRemainingCount = response.data.info.search.remainingCount ?? "-";
        const searchLimit = response.data.info.search.limit ?? "-";
        const numberDetailRemainingCount = response.data.info.numberDetail.remainingCount ?? "-";
        const numberDetailLimit = response.data.info.numberDetail.limit ?? "-";
        const receiptEndDate = response.data.info.receiptEndDate ?? "-";

        $("#display-view-profile-limit").text(`Remaining: ${searchRemainingCount}/${searchLimit}`);
        $("#display-view-tags-limit").text(`Remaining: ${numberDetailRemainingCount}/${numberDetailLimit}`);
        $("#display-expired-at").text(`Expired at: ${receiptEndDate}`);

        if (Number.isInteger(searchRemainingCount) && searchRemainingCount > 0 ||
          Number.isInteger(numberDetailRemainingCount) && numberDetailRemainingCount > 0) {
          $("#submit-btn").prop("disabled", false);
        } else {
          $("#submit-btn").prop("disabled", true);
        }
      },
      error: function (xhr, status, error) {
        console.error(xhr.responseText);
        console.error(status);
        console.error("Error fetching data: " + error);
        Swal.close();
        let response = JSON.parse(xhr.responseText);
        Swal.fire({
          icon: 'error',
          title: 'Failed to get subscription data',
          text: response.message || "An error occurred",
        });
        $("#submit-btn").prop("disabled", true);
      },
    });
  });

  // event listener for change event on checkbox use different credentials
  $('#use-different-credentials').change(function () {
    const isChecked = this.checked;

    $('#final-key-token').prop('disabled', !isChecked);
    $('#final-key').prop('readonly', !isChecked);
    $('#token').prop('readonly', !isChecked);

    if (isChecked) {
      $('#final-key').val('');
      $('#token').val('');
    } else {
      $('#final-key-token option:first').prop('selected', true);
      $('#final-key').val('3a2adf118bb013b99f492c8419592cd7940cdb344320e02aa74a1b877094886a');
      $('#token').val('bxuIUB07327befeadca081fdc2d97d39dd06734a623dc37a172572371f');

      $('#display-view-profile-limit').text('Remaining: -/-');
      $('#display-view-tags-limit').text('Remaining: -/-');
      $('#display-expired-at').text('Expired at: -');

      $('#submit-btn').prop('disabled', true);
    }
  });
});