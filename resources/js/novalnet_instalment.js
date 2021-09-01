jQuery(document).ready( function() {
    if (jQuery('#nn_instalment_cycle').length > 0) {
        jQuery('#nn_instalment_cycle').on('change',function() {             
            var cycleInformation = '';              
            for (instalmentCycle = 1; instalmentCycle <= jQuery(this).val(); instalmentCycle++) {
                if(instalmentCycle != jQuery(this).val())
                {
                    cycleInformation += '<tr><td>' + instalmentCycle + '</td><td>'+ jQuery(this).find(':selected').attr('data-amount') +'</td></tr>';
                } else {
                    var lastCycleAmount = (jQuery('#nn_order_amount').val() - (jQuery(this).find(':selected').attr('data-cycle-amount') * (jQuery(this).val() - 1)));
                    
                    cycleInformation += '<tr><td>' + instalmentCycle + '</td><td>'+ formatMoney(lastCycleAmount) + ' '+ jQuery('#nn_order_currency').val()+'</td></tr>';
                }
            }                           
            jQuery('#nn_instalment_cycle_information').html(cycleInformation);
        }).change();
    }
            
    jQuery('#novalnet_form').on('submit',function(){
      jQuery('#novalnet_form_btn').attr('disabled',true);      
    });
  
});
