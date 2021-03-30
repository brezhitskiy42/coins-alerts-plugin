(function($) {
  'use strict';

  $(function() {
    
    // Replacing a standard select with Select2
    $('select[name="coins"]').select2({
      data: filterCoins(caCustom.coins),
      width: '100%'
    }).val(filterCoins(caCustom.coins)[0].id).trigger('change');
    
    $('select[name="criteria"]').select2({ minimumResultsForSearch: -1, width: '100%' });
    
    // Closing an information block
    $('button.close-btn__ca').on('click', function(e) {
      e.preventDefault();
      
      $(this).parent().hide();
    });
    
    // Form handler for adding alert
    $('form#add-alert__ca').on('submit', function(e) {
      e.preventDefault();
      
      $('.info__ca').hide();
      $('.spinner__ca').show();
      
      var coin_id = $('select[name="coins"]').val();
      var criteria = $('select[name="criteria"]').val();
      var amount = $('input[name="amount"]').val();
      var email = $('input[name="email"]').val();
      
      if (!coin_id || !criteria || !checkAmount(amount) || !checkEmail(email)) showInfo('error');
      
      var data = {
        action: 'add_alert',
        nonce: caCustom.add_alert_nonce,
        coin_id: coin_id,
        criteria: criteria,
        amount: amount,
        email: email
      };
      
      $.post(caCustom.ajax_url, data, function(resp) {
        if (resp == 'error') { showInfo('error'); }
        else if (resp == 'internal_error') { showInfo('internal_error'); }
        else if (resp == 'success') { 
          showInfo('success');
          $('form#add-alert__ca')[0].reset();
          $('select[name="coins"]').val(filterCoins(caCustom.coins)[0].id).trigger('change');
          $('select[name="criteria"]').val('rises').trigger('change');
          $('.addon__ca').text(caCustom.currency);
        }
      });
    });
    
    // Deactivating alert
    $('body').on('click', 'button.deactivate__ca', function(e) {
      e.preventDefault();
      
      var secretKey = $(this).attr('data-secret-key');
      var alertId = $(this).attr('data-alert-id');      
      
      if (!alertId || !secretKey) return;
      
      var data = {
        action: 'deactivate_alert',
        nonce: caCustom.deactivate_alert_nonce,
        secret_key: secretKey,
        alert_id: alertId
      };
      
      $.post(caCustom.ajax_url, data, function(resp) {
        if (resp == 'error') return;
        else window.location.reload(true);
      });
    });
    
    // Removing alert
    $('body').on('click', 'button.remove__ca', function(e) {
      e.preventDefault();
      
      var secretKey = $(this).attr('data-secret-key');
      var alertId = $(this).attr('data-alert-id');
      
      if (!alertId || !secretKey) return;
      
      var data = {
        action: 'remove_alert',
        nonce: caCustom.remove_alert_nonce,
        secret_key: secretKey,
        alert_id: alertId
      };
      
      $.post(caCustom.ajax_url, data, function(resp) {
        if (resp == 'error') return;
        else window.location.reload(true);
      });
    });
    
    // Processing the list of currencies for Select2
    function filterCoins(rawCoins) {
      var coins = [];
      for (var id in rawCoins) {
        coins.push({
          id: +id,
          text: rawCoins[id].name_tr + ' [' + rawCoins[id].symbol + ']' 
        });
      }
      return coins;
    }
    
    // Checks for received data
    function checkAmount(amount) {
      return $.isNumeric(amount) && amount > 0;
    }
    
    function checkEmail(email) {
      var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
      return re.test(email.toLowerCase());
    }
    
    // Information block output
    function showInfo(infoType) {
      var ERROR_TEXT = caCustom.error_text;
      var INTERNAL_ERROR_TEXT = caCustom.internal_error_text;
      var SUCCESS_TEXT = caCustom.success_text;
      
      $('.spinner__ca').hide();
      
      if (infoType == 'error') { 
        $('.info-text__ca').text(ERROR_TEXT);
        $('.info__ca').removeClass('success__ca').addClass('error__ca').show();
      } else if (infoType == 'internal_error') {
        $('.info-text__ca').text(INTERNAL_ERROR_TEXT);
        $('.info__ca').removeClass('success__ca').addClass('error__ca').show(); 
      } else if (infoType == 'success') {
        $('.info-text__ca').text(SUCCESS_TEXT);
        $('.info__ca').removeClass('error__ca').addClass('success__ca').show();   
      }
    }
    
    // The select criteria handler
    $('select[name="criteria"]').on('change', function(e) {
      var criteria = $(this).val();
      
      if (criteria == 'incr_perc' || criteria == 'decr_perc') $('.addon__ca').text('%');
      else if (criteria == 'supply') $('.addon__ca').text(caCustom.coins[$('select[name="coins"]').val()].symbol);
      else $('.addon__ca').text(caCustom.currency);
      
      setCurrentValue(caCustom.currency, caCustom.coins[$('select[name="coins"]').val()].symbol, criteria);
    });
    
    $('select[name="coins"]').on('change', function() { $('select[name="criteria"]').trigger('change'); });
    
    // Replacing a standard table with DataTables    
    replaceTable();
    
    function replaceTable() {
      
      var tableWidth = $('.my-alerts__ca').width(),
          table = $('#my-alerts__ca');
      
      var dtOpts = {
        responsive: true,
        order: [[2, 'desc']],
        searching: false,
        lengthChange: false,
        columnDefs: [{ width: 90, targets: 7 }]
      };
      
      if (tableWidth < 1100) {
        if (!$.fn.dataTable.isDataTable('#my-alerts__ca')) {
          table.DataTable(dtOpts);
        } else {
          table.DataTable().destroy();
          table.DataTable(dtOpts);
        }
      } else {
        dtOpts.responsive = false;
        
        if (!$.fn.dataTable.isDataTable('#my-alerts__ca')) {
          table.DataTable(dtOpts);
        } else {
          table.DataTable().destroy();
          table.DataTable(dtOpts);
        }
      }
    }
    
    // Setting current value
    setCurrentValue(caCustom.currency, 'BTC', 'rises');
    
    function setCurrentValue(currency, coin, criteria) {
      
      var $currentValue = $('.current-value__ca span');
      
      $currentValue.text(caCustom.loading);
      
      if (!currency || !coin || !criteria) {
        $currentValue.text(caCustom.loading);
        return;
      }
      
      var data = {
        action: 'get_current_value',
        currency: currency,
        coin: coin,
        criteria: criteria
      };
      
      $.post(caCustom.ajax_url, data, function(resp) {
        if (resp == 'error') {
          $currentValue.text(caCustom.loading);
          return;
        }
        $currentValue.text(resp);
      });
      
    }

  });

})(jQuery);
