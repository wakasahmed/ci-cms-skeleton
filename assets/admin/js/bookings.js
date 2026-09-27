(function () {
    "use strict";
    var deleteNoteModal = document.getElementById("deleteNoteModal");
    var deleteNoteConfirm = document.getElementById("deleteNoteConfirm");
    var deleteNoteTargetForm = null;
    if (deleteNoteModal && deleteNoteConfirm) {
        deleteNoteModal.addEventListener("show.bs.modal", function (event) {
            var trigger = event.relatedTarget;
            var formId = trigger ? trigger.getAttribute("data-note-form") : null;
            deleteNoteTargetForm = formId ? document.getElementById(formId) : null;
        });
        deleteNoteConfirm.addEventListener("click", function () {
            if (deleteNoteTargetForm) {
                deleteNoteTargetForm.submit();
            }
        });
    }
    if (window.jQuery) {
        window.jQuery(function ($) {
            $("form[data-booking-form]").each(function () {
                var validator = $(this).data("validator");
                if (validator) {
                    // Select2 hides the required guide select behind its visible control.
                    validator.settings.ignore = ":hidden:not([data-user-select])";
                }
            });
        });
    }
    var percentage = document.getElementById("refund-percent");
    var amount = document.getElementById("refund-amount");
    if (percentage && amount) {
        var $percentage = window.jQuery ? window.jQuery(percentage) : null;
        var applyPercentage = function () {
            if (percentage.value !== "custom") {
                amount.value = Math.round(
                    (Number(percentage.dataset.paid) *
                        Number(percentage.value)) /
                        100
                );
            }
        };
        // Select2 announces changes through jQuery events only, not native ones.
        if ($percentage) {
            $percentage.on("change", applyPercentage);
        } else {
            percentage.addEventListener("change", applyPercentage);
        }
        amount.addEventListener("input", function () {
            percentage.value = "custom";
            if ($percentage) {
                // Refresh the Select2 display without running applyPercentage.
                $percentage.trigger("change.select2");
            }
        });
    }
    document.querySelectorAll(".booking-modal").forEach(function (modal) {
        modal.addEventListener("shown.bs.modal", function () {
            var field = modal.querySelector("input, select, textarea");
            if (field && field.hasAttribute("data-user-select")) {
                var selection = modal.querySelector(".select2-selection");
                if (selection) {
                    selection.focus();
                }
            } else if (field) {
                field.focus();
            }
        });
    });
    var reopen = document.getElementById("booking-reopen");
    if (reopen && window.bootstrap) {
        var target = document.getElementById(reopen.dataset.modal);
        if (target) {
            bootstrap.Modal.getOrCreateInstance(target).show();
        }
    }
})();
