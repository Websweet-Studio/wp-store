jQuery(document).ready(function($) {
    // --- Product Type Toggle ---
    const $typeSelect = $('#_store_product_type');
    const $weightRow = $('.cmb2-id--store-weight-kg');
    const $fileRow = $('.cmb2-id--store-digital-file');

    function toggleFields() {
        const type = $typeSelect.val();
        if (type === 'digital') {
            $weightRow.hide();
            $fileRow.show();
        } else {
            $weightRow.show();
            $fileRow.hide();
        }
    }

    if ($typeSelect.length) {
        $typeSelect.on('change', toggleFields);
        toggleFields(); // Initial run
    }

    // --- Tabs Implementation ---
    const $metabox = $('#wp_store_product_detail_metabox');
    if (!$metabox.length) return;

    // Create tabs container
    const $tabsContainer = $('<ul class="cmb-tabs"></ul>');
    const $fieldList = $metabox.find('.cmb2-wrap > .cmb-field-list');

    // Make sure we have a field list to work with
    if (!$fieldList.length) return;
    
    // Add tabs container before field list
    $fieldList.before($tabsContainer);

    // Find all title fields (which act as tab headers)
    const $titles = $fieldList.find('.cmb-type-title');
    
    // Process each title to create a tab
    $titles.each(function(index) {
        const $titleRow = $(this);
        const tabId = $titleRow.attr('id') || 'tab-' + index;
        const tabName = $titleRow.find('.cmb2-metabox-title').text();
        
        // Create tab button
        const $tab = $('<li class="cmb-tab" data-tab="' + tabId + '">' + tabName + '</li>');
        $tabsContainer.append($tab);

        // Hide the title row itself as it's now a tab
        $titleRow.hide();

        // Group fields following this title until the next title
        const $nextFields = $titleRow.nextUntil('.cmb-type-title');
        
        // Wrap them in a tab content div (we'll just use class for toggling)
        $nextFields.addClass('tab-content tab-content-' + tabId);
        
        // If it's the first tab, activate it
        if (index === 0) {
            $tab.addClass('active');
            $nextFields.show();
        } else {
            $nextFields.hide();
        }
    });

    // Handle tab click
    $tabsContainer.on('click', '.cmb-tab', function() {
        const $this = $(this);
        const tabId = $this.data('tab');

        // Toggle active tab state
        $tabsContainer.find('.cmb-tab').removeClass('active');
        $this.addClass('active');

        // Toggle field visibility
        $fieldList.find('.cmb-row').not('.cmb-type-title').hide(); // Hide all fields first
        $fieldList.find('.tab-content-' + tabId).show(); // Show fields for this tab
        
        // Re-apply product type logic if we switched to a tab containing relevant fields
        if (tabId === 'tab_general' || tabId === 'tab_shipping') {
            toggleFields();
        }
    });
});
