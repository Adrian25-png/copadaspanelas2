document.addEventListener("DOMContentLoaded", function () {
    const confirmLink = document.getElementById("confirm-link");
    const modal = document.getElementById("confirm-modal");
    const confirmBtn = document.getElementById("confirm-btn");
    const cancelBtn = document.getElementById("cancel-btn");
    const closeBtn = document.getElementById("close-btn");

    if (confirmLink) {
        confirmLink.addEventListener("click", function (e) {
            e.preventDefault();
            modal.style.display = "block";
        });
    }

    confirmBtn.addEventListener("click", function () {
        window.location.href = confirmLink.href;
    });

    cancelBtn.addEventListener("click", function () {
        modal.style.display = "none";
    });

    closeBtn.addEventListener("click", function () {
        modal.style.display = "none";
    });

    window.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
});