jQuery(document).ready(function($) {
    // Add new emirate row
    $('#add-emirate').on('click', function(e) {
        e.preventDefault();
        
        // Clone template row (use first row as template if empty)
        let newRow = $('.wc-uae-emirates-table tbody tr:first').clone();
        
        // Clear values and generate new index
        newRow.find('input').val('');
        newRow.find('td:first input').attr('name', 'emirates[new_'+Date.now()+'][code]');
        newRow.find('td:nth-child(2) input').attr('name', 'emirates[new_'+Date.now()+'][name]');
        
        // Add remove handler
        newRow.find('.remove-row').on('click', removeRow);
        
        $('.wc-uae-emirates-table tbody').append(newRow);
    });

    // Remove row handler
    $(document).on('click', '.remove-row', removeRow);

    function removeRow(e) {
        e.preventDefault();
        if ($('.wc-uae-emirates-table tbody tr').length > 1) {
            $(this).closest('tr').remove();
        }
    }

    // Initialize existing remove buttons
    $('.remove-row').each(function() {
        $(this).on('click', removeRow);
    });
});
