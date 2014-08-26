jQuery( document ).ready( function( $ ) {

  $('#expiratordate').datepicker({

    dateFormat: 'yy-mm-dd'

  });

  $('#expiratortime').timepicker();

  $('#expirator-use').change( function() {

    if( $(this).is(':checked') ) {
       
      $('#expirator-settings').slideDown();

    } else {

      $('#expirator-settings').slideUp();

    }

  });

  $('#expirator-status').change( function() {
    if( $(this).is(':checked') ) {
       
       $('#expirator-status-box').slideDown();

    } else {

       $('#expirator-status-box').slideUp();

    }
  });

  $('#expirator-category').change( function() {
    if( $(this).is(':checked') ) {
       
       $('#expirator-category-box').slideDown();

    } else {

       $('#expirator-category-box').slideUp();

    }
  });

  $('#expirator-postmeta').change( function() {
    if( $(this).is(':checked') ) {
       
       $('#expirator-postmeta-box').slideDown();

    } else {

       $('#expirator-postmeta-box').slideUp();

    }
  });

});