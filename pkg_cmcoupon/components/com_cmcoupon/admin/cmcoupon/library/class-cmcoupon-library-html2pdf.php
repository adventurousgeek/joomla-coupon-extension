<?php
/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

if ( ! defined( '_CM_' ) ) {
	exit;
}

if ( ! class_exists( 'TCPDF' ) ) {
	require CMCOUPON_DIR . '/cmcoupon/library/tcpdf/tcpdf_include.php';
}

/**
 * Class
 */
class Cmcoupon_Library_Html2pdf extends TCPDF {

	/**
	 * Margin top.
	 *
	 * @var int
	 */
	var $margin_top = 10;

	/**
	 * Margin bottom.
	 *
	 * @var int
	 */
	var $margin_bottom = 10;

	/**
	 * Margin left.
	 *
	 * @var int
	 */
	var $margin_left = 10;

	/**
	 * Margin right.
	 *
	 * @var int
	 */
	var $margin_right = 10;

	/**
	 * Footer.
	 *
	 * @var int
	 */
	var $y_footer = 0;

	/**
	 * Header html.
	 *
	 * @var string
	 */
	var $html_header = '';

	/**
	 * Body html.
	 *
	 * @var string
	 */
	var $html_body = '';

	/**
	 * Footer html.
	 *
	 * @var string
	 */
	var $html_footer = '';

	/**
	 * Constructor
	 *
	 * @param string $html_header header.
	 * @param string $html_body body.
	 * @param string $html_footer footer.
	 **/
	public function __construct( $html_header, $html_body, $html_footer ) {

		parent::__construct( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, false );

		$this->html_header = $this->convert_utf8( $html_header );
		$this->html_body = $this->convert_utf8( $html_body );
		$this->html_footer = $this->convert_utf8( $html_footer );

		$this->SetCreator( PDF_CREATOR );

		// disable header and footer.
		$this->setPrintHeader( true );
		$this->setPrintFooter( true );

		$this->SetMargins( $this->margin_left, $this->margin_top, $this->margin_right );
		// header and footer margins.
		$this->setHeaderMargin( PDF_MARGIN_HEADER );
		$this->setFooterMargin( PDF_MARGIN_FOOTER );

		// set default monospaced font.
		$this->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );
		// set auto page breaks.
		$this->SetAutoPageBreak( true, PDF_MARGIN_BOTTOM );

		// set image scale factor.
		$this->setImageScale( PDF_IMAGE_SCALE_RATIO );

		$this->SetFont( 'helvetica', '', 10 );

		$this->getBottomMarginHeight();
	}

	/**
	 * Generate pdf
	 *
	 * @param string $output_type the type.
	 * @param string $filename if file then the filename.
	 **/
	public function processpdf( $output_type, $filename = null ) {
		$this->AddPage();
		$this->writeHTML( $this->html_body, true, false, true, false, '' );
		$this->lastPage();

		// Close and output PDF document.
		return $this->Output( $filename, $output_type );
	}

	/**
	 * Set heder
	 **/
	public function Header() {
		$this->writeHTML( $this->html_header, false, false, true, false, 'L' );

		// set top margin based on current header ending.
		$this->SetTopMargin( $this->GetY() + 5 );
	}

	/**
	 * Set footer
	 **/
	public function Footer() {

		// set distance from bottom.
		$this->SetY( $this->y_footer * -1 );
		$this->writeHTML( $this->html_footer, false, false, true, false, 'L' );
	}

	/**
	 * Convert data to utf8 to avoid errors
	 *
	 * @param string $str the content.
	 **/
	public function convert_utf8( $str ) {
		if ( ! class_exists( 'ForceUTF8Encoding' ) ) {
			require CMCOUPON_DIR . '/cmcoupon/library/class-forceutf8encoding.php';
		}
		return ForceUTF8Encoding::to_utf8( str_replace( array( chr( 160 ) . chr( 194 ), chr( 194 ) . chr( 160 ) ), ' ', $str ) );
	}

	/**
	 * Get bottom margin height
	 **/
	public function getBottomMarginHeight() {
		// pdf2 set x margin to pdf1's xmargin, but y margin to zero
		// to make sure that pdf2 has identical settings, you can clone the object (after initializing the main pdf object).
		$pdf2 = clone $this;
		$pdf2->SetTopMargin( 0 );
		$pdf2->AddPage();
		$height_before = $pdf2->GetY();
		$pdf2->writeHTML( $this->html_footer, false, false, true, false, 'L' );
		$height_after = $pdf2->GetY();
		$this->y_footer = ( $height_after - $height_before ) + $this->margin_bottom;
		$pdf2->deletePage( $pdf2->getPage() );
	}

}
