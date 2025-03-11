document.addEventListener("DOMContentLoaded", function () {
  // Common DOM element references
  const elements = {
    yearSelect: document.getElementById("yearSelect"),
    monthSelect: document.getElementById("monthSelect"),
    toggleModeBtn: document.getElementById("toggleMode"),
    timesheetTable: document.getElementById("timesheetTable"),
    dtrTable: document.getElementById("dtrTable"),
    dtrButtons: document.getElementById("dtrButtons"),
    downloadButton: document.getElementById("downloadButton"),
    showTimesheetBtn: document.getElementById("showTimesheet"),
    showActivitiesBtn: document.getElementById("showActivities"),
    timesheetView: document.getElementById("timesheetView"),
    activitiesView: document.getElementById("activitiesView"),
  };

  // URL parameter handling
  const urlParams = new URLSearchParams(window.location.search);
  const traineeId = urlParams.get("id");
  const currentMode = urlParams.get("mode") || "timesheet";

  // Initialize interface mode
  function initializeMode() {
    if (currentMode === "dtr") {
      elements.timesheetTable.classList.add("hidden");
      elements.dtrTable.classList.remove("hidden");

      // Update toggle mode button
      const modeIcon = elements.toggleModeBtn.querySelector("i");
      const modeText = elements.toggleModeBtn.querySelector("span");

      modeIcon.classList.remove("fa-calendar-check");
      modeIcon.classList.add("fa-list");
      modeText.textContent = "Switch to Timesheet";

      // Toggle DTR buttons visibility
      if (elements.dtrButtons) {
        elements.dtrButtons.classList.remove("hidden");
      }
    }
  }

  // Update URL with selected parameters
  function updateURL() {
    urlParams.set("year", elements.yearSelect.value);
    urlParams.set("month", elements.monthSelect.value);
    window.location.href =
      window.location.pathname + "?" + urlParams.toString();
  }

  // Toggle mode between timesheet and DTR
  function toggleMode() {
    const newMode = currentMode === "timesheet" ? "dtr" : "timesheet";
    urlParams.set("mode", newMode);
    window.location.href =
      window.location.pathname + "?" + urlParams.toString();
  }

  // Image preview modal functionality
  function openPreviewModal(src, title) {
    const modal = document.getElementById("previewModal");
    const modalImage = document.getElementById("modalImage");
    const modalTitle = document.getElementById("modalTitle");

    modalImage.src = src;
    modalTitle.textContent = title;
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
  }

  function closePreviewModal() {
    const modal = document.getElementById("previewModal");
    modal.classList.add("hidden");
    document.body.style.overflow = "auto";
  }

  // Tab switching functionality
  function setupTabSwitching() {
    if (elements.showTimesheetBtn && elements.showActivitiesBtn) {
      elements.showTimesheetBtn.addEventListener("click", () => {
        elements.timesheetView.classList.remove("hidden");
        elements.activitiesView.classList.add("hidden");

        elements.showTimesheetBtn.classList.remove(
          "bg-gray-500",
          "hover:bg-gray-600"
        );
        elements.showTimesheetBtn.classList.add(
          "bg-primary-600",
          "hover:bg-primary-700"
        );

        elements.showActivitiesBtn.classList.remove(
          "bg-primary-600",
          "hover:bg-primary-700"
        );
        elements.showActivitiesBtn.classList.add(
          "bg-gray-500",
          "hover:bg-gray-600"
        );
      });

      elements.showActivitiesBtn.addEventListener("click", () => {
        elements.activitiesView.classList.remove("hidden");
        elements.timesheetView.classList.add("hidden");

        elements.showActivitiesBtn.classList.remove(
          "bg-gray-500",
          "hover:bg-gray-600"
        );
        elements.showActivitiesBtn.classList.add(
          "bg-primary-600",
          "hover:bg-primary-700"
        );

        elements.showTimesheetBtn.classList.remove(
          "bg-primary-600",
          "hover:bg-primary-700"
        );
        elements.showTimesheetBtn.classList.add(
          "bg-gray-500",
          "hover:bg-gray-600"
        );
      });
    }
  }

  // Event Listeners
  function setupEventListeners() {
    if (elements.yearSelect && elements.monthSelect) {
      elements.yearSelect.addEventListener("change", updateURL);
      elements.monthSelect.addEventListener("change", updateURL);
    }

    if (elements.toggleModeBtn) {
      elements.toggleModeBtn.addEventListener("click", toggleMode);
    }

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        closePreviewModal();
      }
    });

    // Expose modal functions globally
    window.openPreviewModal = openPreviewModal;
    window.closePreviewModal = closePreviewModal;
  }

  // Initialize everything
  function init() {
    initializeMode();
    setupTabSwitching();
    setupEventListeners();
  }

  // Run initialization
  init();
});
