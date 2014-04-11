<?php

class XDReport {

	private $_chart_pool = null;
	private $_pdf_object = null;

	// ----------------------------------------
		
	public function __construct ($user) {
		
		$this->_chart_pool = new XDChartPool($user);
		$this->_pdf_object = new FPDF();
		$this->_pdf_object->SetLeftMargin(13);
		$this->_pdf_object->SetTopMargin(2);
	
	}//__construct
	
	// ----------------------------------------
		
	public function generate($dest_file = NULL) 
	{
	
		$this->_pdf_object->AddPage();

		$this->_pdf_object->SetX(9);
		$this->_pdf_object->SetFont('Arial','B',16);
		$this->_pdf_object->Cell(40,10,'XDMoD Report');
		
		$this->_pdf_object->SetX(160);
		$this->_pdf_object->SetFont('Arial','I',8);
		$this->_pdf_object->Cell(40,10,date('l, F j, Y -- G:i'),0,0,'R');
	
		$this->_pdf_object->SetY($this->_pdf_object->GetY() + 10);
		
		$this->_pdf_object->Line(10, $this->_pdf_object->GetY(), 200, $this->_pdf_object->GetY());
		
		$report_data =  $this->_chart_pool->fetchReportData();

		$firstPage = true;
		
		foreach($report_data as $entry){
		
			if (!$firstPage) $this->_pdf_object->AddPage();
			
			$firstPage = false;
			
			$this->_pdf_object->SetFont('Arial','',14);
			$this->_pdf_object->Cell(30,10,$entry['title'],0,0,'L');
    			$this->_pdf_object->Ln(10);
			
			$this->_pdf_object->Image($entry['image_url'],10,28,$entry['image_width'] / 5,$entry['image_height'] / 5,'png');
			$this->_pdf_object->Ln(10);		
			$this->_pdf_object->SetY($this->_pdf_object->GetY() + ($entry['image_height'] / 5) + 10);
			
			$this->_pdf_object->SetFont('Arial','',9);
			//$this->_pdf_object->Cell(30,10,$entry['comments'],0,0,'L');
			$this->_pdf_object->Write(5, $entry['comments']);
			
		}
		
		if ($dest_file == NULL){
			$this->_pdf_object->Output();                   // Display PDF inline
		}
		else{
			$this->_pdf_object->Output($dest_file, 'F');    // Output PDF to a file
		}
		
	}//generate

}//XDReport

?>
