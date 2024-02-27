jQuery(document).ready(function($) {
    // Listen for changes in the payment method selection
    $('form.checkout').on('change', 'input[name="payment_method"]', function() {
        var selectedPaymentMethod = $(this).val();
        
        // Check if the selected payment method is "rethink_payment_v1" (your card payment method)
        if (selectedPaymentMethod === 'rethink_payment_v1') {
            // Show the credit card fields
            $('#credit-card-fields').show();
        } else {
            // Hide the credit card fields
            $('#credit-card-fields').hide();
        }
    });
});
