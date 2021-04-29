/*!
 * Jetpack CRM
 * https://jetpackcrm.com
 * V1.2+
 *
 * Copyright 2020 Automattic
 *
 * Date: 22/12/2016
 */

// declare
var quoteTemplateBlocker = false; 

// init
jQuery(document).ready(function(){

	// "use quote builder"
	jQuery('#zbsQuoteBuilderStep2').click(function(){

		// SHOULD show some LOADING here...

		// get content + inject (via ajax)
		zbscrm_getTemplatedQuote(function(){

			// callback, after inject

			// show editor + step 3
			jQuery('#wpzbscsub_quotecontent').show(); // <DAL3
			jQuery('#zerobs-quote-content-edit').show(); // DAL3
			jQuery('#wpzbscsub_quoteactions').show(); // <DAL3
			jQuery('#zerobs-quote-nextstep').show(); // DAL3

			// hide button etc.
			jQuery('#zbs-quote-builder-step-1').slideUp();

			// fix height of content box, after the fact
			setTimeout(function(){

				jQuery('#zbs_quote_content_ifr').css('height','580px');

				// and scroll down to it - fancy!

				// <DAL3
				if (jQuery("#wpzbscsub_quotecontent").length > 0){
				    jQuery('html, body').animate({
				        scrollTop: jQuery("#wpzbscsub_quotecontent").offset().top
				    }, 2000);
				}

				// DAL3
				if (jQuery("#zerobs-quote-content-edit").length > 0){
				    jQuery('html, body').animate({
				        scrollTop: jQuery("#zerobs-quote-content-edit").offset().top
				    }, 2000);
				}


			},0);


		});


	});


	// save quote button - proxy
	jQuery('#zbsQuoteBuilderStep3').click(function(){

		// click save
		jQuery('#publish').click();

	});

	// on change of this, say good bad
	jQuery('#zbsQuoteBuilderEmailTo').keyup(function(){


		var email = jQuery('#zbsQuoteBuilderEmailTo').val();
		if (typeof email == "undefined" || email == '' || !zbscrm_JS_validateEmail(email)){

			// email issue
			jQuery('#zbsQuoteBuilderEmailTo').css('border','2px solid orange');
			jQuery('#zbsQuoteBuilderEmailToErr').show();


		} else {

			// return to normal 
			jQuery('#zbsQuoteBuilderEmailTo').css('border','1px solid #ddd');
			jQuery('#zbsQuoteBuilderEmailToErr').hide();

		}


	});


	// send quote via email
	jQuery('#zbsQuoteBuilderSendNotification').off('click').click(function(ind,ele){

		jpcrm_quotes_send_email_modal();

	});

	// if this is set, show the templated dialogs
	if (typeof window.zbscrm_templated != "undefined"){

		// and hide this
		jQuery('#zbs-quote-builder-step-1').hide();

		// show editor + step 3
		jQuery('#wpzbscsub_quotecontent').show(); // <DAL3
		jQuery('#zerobs-quote-content-edit').show(); // DAL3
		jQuery('#wpzbscsub_quoteactions').show(); // <DAL3
		jQuery('#zerobs-quote-nextstep').show(); // DAL3


	}

	// step 3 - copy url
	if (jQuery('#zbsQuoteBuilderURL').length) document.getElementById("zbsQuoteBuilderURL").onclick = function() {
	    this.select();
	    document.execCommand('copy');
	}




});



function zbscrm_appendTextToEditor(text) {
    if (typeof window.parent.send_to_editor == "function") window.parent.send_to_editor(text);
}


