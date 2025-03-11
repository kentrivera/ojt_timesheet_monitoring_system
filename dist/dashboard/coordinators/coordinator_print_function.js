function printDTR() {
  // Create a new window for printing
  const printWindow = window.open("", "_blank", "width=800,height=600");

  // Get the DTR content
  const dtrContent = document.getElementById("dtrContent").cloneNode(true);

  // Add custom print styling
  printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Daily Time Record</title>
            <style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 20px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    table, th, td {
        border: 1px solid #333;
    }
    th, td {
        padding: 8px;
        text-align: left;
        font-size: 12px;
    }
    .supervisor_container {
        display: flex;
        justify-content: flex-end; /* Align to the right */
        margin: 1.5rem; /* Equivalent to m-6 in Tailwind */
        padding: 1rem; /* Equivalent to p-4 in Tailwind */
    }
    .supervisor {
        border-top: 1px solid #ccc; /* Equivalent to border-t border-gray-400 */
        padding-top: 0.5rem; /* Equivalent to pt-2 */
        margin-top: 1.5rem; /* Equivalent to mt-6 */
        text-align: center; /* Center text */
    }
    .supervisor-name {
        font-weight: 500; /* Equivalent to font-medium */
    }
    .supervisor-role {
        color: #6b7280; /* Equivalent to text-gray-500 */
        font-weight: 700; /* Equivalent to font-bold */
    }
</style>
        </head>
        <body>
            ${dtrContent.outerHTML}
        </body>
        </html>
    `);

  // Close the document writing
  printWindow.document.close();

  // Wait for content to load, then print
  printWindow.onload = function () {
    printWindow.print();
    printWindow.close();
  };
}

// Replace the existing print button event listener
document.getElementById("printButton").addEventListener("click", printDTR);
