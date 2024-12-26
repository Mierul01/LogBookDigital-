$(document).ready(function() {
  // Initialize DataTable
  var dataTable = $('#productTable').DataTable({
      "paging": true,
      "lengthMenu": [
          [5, 10, 20, 30, -1], 
          ['5', '10', '20', '30', 'All']
      ],
      "lengthChange": false, // Disable length change
      "searching": false, // Disable search
      "info": false, // Disable info
      "order": [[1, 'asc']], // Sort by the Week No column (index 1)
      "columnDefs": [
          { "targets": [5], "searchable": false }, // Disable search for the 6th column (index 5)
          {
            "targets": 1, // Week No column
            "render": function(data, type, row) {
              return type === 'sort' ? parseInt(data, 10) : data;
            }
          }
      ],
      "dom": 'Bfrtip', // Only show buttons, filter, and table
      "buttons": [
          {
              extend: 'print',
              text: '<i class="fas fa-print"></i> Print',
              className: 'dt-button'
          },
          {
              extend: 'pdfHtml5',
              text: '<i class="fas fa-file-pdf"></i> PDF',
              className: 'dt-button'
          }
      ],
      responsive: true
  });
});