function zbscrm_getTemplatedQuote(cb,errcb){

	if (!window.quoteTemplateBlocker){

		// req:
		var custID =  ''; if (jQuery('#zbscq_customer').length > 0) custID = jQuery('#zbscq_customer').val();
		var quoteTemplateID = 0; if (jQuery('#zbs_quote_template_id').length > 0) quoteTemplateID = parseInt(jQuery('#zbs_quote_template_id').val());

		// retrieve deets - <DAL3
		var zbs_quote_title = ''; if (jQuery('#name').length > 0) zbs_quote_title = jQuery('#name').val();
		var zbs_quote_val = ''; if (jQuery('#val').length > 0) zbs_quote_val = jQuery('#val').val();
		var zbs_quote_dt = ''; if (jQuery('#date').length > 0) zbs_quote_dt = jQuery('#date').val();

		//console.log('here',[custID,quoteTemplateID]);

		// DAL3 + we do a more full pass of data
		var fields = {};
        if (zbscrm_JS_DAL() > 2){
			// this'll work excluding checkboxes - https://stackoverflow.com/questions/11338774/serialize-form-data-to-json			  		    
		    jQuery.map(jQuery('#zbs-edit-form').serializeArray(), function(n, i){
		        fields[n['name']] = n['value'];
		    });
		}

		if (!empty(custID)){ 

			// has quote template (not blank)
			if(!empty(quoteTemplateID)){

				// proceed.
			    quoteTemplateAJAX = jQuery.ajax({
			        url: ajaxurl,
			        type: "POST",
			        data: {
				        action: "zbs_get_quote_template",
				        quote_fields:fields, // DAL3 only cares about this

				        // DAL2:
				        cust_id: custID,
				        quote_type: quoteTemplateID,
				        quote_title: zbs_quote_title,
				        quote_val: zbs_quote_val,
				        quote_dt: zbs_quote_dt,

				        // Sec:
				        security: jQuery( '#quo-ajax-nonce' ).val()
				    },
			        dataType: "json"
			    });
			    quoteTemplateAJAX.done(function(e) {

			    	// msg out
			        swal({title: "Success!",text: "Quote Template Populated",type: "success",confirmButtonText: "OK"});

			        setTimeout(function(){
			        // inject
			        zbscrm_appendTextToEditor(e.html);
			    },500);

			        // disable blocker
			        window.quoteTemplateBlocker = false;

			        // callback?
			        if (typeof cb == "function") cb();


			    }), quoteTemplateAJAX.fail(function(e) {

			    	// err
			        swal({title: "Error!",text: "Failed retrieving template! If this error persists please contact Jetpack CRM support.",type: "error",confirmButtonText: "OK"});

			        // disable blocker
			        window.quoteTemplateBlocker = false;

			        // callback?
			        if (typeof errcb == "function") errcb();

			    });

			} else {

				// blank template

			        // disable blocker
			        window.quoteTemplateBlocker = false;

			        // callback?
			        if (typeof cb == "function") cb();
				
			}

		} else {

			// err - no cust / quote template id
			if (empty(custID)) swal({title: "Error!",text: "Please Choose a Customer",type: "error",confirmButtonText: "OK"});
            if (empty(quoteTemplateID)) swal({title: "Error!",text: "Please Choose a Template",type: "error",confirmButtonText: "OK"});

	        // disable blocker
	        window.quoteTemplateBlocker = false;

			return false; 

		}

	} // blocker

}



// ========================================================================
// ======= Helpers
// ========================================================================

/*
 * Show the 'send quote via email' modal
 */
