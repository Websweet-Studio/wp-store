jQuery(function ($) {
  function showToast(message, type) {
    var $toast = $('#wp-store-admin-toast');
    if ($toast.length === 0) {
      $toast = $('<div id="wp-store-admin-toast" class="wp-store-toast" />').appendTo('body');
    }
    $toast.removeClass('success error').addClass(type === 'success' ? 'success' : 'error');
    $toast.text(message || '');
    $toast.stop(true, true).fadeIn(150);
    clearTimeout($toast.data('timer'));
    var timer = setTimeout(function () {
      $toast.fadeOut(200);
    }, 2500);
    $toast.data('timer', timer);
  }

  function attachSpinner($select) {
    var $sp = $select.next('.wps-spinner');
    if ($sp.length === 0) {
      $sp = $('<span class="wps-spinner" />').insertAfter($select);
    }
    $sp.show();
    return $sp;
  }

  function updateStatus($select) {
    var orderId = $select.data('order-id');
    var nonce = $select.data('nonce');
    var status = $select.val();
    if (!orderId || !nonce || !status) return;
    var $spinner = attachSpinner($select);
    $select.prop('disabled', true);
    $.post(wpStoreOrderColumns.ajaxUrl, {
      action: 'wp_store_update_order_status',
      order_id: orderId,
      status: status,
      _wpnonce: nonce
    })
      .done(function (res) {
        if (res && res.success) {
          showToast('Status berhasil diperbarui', 'success');
        } else {
          var msg = (res && res.data && res.data.message) ? res.data.message : 'Gagal memperbarui status';
          showToast(msg, 'error');
        }
      })
      .fail(function () {
        showToast('Kesalahan jaringan', 'error');
      })
      .always(function () {
        $select.prop('disabled', false);
        $spinner.hide();
      });
  }

  $(document).on('change', '.wps-order-status-select', function () {
    updateStatus($(this));
  });
});
