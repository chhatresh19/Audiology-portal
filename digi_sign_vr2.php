<?php
header('Content-Type: text/plain');
  $signfull = utf8_encode($_POST['dt']); // encoding part
  $data = json_decode($signfull); //json decoding to baseencoded string
  //echo $data;
    $docno= $_REQUEST["document"];
	//$docno = "1000000";
	$fold="";
    $fil ="";
	$signpart = strpos($data,"CommonName"); // Position Finder
    $sign = substr($data,0,$signpart-1); // Remove signature Text 
    $signfin = str_replace("Signature=","",$sign); //Extract encoded value
  
   $pdf_decoded = base64_decode ($signfin);//Decode 
   //$fold ='dssample/updates/';
	// $fil ='SS';
	$fullfil ='http://10.0.240.69/icf/ihms/VR_ORDER/signed/VRNO_'.$docno.'.pdf'; //path
	$pdf = fopen ('VR_ORDER/signed/VRNO_'.$docno.'.pdf','w');
	fwrite ($pdf,$pdf_decoded);
	fclose ($pdf);	
			$fold1 = "VR_ORDER/signed/VRNO_";	
			if(filesize($fold1.$docno.'.pdf')>10000)
			{
							

			///////////////////////////////////////////////////	
			// $docnotrim=substr($docno,0,-4);
			/* $sql = "
			UPDATE tbl_allotments 
			SET 
			tah_io_dt_flag='$date',
			tah_io_dt_flag_otp='$fullfil',
			tah_io_dt_flag_by='$name' 
			WHERE 
			tah_po_allot_no='$docno'";//echo $sql; */
			/* if (mysqli_query($link, $sql)) 
			{

			} 
			else 
			{
				echo "Error: " . $sql . "<br>" . mysqli_error($link);
			} */
			///////////////////////////////////////////////////	

			echo $fullfil;
			//echo "Error: " ;
			}
			ELSE	
			{
				echo "ERROR";
			}


?>