function jpcrm_quotes_send_email_modal(){

	// retrieve vars
	var quotenotificationproceed = true;
	var recipientEmail = jQuery('#zbsQuoteBuilderEmailTo').val();

	// verify email
	if (typeof recipientEmail != "undefined" && recipientEmail != '' && zbscrm_JS_validateEmail(recipientEmail)){

		// build options html
		var optsHTML = '<div id="jpcrm_quote_email_modal_opts">';

			// to
			optsHTML += '<div class="jpcrm-send-email-modal-field">';
				optsHTML += '<label for="jpcrm_quote_email_modal_toemail">' + jpcrm_quotes_lang('toemail') + '</label>';
				optsHTML += '<input type="email" id="jpcrm_quote_email_modal_toemail" value="' + recipientEmail + '" placeholder="' + jpcrm_quotes_lang('toemailplaceholder') + '" />';
			optsHTML += '</div>';

			// attach associated pdfs? (if any)
			if (jQuery('.zbsFileLine').length > 0){
				optsHTML += '<div class="jpcrm-send-email-modal-field">';
				var checkedStr = ''; if (jQuery('#zbsc_sendattachments').is(':checked')) checkedStr = 'checked="checked" ';
					optsHTML += '<input type="checkbox" id="jpcrm_quote_email_modal_attachassoc" value="1" ' + checkedStr + '/>';
					optsHTML += '<label for="jpcrm_quote_email_modal_attachassoc">' + jpcrm_quotes_lang('attachassoc') + '</label>';
				optsHTML += '</div>';
			}

			// attach inv as pdf?
			var checkedStr = 'checked="checked" '; // default yes
			optsHTML += '<div class="jpcrm-send-email-modal-field">';
				optsHTML += '<input type="checkbox" id="jpcrm_quote_email_modal_attachaspdf" value="1" ' + checkedStr + '/>';
				optsHTML += '<label for="jpcrm_quote_email_modal_attachaspdf">' + jpcrm_quotes_lang('attachpdf') + '</label>';
			optsHTML += '</div>';

		optsHTML += '</div>';

	    swal({
	      title: jpcrm_quotes_lang('send_email'),
	      html: '<div class="ui segment">' + jpcrm_quotes_lang('sendthisemail') + optsHTML + '</div>',
	      type: 'question',
	      showCancelButton: true,
	      confirmButtonColor: '#3085d6',
	      cancelButtonColor: '#d33',
	      confirmButtonText: jpcrm_quotes_lang('sendthemail'),
	      //allowOutsideClick: false
	    }).then(function (result) {

	        // this check required from swal2 6.0+
	        if (result.value){

				var recipientEmail = jQuery('#jpcrm_quote_email_modal_toemail').val();
				var quoteID = parseInt(jQuery('#zbsQuoteBuilderEmailTo').attr('data-quoteid'));
				if (typeof recipientEmail != "undefined" && recipientEmail != '' && zbscrm_JS_validateEmail(recipientEmail) && quoteID > 0){

		            // get settings
		            var attachassoc = -1; if (jQuery('#jpcrm_quote_email_modal_attachassoc').length > 0 && jQuery('#jpcrm_quote_email_modal_attachassoc').is(':checked')) attachassoc = 1;
		            var attachpdf = -1; if (jQuery('#jpcrm_quote_email_modal_attachaspdf').length > 0 && jQuery('#jpcrm_quote_email_modal_attachaspdf').is(':checked')) attachpdf = 1;
		            var params = {
		            	'id':quoteID,
		            	'cid':jQuery('#zbscq_customer').val(),
		            	'email':recipientEmail,
		            	'attachassoc':attachassoc,
		            	'attachpdf':attachpdf
		            };

	            	// send email
					swal.fire({
						title: jpcrm_quotes_lang('sendingemail'),
						html: '<div style="clear:both">&nbsp;</div><div class="ui active loader" style="margin-top:2em;padding-bottom:2em"></div><div style="clear:both">&nbsp;</div>',
						showConfirmButton: false,
						showCancelButton: false,
						allowOutsideClick: false
					});
	            	jpcrm_quotes_send_email(params);


				} else {

					// not legit email!
					swal.fire(jpcrm_quotes_lang('sendneedsassignment'));

				}

	        }

	    });

	} else {

		// not legit email!
		swal.fire(jpcrm_quotes_lang('sendneedsassignment'));
	}

}

/*
 * Send the quote via email (AJAX)
 */
function jpcrm_quotes_send_email(params){

	if (!window.jpcrmQuoteBlocker){

		window.jpcrmQuoteBlocker = true;

		// check params?
		if (typeof params.id != "undefined" && params.id > 0 && typeof params.email != "undefined" && zbscrm_JS_validateEmail(params.email)){

			// postbag!
			var data = {
				'action': 'jpcrm_quotes_send_quote',
				'sec': window.zbsEditSettings.nonce,
				// data
				'em': params.email,
				'qid': params.id,
				'cid': params.cid,			
            	'attachassoc':params.attachassoc,
            	'attachpdf':params.attachpdf
			};

            jQuery.ajax({
					url: ajaxurl,
					type: "POST",
					data: data,
					dataType: "json",
					success: function(response) { 

						// done			
						swal(
						  jpcrm_quotes_lang('senttitle'),
						  jpcrm_quotes_lang('sent'),
						  'info'
						);

						// blocker
						window.jpcrmQuoteBlocker = false;

					},
					error: function(response){ 

						// err			
						swal(
						  jpcrm_quotes_lang('senderrortitle') + ' #19v3',
						  jpcrm_quotes_lang('senderror'),
						  'error'
						);

						// blocker
						window.jpcrmQuoteBlocker = false;

					}
            });

	    } // / if deets check out 

    } // / if not blocked
}


/*
 * Language output
 * 	Passes language from window.zbsListViewLangLabels (js set in listview php)
 */
function jpcrm_quotes_lang(key,fallback,subkey){

    if (typeof fallback == 'undefined') var fallback = '';

    if (typeof window.zbsEditViewLangLabels[key] != "undefined") {

    	if (typeof subkey == "undefined")

    		return window.zbsEditViewLangLabels[key];

    	else if (typeof window.zbsEditViewLangLabels[key][subkey] != "undefined") 

    		return window.zbsEditViewLangLabels[key][subkey];

    }

    return fallback;
}
// ========================================================================
// ======= /Helpers
// ======================================================================== 