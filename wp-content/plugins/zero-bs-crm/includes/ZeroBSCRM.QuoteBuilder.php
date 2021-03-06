<?php 
/*!
 * Jetpack CRM
 * https://jetpackcrm.com
 * V1.20
 *
 * Copyright 2020 Automattic
 *
 * Date: 01/11/16
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

// This fires post CRM init
add_action('zerobscrm_post_init','jpcrm_quote_generate_posted_pdf');

/*
 * Catches any quote PDF requests
 *
 * @returns (conditionally) pdf file
 */
function jpcrm_quote_generate_posted_pdf(){

    // download flag
    if ( isset($_POST['jpcrm_quote_download_pdf'])  ) {

	    // Check nonce
	    if (!wp_verify_nonce( $_POST['jpcrm_quote_pdf_gen_nonce'], 'jpcrm-quote-pdf-gen' )) exit();

	    // check permissions
	    if (!zeroBSCRM_permsQuotes()) exit();

	    global $zbs;

	    // only 3.0+
	    if (!$zbs->isDAL3()) exit();

        #} Check ID
        $quoteID = -1;
        if (isset($_POST['jpcrm_quote_id']) && !empty($_POST['jpcrm_quote_id'])) $quoteID = (int)sanitize_text_field($_POST['jpcrm_quote_id']);
        if ($quoteID <= 0) exit();

        // generate the PDF
        $pdf_file = jpcrm_quote_generate_pdf($quoteID);

        if ($pdf_file !== false){

            // output the PDF
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; filename="quote-'.$quoteID.'.pdf"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($pdf_file));
            header('Accept-Ranges: bytes');
            readfile($pdf_file);

            // delete the PDF file once it's been read (i.e. downloaded)
            unlink($pdf_file); 

        }

        exit();
    }


}


/*
 * Generate PDF file for a quote
 *
 * @param int Quote ID
 * @returns str Path to created pdf
 */
function jpcrm_quote_generate_pdf($quoteID=false){
    
    // got permissions?
    if (!zeroBSCRM_permsQuotes()) return false;

	// Check ID	
	if ($quoteID == false || $quoteID <= 0) return false;

    // let's build a PDF
    global $zbs;

    // PDF Install check: 
    zeroBSCRM_extension_checkinstall_pdfinv();

	// Require DOMPDF    	
	$zbs->libLoad('dompdf');

	// build HTML
	$html = zeroBSCRM_retrieveQuoteTemplate('default');
    $content = zeroBS_getQuoteBuilderContent($quoteID);
    $html = str_replace('###QUOTECONTENT###',$content['content'],$html);

    // build PDF
	$options = new Dompdf\Options();
	$options->set('isRemoteEnabled', TRUE);
	//$options->set('defaultFont', 'Noto Sans');
	$dompdf = new Dompdf\Dompdf($options);
	$contxt = stream_context_create([ 
	    'ssl' => [ 
	        'verify_peer' => FALSE, 
	        'verify_peer_name' => FALSE,
	        'allow_self_signed'=> TRUE
	    ] 
	]);
	$dompdf->setHttpContext($contxt);
	$dompdf->set_paper('A4', 'portrait');

	// pass HTML & render
    $dompdf->loadHtml($html,'UTF-8');
	$dompdf->render();

	// directory & target
	$upload_dir = wp_upload_dir();
	$pdf_dir = $upload_dir['basedir'].'/quotes/';

        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
        }       
    $file_to_save = $pdf_dir.'quote-'.$quoteID.'.pdf';	
    
	// save the .pdf
	file_put_contents($file_to_save, $dompdf->output());		

	return $file_to_save;

}