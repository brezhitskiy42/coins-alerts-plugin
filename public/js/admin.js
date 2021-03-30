(function($) {
  'use strict';

  $(function() {
    
    // Replacing a standard table with DataTables    
    replaceTable();
    
    function replaceTable() {
      
      var tableWidth = $('.manage-alerts__ca').width(),
          table = $('#manage-alerts__ca');
      
      var dtOpts = {
        responsive: true,
        order: [[3, 'desc']]
      };
      
      if (tableWidth < 1100) {
        if (!$.fn.dataTable.isDataTable('#manage-alerts__ca')) {
          table.DataTable(dtOpts);
        } else {
          table.DataTable().destroy();
          table.DataTable(dtOpts);
        }
      } else {
        dtOpts.responsive = false;
        
        if (!$.fn.dataTable.isDataTable('#manage-alerts__ca')) {
          table.DataTable(dtOpts);
        } else {
          table.DataTable().destroy();
          table.DataTable(dtOpts);
        }
      }
    }

  });

})(jQuery);
