<?php
class Att_services_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        include(APPPATH . "PhpMailer/class.phpmailer.php");
        include(APPPATH . "s3.php");
    }
    public function getCountries()
    {
        $query = $this->db->query("SELECT `Id`, `Name`,countryCode FROM `CountryMaster` order by Name");
		
        echo json_encode($query->result());
    }

    public function gitsession()
    {
        
       //exec("git -C /var/www/html/git/gitsession pull");
       $your_command="git -C /var/www/html/git/gitsession pull";
      // $your_command="whoami";
       exec($your_command.' 2>&1', $output, $return_var);
      //  var_dump(); 

      $var = print_r($output, true);

        $query = $this->db->query("INSERT into gitsession(Name) VALUES('$var')");
       
       // echo json_encode($query->result());
    }
    
    public function getOrganization($id)
        {
        $query = $this->db->query("SELECT o.id as orgid, o.Name as name , z.name as zone FROM Organization as  o, ZoneMaster as z WHERE o.country=z.CountryId and o.id=?", array(
            $id
        ));
        $data  = array();
        if ($query->num_rows()) {
            foreach ($query->result() as $row) {
                $data['response'] = 1;
                $data['orgid']    = $row->orgid;
                $data['name']     = $row->name;
                $data['zone']     = $row->zone;
            }
        } else {
            $data['response'] = 0;
        }
        echo json_encode($data);
    }
    
    public function checkLogin()
    {
        $data     = array();
        $active   = 1;
        $userName = '';
        $password = '';
        $org_perm = "1,2,3";
		$date = date('Y-m-d');
        
        $device = isset($_REQUEST['device']) ? $_REQUEST['device'] : '';
        $deviceid = isset($_REQUEST['deviceid']) ? $_REQUEST['deviceid'] : '';
        $qr     = isset($_REQUEST['qr']) ? $_REQUEST['qr'] : '';
        if ($qr == 'true') {
            $userName = encode5t(isset($_REQUEST['userName']) ? trim(strtolower($_REQUEST['userName'])) : '');
            $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
            
        }else{
            $userName = encode5t(isset($_REQUEST['userName']) ? trim(strtolower($_REQUEST['userName'])) : '');
            $password = encode5t(isset($_REQUEST['password']) ? $_REQUEST['password'] : '');
        } 

        $mail_varified='';
        $userName1 = isset($_REQUEST['userName']) ? trim(strtolower($_REQUEST['userName'])) : '';
        $password1 = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
           // echo $userName1;die;


       
        $sql= "SELECT mail_varified FROM OrganizationTemp where (Email= '$userName1' or PhoneNumber= '$userName1')and password='$password1'" ;
        $query=$this->db->query($sql);
        if ($row = $query->row()) {
        //echo"found";
                     $mail_varified = $row->mail_varified;
                      if($mail_varified == '0'){
           $data['mailstatus'] = '1';
           echo json_encode($data);
           return;
        }
   }

   $archive = '';
   $query = $this->db->query("SELECT * FROM `UserMaster` , EmployeeMaster WHERE (Username=? or username_mobile=?)and Password=? and EmployeeMaster.id=UserMaster.`EmployeeId` and EmployeeMaster.OrganizationId not in(502,1074)", array(
    $userName,
    $userName,
    $password
));   
// $query=$this->db->query($sql);
   if ($row = $query->row()) {
      $archive = $row->archive;
      $is_Del = $row->Is_Delete;
      if($archive == '0' || $is_Del =="1" || $is_Del =="2"){

       $data['response'] = '2';
       echo json_encode($data);
       return;
   }
}

    //echo "SELECT * FROM `UserMaster` , EmployeeMaster WHERE (Username='$userName' or username_mobile='$userName')and Password='$password' and EmployeeMaster.id=UserMaster.`EmployeeId` and UserMaster.archive=1 and EmployeeMaster.Is_Delete=0 and EmployeeMaster.OrganizationId not in(502,1074)";
		
        $query = $this->db->query("SELECT * FROM `UserMaster` , EmployeeMaster WHERE (Username=? or username_mobile=?)and Password=? and EmployeeMaster.id=UserMaster.`EmployeeId` and UserMaster.archive=1 and EmployeeMaster.archive=1 and EmployeeMaster.Is_Delete=0 and EmployeeMaster.OrganizationId not in(502,1074)", array(
            $userName,
            $userName,
            $password
        )); // custom app- (502 for RAKP) 1074-Erawan
        //echo($query->num_rows());
        
       
        if ($query->num_rows()) {
            foreach ($query->result() as $row) {
                $data['response'] = 1;
                $data['fname']    = ucfirst($row->FirstName);
                $data['lname']    = ucfirst($row->LastName);
                $data['empid']    = $row->EmployeeId;
                $data['usrpwd']    = decode5t($row->Password);
               // $data['archive']   = $row->archive;
                $data['status']   = $row->VisibleSts;
                $data['orgid']    = $row->OrganizationId;
                $data['sstatus']  = $row->appSuperviserSts;
                $data['org_perm'] = $org_perm;
				$data['imgstatus'] = 1;
				$data['DeviceId'] = $row->DeviceId;
				$data['DeviceIdMobile'] = $deviceid;
				/////////////////////// device restriction for ubisales by NKA

				/*
				if($data['DeviceId']!='0'){
					if($data['DeviceId']!=$deviceid){
					  $data['response'] = '5';
                      echo json_encode($data);
                      return;
					}
				}
				*/
				//////////////////////// device restriction for ubisales by NKA
				$queryImg = $this->db->query("SELECT `AttnImageStatus` FROM `admin_login` WHERE `OrganizationId` = ? limit 1",array($row->OrganizationId));
				if($Imgrow =  $queryImg ->row())
				{
					$data['imgstatus'] = $Imgrow->AttnImageStatus;
				}
                $result1          = $this->db->query("SELECT Name, Email, Country FROM `Organization` WHERE id=?", array(
                    $data['orgid']
                ));
                if ($row1 = $result1->row())
                    if (strlen($row1->Name) > 16)
                        $data['org_name'] = mb_substr($row1->Name, 0, 16,'utf-8') . '..';
                    else
                        $data['org_name'] = $row1->Name;
                
                $data['orgmail'] = $row1->Email;
                $data['orgcountry'] = $row1->Country;
                
                $result2 = $this->db->query("SELECT status, end_date FROM `licence_ubiattendance` WHERE OrganizationId =? order by id desc limit 1",array($data['orgid']));
					if($row2= $result2->row()){	
							$data['trialstatus']= $row2->status;
							if(date('Y-m-d',strtotime($row2->end_date)) < $date){
								$data['trialstatus']= "2";
							}
							$data['buysts']= $row2->status;
					}
					
					$desgname = getDesignation($row->Designation);
                if (strlen($desgname) > 16)
                        $data['desination'] = substr(getDesignation($row->Designation), 0, 16) . '...';
                    else
                        $data['desination'] = getDesignation($row->Designation);
				
				$data['desinationId'] = $row->Designation;
                
                if ($row->ImageName != "") {
                    $dir             = "public/uploads/" . $row->OrganizationId . "/" . $row->ImageName;
                    $data['profile'] = "https://ubitech.ubihrm.com/" . $dir;
                 //   $data['profile'] =  IMGURL3 . $dir;
                } else {
                    $data['profile'] = "http://ubiattendance.ubihrm.com/assets/img/avatar.png";
                }
            }
            $result1 = $this->db->query("SELECT * FROM `PlayStore` Where 1");
            if ($row1 = $result1->row()) {
                if ($device == 'Android')
                    $data['store'] = $row1->googlepath;
                else if ($device == 'iOS')
                    $data['store'] = $row1->applepath;
                else
                    $data['store'] = 'https://ubiattendance.ubihrm.com';
            }
        } else {
            $data['response'] = 0;
        }
		 $this->db->close();
        echo json_encode($data);
    }
	/////// Date - 26/11/2018 via abhinav@ubitechsolutions.com
    //// function for resending organization email verification from app
	//////
	public function resend_verification_mail(){
		//////////////////-------------activate mail body-strt
					$orgid = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : "";
					$email = getOrgEmail($orgid);
					$emp_id = getEmpIDbyEmail(encode5t($email),$orgid);
					$contact_person_name = getAdminName($orgid);
					$result = array();
                    $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<div class=Section1>

					<div align=center>

					<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="550"
					 style="550px;border-collapse:collapse" align="center">
					 <tr style="height:328.85pt">
					  <td width=917 valign=top style="width:687.75px;padding:0in 0in 0in 0in;
					  height:328.85px">
					  <div align=center>
					  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="100%" style="width:100.0%;border-collapse:collapse">
					   <tr>
						<td valign=top style="background:#52BAD5;padding:0in 16.1pt 0in 16.1pt">
						<div align=center>
						<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						 width="100%" style="width:100.0%;border-collapse:collapse">
						 <tr>
						  <td valign=top style="padding:21.5pt 0in 21.5pt 0in">
						  <p class=MsoNormal align=center style="margin-bottom:0in;margin-bottom:
						  .0001pt;text-align:center;line-height:normal"><span style="font-size:
						  12.0pt;font-family:Arial,sans-serif"><img width=200 
						  id="Picture 1" src="http://ubitechsolutions.com/ubitechsolutions/Mailers/ubiAttendance/ubiAttendance/logo.png" alt="ubitech solutions"></span></p>
						  </td>
						 </tr>
						</table>
						</div>
						</td>
					   </tr>
					   <tr>
						<td valign=top style="background:#52BAD5;padding:0in 16.1pt 0in 16.1pt">
						<div align=center>
						<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						 width="100%" style="width:100.0%;border-collapse:collapse">
						 <tr>
						  <td valign=top style="padding:0in 0in 0in 0in">
						  <div align=center>
						  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						   width="100%" style="width:100.0%;border-collapse:collapse">
						   <tr>
							<td valign=top style="padding:0in 0in 0in 0in">
							<div align=center>
							<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
							 width="100%" style="width:100.0%;background:white">
							 <tr>
							  <td width="550" valign=top style="width:550px;padding:21.5pt 0in 0in 0in">
							  <p class=MsoNormal align=center style="margin-bottom:0in;margin-bottom:
							  .0001pt;text-align:center;line-height:normal"><span style="font-size:
							  12.0pt;font-family:Arial,sans-serif">&nbsp;</span></p>
							  </td>
							 </tr>
							</table>
							</div>
							</td>
						   </tr>
						  </table>
						  </div>
						  </td>
						 </tr>
						</table>
						</div>
						</td>
					   </tr>
					   <tr>
						<td valign=top style="padding:0in 0in 0in 0in">
						<div align=center>
						<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						 width="550" style="width:550px;border-collapse:collapse">
						 <tr>
						  <td valign=top style="padding:0in 0in 0in 0in">
						  <div align=center>
						  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						   width="550" style="width:550px;border-collapse:collapse">
						   <tr>
							<td width=30 valign=top style="width:22.5pt;padding:0in 0in 0in 0in">
							<p class=MsoNormal align=right style="margin-bottom:0in;margin-bottom:
							.0001pt;text-align:right;line-height:normal"><span style="font-size:
							12.0pt;font-family:Arial,sans-serif"><img width=30 height=59
							id="Picture 2" src="http://ubitechsolutions.com/ubitechsolutions/Mailers/ubiAttendance/ubiAttendance/image002.jpg" alt=" "></span></p>
							</td>
							<td width="550" valign=top style="width:550px;padding:0in 37.6pt 0in 21.5pt">
							<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
							 align=center width="550" style="550px;border-collapse:collapse">
							 <tr>
							  <td valign=top style="padding:0in 0in 21.5pt 0in">
							  <p class=MsoNormal align=center style="margin-bottom:0in;margin-bottom:
							  .0001pt;text-align:left;line-height:22.55pt"><b><p style="font-size:20.0pt;font-family:Helvetica,sans-serif;
							  color:#606060;text-align:center;">Welcome - now let&#39;s get started!<br/>
							  </p>  	
								<p style="font-size:14.0pt;font-family:Helvetica,sans-serif;
							  color:#606060;text-align:center;">
								<a href="https://ubiattendance.ubihrm.com/index.php/services/activateOrg?iuser='.encrypt($orgid).'">Verify now to start your trial</a>
								</p>
							
								<div align=center>
							  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
							   width="550" style="width:550px;border-collapse:collapse">
							   <tr>
								<td align="center" style="padding:0in 0in 0in 0in">
								<a href="https://ubiattendance.ubihrm.com/index.php/services/activateOrg?iuser='.encrypt($orgid).'" target="_blank" style="font-size:20px;font-family:Helvetica,sans-serif;color:white;text-decoration:none">
								<p class=MsoNormal align=center style="margin-bottom:0in;								margin-bottom:.0001pt;text-align:center;line-height:normal; background:#52bad5;width: 250px;padding: 15px;">Verify your Account</span></b></span></p></a>
								</td>
							   </tr>
							  </table>
							  </div>
								<span
							  style="font-size:11pt;font-family:Helvetica,sans-serif;
							  color:#606060">
							  
							 <br/> Hello '.strtok($contact_person_name, " ").',
							  
								</span></b>
								<p style="text-align: left;" class="paragraph-text">
								Thanks for trying ubiAttendance. You&#39;re going to love it.<br/><br/>
								First thing&#39;s first:  <a href="https://ubiattendance.ubihrm.com/index.php/services/activateOrg?iuser='.encrypt($orgid).'">Verify your Account</a> to start exploring our world class App.<br/>Enjoy your 15-day trial to your heart`s content!<br/><br/><br/>Need more help?  <a href="mailto:ubiattendance@ubitechsolutions.com">Contact us</a> or <a target="_blank" href="https://www.youtube.com/channel/UCLplA-GWJOKZTwGlAaVKezg">View our Channel</a> and learn about key features and best practices.
								</p>
								
							  </p>
							  </td>

							 </tr>
							 <tr>
							 
							 </tr>
							 <tr>
							  <td valign=top style="padding:0in 0in 2.7pt 0in">
									Cheers,<br/>Team ubiAttendance<br/><a href="http://www.ubiattendance.com/" target="_blank">www.ubiattendance.com</a><br/> Tel/ Whatsapp:  +91-7067835131, 7067822132, 6264345452<br/>Email: ubiattendance@ubitechsolutions.com
							  </td>
							 </tr>
							 
							</table>

							</td>
						   </tr>
						  </table>
						  </div>
						  </td>
						 </tr>
						</table>
						</div>
						</td>
					   </tr>
					  </table>
					  </div>
					  </td>
					 </tr>
					</table>

					</div>


					</div>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "UbiAttendance- Account verification";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new("abhinav@ubitechsolutions.com", $subject, $message, $headers);
                    //sendEmail_new('vijay@ubitechsolutions.com', $subject, $message, $headers);
                   // sendEmail_new('ubiattendance@ubitechsolutions.com', $subject, $message, $headers);
					$result['status']     = true;
					echo json_encode($result); 
                    //sendEmail_new('deeksha@ubitechsolutions.com', $subject, $message, $headers);
                    //////////////////-------------activate mail body-close
	}
	
	
	
	public function register_orgTemp()
	{
		$org_name            = isset($_REQUEST['org_name']) ? $_REQUEST['org_name'] : "";
        $contact_person_name = isset($_REQUEST['name']) ? $_REQUEST['name'] : "";
        $email               = isset($_REQUEST['email']) ? strtolower(trim($_REQUEST['email'])) : "";
        $password            = isset($_REQUEST['password']) ? $_REQUEST['password'] : "123456";
        $countrycode         = isset($_REQUEST['countrycode']) ? $_REQUEST['countrycode'] : "";
        $phone               = isset($_REQUEST['phone']) ? $_REQUEST['phone'] : "";
        $county              = isset($_REQUEST['country']) ? $_REQUEST['country'] : "0";
        $address             = isset($_REQUEST['address']) ? $_REQUEST['address'] : "";
        $platform             = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : "";
        


        ///   referal data
                $referrerId = isset($_REQUEST['referrerId']) ? $_REQUEST['referrerId'] : "0";
				$currentReferrerDiscount=isset($_REQUEST['referrerAmt']) ? $_REQUEST['referrerAmt'] : "0";
				$currentReferenceDiscount=isset($_REQUEST['referrenceAmt']) ? $_REQUEST['referrenceAmt'] : "0";
				$validFrom=isset($_REQUEST['ReferralValidFrom']) ? $_REQUEST['ReferralValidFrom'] : "0";
				$validTo=isset($_REQUEST['ReferralValidTo']) ? $_REQUEST['ReferralValidTo'] : "0";
		
		
        //$password = ''.rand(100000,999999);
        //$password = $phone;
        $date                = date('Y-m-d H:i:s');
        //    $password = encrypt(make_rand_pass());
        $emp_id              = 0;
        $org_id              = 0;
        $data                = array();
        $data['f_name']      = $contact_person_name;
        $org                 = explode(" ", $org_name);
        //    $username=strtolower("admin@".$org[0].".com");
        $username            = strtolower($email);
        $counter             = 0;
        
        $sql              = "SELECT Id FROM OrganizationTemp where Email = '$email'";
        $this->db->query($sql);
		if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false1"; // email id duplicacy
            echo json_encode($data);
            return;
        }
        $sql = "SELECT * FROM OrganizationTemp where PhoneNumber = '$phone'  ";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false2"; // phone no. duplicacy
            echo json_encode($data);
            return;
        }


        $sql              = "SELECT Id FROM Organization where Email = '$email' AND cleaned_up != 1 AND delete_sts !=  1";
        $this->db->query($sql);
		if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false1"; // email id duplicacy
            echo json_encode($data);
            return;
        }
		
        $sql = "SELECT Id FROM UserMaster where Username = '" . encode5t($email) . "'   ";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false3"; // user register with this email duplicacy
            echo json_encode($data);
            return;
        }
        
        $sql = "SELECT * FROM Organization where PhoneNumber = '$phone'  AND  cleaned_up != 1 AND delete_sts !=  1 ";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false2"; // phone no. duplicacy
            echo json_encode($data);
            return;
        }
        
	
        
        $sql = "SELECT * FROM UserMaster where username_mobile = '" . encode5t($phone) . "'";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false4"; // user register with this phone duplicacy
            echo json_encode($data);
            return;
        }
		if(  $counter > 0)
		{
			$data['sts'] = "false"; // 
            echo json_encode($data);
		}
		else{

		$query = $this->db->query("insert into OrganizationTemp(Name,Contact_person,Email,countrycode,PhoneNumber,Country,Address,platform , password , referrerId, ReferralValidFrom, ReferralValidTo, referrerAmt, referrenceAmt) values('$org_name','$contact_person_name','".$email."','$countrycode','" . $phone. "',$county,'$address','$platform','".$password."' , $referrerId  ,'$validFrom','$validTo', '$currentReferrerDiscount','$currentReferenceDiscount')");

		if($this->db->affected_rows()>0)
		{
			$otp = "";
			$id =  $this->db->insert_id();
			$otp = $this->db->insert_id();
			$potp = strlen($otp);
			$digits = 6-$potp;
			$tempotp = rand(pow(10, $digits-1), pow(10, $digits)-1);
			$otp = $otp.$tempotp;
			$this->db->query("Update OrganizationTemp set OTP = '$otp' where id = $id");
			$message = '<html>
					<head>
					<title></title>
					</head>
					<body>
					<div style="text-align: left;  font-family: sans-serif">
					<p>Dear '.$contact_person_name.',</p>
					<p>The one-time password (OTP) to complete your company registration process is </p>
					<p style="color: #da320c; font-size: 19px; font-family: monospace;">'.$otp.'</p>
					<p> <span style="font-size: 16px;"  ><b>Please note:</b></span><span> The OTP is valid for 24 hours and can be used only once</span></p><br />
					<div>Thanks & regards,</div>
					<div>ubiAttendance Team</div>
					<div>Need any help? We are happy to help.</div>
					<div>Contact us on (India): +91 6264345452 / (Overseas): +971 55-5524131</div>
					<div>Email us at &nbsp;<a style="color: #0b6fec" href="ubiattendance@ubitechsolutions.com">ubiattendance@ubitechsolutions.com</a></div>
					</div>
					</body>
					</html>';
		        	$headers = '';
                    $subject = "ubiAttendance- Account verification";
                    sendEmail_new($email, $subject, $message, $headers);
			$data['sts'] = "true"; // 
            echo json_encode($data);
		}
	}
	}
	

	public function resend_otp()
        {

	    $userName1 = isset($_REQUEST['username']) ? trim(strtolower($_REQUEST['username'])) : '';
        $password1 = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
        $Id='';
        $email='';
        $contactPerson = '';
        
         $sql= "SELECT * FROM OrganizationTemp where (Email= '$userName1' or PhoneNumber= '$userName1')and password='$password1'" ;

		$query=$this->db->query($sql);
		if ($row = $query->row()) {
                     $Id = $row->Id;
                     $email = $row->Email;
                     $contactPerson = $row->Contact_person;

                      //print_r($email);
            $otp = "";
            $id= "";
            $id =  $Id;
			$otp = $Id;
			$potp = strlen($otp);
			$digits = 6-$potp;
			$tempotp = rand(pow(10, $digits-1), pow(10, $digits)-1);
			$otp = $otp.$tempotp;

			//print_r($otp);

			$this->db->query("Update OrganizationTemp set OTP = '$otp' where id = '$id'");

			$message = '<html>
					<head>
					<title></title>
					</head>
					<body>
					<div style="text-align: left;  font-family: sans-serif">
					<p>Dear '.$contactPerson.',</p>
					<p>The one-time password (OTP) to complete your company registration process is </p>
					<p style="color: #da320c; font-size: 19px; font-family: monospace;">'.$otp.'</p>
					<p> <span style="font-size: 16px;"  ><b>Please note:</b></span><span> The OTP is valid for 24 hours and can be used only once</span></p><br />
					<div>Thanks & regards,</div>
					<div>ubiAttendance Team</div>
					<div>Need any help? We are happy to help.</div>
					<div>Contact us on (India): +91 6264345452 / (Overseas): +971 55-5524131</div>
					<div>Email us at &nbsp;<a style="color: #0b6fec" href="ubiattendance@ubitechsolutions.com">ubiattendance@ubitechsolutions.com</a></div>
					</div>
					</body>
					</html>';
		        	$headers = '';
                    $subject = "ubiAttendance- Account verification";

                    sendEmail_new($email, $subject, $message, $headers);

			        $data['sts'] = "true";  // 
                    echo json_encode($data);


        }
        else{
        	 $data['sts'] = "false";  // 
                    echo json_encode($data);

        }

      //  $query = $this->db->query("select time_to_sec(timediff(CURRENT_TIMESTAMP , `CreatedDate`)) as time from OrganizationTemp 
        //	where id = '$Id' and mail_varified = 0");

       // if($row = $query->row())
		//{
			
		
        
        

   // }
		
	
    }
	
	
	public function varifyotp()
	{
		 $otp            = isset($_REQUEST['otp']) ? $_REQUEST['otp'] : "";
		$query = $this->db->query("select time_to_sec(timediff(CURRENT_TIMESTAMP , `LastModifiedDate`)) as time , Id,PhoneNumber,password , mail_varified from OrganizationTemp where OTP = $otp and mail_varified = 0");
		$data = array();

		if($row = $query->row())
		{
			if($row->time < 86400)
			{
				
			$res =  $this->register_orgnew($row->Id);
					if($res['sts'] == 'true')
					{
						$id = $row->Id;
						$data['sts'] = 'true';
						$data['phone'] = $row->PhoneNumber;
						$data['pass'] = $row->password;
						$query1 = $this->db->query("update OrganizationTemp set mail_varified = 1 where id =  $id");

					    echo json_encode($data);
					}
					else
					{
						$data['sts'] = 'otpused';
		            	echo json_encode($data);
					}
			
			}
			else{
				$data['sts'] = 'timeout';
		    	echo json_encode($data);
			}
		}
		else{
						$data['sts'] = 'false';
		            	echo json_encode($data);
	    	}
	}
	public function verifyOTPNew()
	{
		 $otp            = isset($_REQUEST['otp']) ? $_REQUEST['otp'] : "";
		  $userName1 = isset($_REQUEST['username']) ? trim(strtolower($_REQUEST['username'])) : '';
         $password1 = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
		// $query = $this->db->query("select time_to_sec(timediff(CURRENT_TIMESTAMP , `LastModifiedDate`)) as time , Id,PhoneNumber,password , mail_varified from OrganizationTemp where OTP = $otp and mail_varified = 0");
		 $query = $this->db->query("select time_to_sec(timediff(CURRENT_TIMESTAMP , `LastModifiedDate`)) as time ,Email, Id,PhoneNumber,password , mail_varified from OrganizationTemp where  OTP = '$otp' and mail_varified = 0 and (Email= '$userName1' or PhoneNumber= '$userName1')and password='$password1'");

		$data = array();

		if($row = $query->row())
		{
			if($row->time < 86400)
			{
				
			$res =  $this->register_orgnew($row->Id);
					if($res['sts'] == 'true')
					{
						$id = $row->Id;
						$data['sts'] = 'true';
						$data['phone'] = $row->PhoneNumber;
						$data['pass'] = $row->password;
						$query1 = $this->db->query("update OrganizationTemp set mail_varified = 1 where id =  $id");

					    echo json_encode($data);
					}
					else
					{
						$data['sts'] = 'otpused';
		            	echo json_encode($data);
					}
			
			}
			else{
				$data['sts'] = 'timeout';
		    	echo json_encode($data);
			}
		}
		else{
						$data['sts'] = 'false';
		            	echo json_encode($data);
	    	}
	}
	
	  public function register_orgnew($id)
    {
       
        $org_name            = "";
        $contact_person_name = "";
        $email               = "";
        $password            = "";
        $countrycode         = "";
        $phone               = "";
        $county              = "";
        $address             = "";
        $platform            = "";

		$query = $this->db->query("select * from OrganizationTemp where id = $id ");

		if($row = $query->row())
		{
	    $org_name            = $row->Name;
        $contact_person_name = $row->Contact_person;
        $email               = $row->Email;
        $password            = $row->password;
        $countrycode         = $row->countrycode;;
        $phone               = $row->PhoneNumber;
        $county              = $row->Country;
        $address             = $row->Address;
        $platform             = $row->platform;
		}

        
		
		
        //$password = ''.rand(100000,999999);
        //$password = $phone;
        $date                = date('Y-m-d H:i:s');
        //    $password = encrypt(make_rand_pass());
        $emp_id              = 0;
        $org_id              = 0;
        $data                = array();
        $data['f_name']      = $contact_person_name;
        $org                 = explode(" ", $org_name);
        //    $username=strtolower("admin@".$org[0].".com");
        $username            = strtolower($email);
        $counter             = 0;
        $sql              = "SELECT * FROM Organization where Email = '$email' AND cleaned_up != 1 AND delete_sts !=  1";
        $this->db->query($sql);
		if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false1"; // email id duplicacy
            return $data;
            
        }
		
        $sql = "SELECT * FROM UserMaster where Username = '" . encode5t($email) . "'   ";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false3"; // user register with this email duplicacy
            return $data;
            
        }
        
        $sql = "SELECT * FROM Organization where PhoneNumber = '$phone'  AND  cleaned_up != 1 AND delete_sts !=  1 ";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false2"; // phone no. duplicacy
            return $data;
            
        }
        
	
        
        $sql = "SELECT * FROM UserMaster where username_mobile = '" . encode5t($phone) . "'";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false4"; // user register with this phone duplicacy
            return $data;
            
        }
  
        if ($counter > 0) {
            $data['sts'] = "false"; // 
            return $data;
        } else {
            $data['sts'] = "true";
            $TimeZone    = 0;
            $query       = $this->db->query("SELECT * FROM `ZoneMaster` WHERE `CountryId`=$county");
            if ($row = $query->result())
                $TimeZone = $row[0]->Id;
            $days     = 1;
            $emplimit = 0;
			
			$addonbulkatt = 0;
			$addonlocationtrack = 0;
			$addonvisit = 0;
			$addongeofence = 0;
			$addonpayroll = 0;
			$addontimeoff = 0;
            $query22  = $this->db->query('SELECT * FROM ubitech_login limit 1');
            foreach ($query22->result_array() as $row22) {
                $days     = $row22['trial_days'];
                $emplimit = $row22['user_limit'];
				$addonbulkatt = $row22['bulk_attendance'];
				$addonlocationtrack = $row22['location_tracing'];
				$addonvisit = $row22['visit_punch'];
				$addongeofence = $row22['geo_fence'];
				$addonpayroll = $row22['payroll'];
				$addontimeoff = $row22['time_off'];
            }
            
            if ($countrycode == '' || $countrycode == '0')
                $countrycode = getCountryCodeById1($county);
            
            $query = $this->db->query("insert into Organization(Name,Email,countrycode,PhoneNumber,Country,Address,TimeZone,CreatedDate,LastModifiedDate,NoOfEmp,platform ,mail_varified ) values('$org_name','$email','$countrycode','" . $phone . "',$county,'$address',$TimeZone,'$date','$date',$emplimit,'$platform' , 1)");
            
            if ($query > 0) {
                
               
                
                $org_id = $this->db->insert_id();
                $zone   = getTimeZone($org_id);
                date_default_timezone_set($zone);
                $date  = date('Y-m-d');
                $query = $this->db->query("update Organization set CreatedDate=?,LastModifiedDate=? where Id=?", array(
                    $date,
                    $date,
                    $org_id
                ));
                
                
                $epassword = encrypt($password);
                $query1    = $this->db->query("insert into admin_login(username,password,email,OrganizationId,name) values('$username','$epassword','$email',$org_id,'$contact_person_name')");
                // this code for insert days trial days start //

                //////////////////////////////  Push Notifications //////////////////////////////////

                $query34    = $this->db->query("insert into NotificationStatus(OrganizationId) values($org_id)");

                //////////////////////////////   Push Notifications  ////////////////////////////////////
                
                
                $start_date = date('Y-m-d');
                
                // create default disable email alert
                $query33 = $this->db->query("INSERT INTO Alert_Settings(OrganizationId, Created_Date) VALUES ($org_id,'$start_date')");
                ///////////////////////////////////////
                
                $end_date = date('Y-m-d', strtotime("+" . $days . " days"));
				
                $query33  = $this->db->query("insert into licence_ubiattendance(OrganizationId,start_date,end_date,extended,user_limit,Addon_BulkAttn,Addon_LocationTracking,Addon_VisitPunch,Addon_GeoFence,Addon_Payroll,Addon_TimeOff) values($org_id,'$start_date','$end_date',1,$emplimit,$addonbulkatt,$addonlocationtrack,$addonvisit,$addongeofence,$addonpayroll,$addontimeoff)");
                // this code for insert days trial days end //
                
                //// This Code For Insert ShiftMaster,DepartmentMaster,DesignationMaster Table Start ////
                $data1 = array(
                    array(
                        'Name' => 'Trial Department',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Human Resource',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Finance',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Marketing',
                        'OrganizationId' => $org_id
                    )
                );
                $this->db->insert_batch('DepartmentMaster', $data1);
                $data1 = array(
                    array(
                        'Name' => 'Trial shift',
                        'TimeIn' => '09:00:00',
                        'TimeOut' => '18:00:00',
                        'OrganizationId' => $org_id
                    )
                );
                $this->db->insert_batch('ShiftMaster', $data1);
				
				/*$data1 = array(
                    array(
                        'Name' => 'Default Leave',
                        'LeaveDays' => 0,                        
                        'OrganizationId' => $org_id,
						'DefaultSts' => 1
                    )
                );
                $this->db->insert_batch('LeaveMaster', $data1);*/
				
                $data1 = array(
                    array(
                        'Name' => 'Trial Designation',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Manager',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'HR',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Clerk',
                        'OrganizationId' => $org_id
                    )
                );
                $this->db->insert_batch('DesignationMaster', $data1);
				/////////////// User Permission ////////////////////
				$roleid = getDesignationId("Trial Designation",$org_id);
				$data1 = array(
                    array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"12",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,					
                    ),
                    array(
                        'RoleId'=>$roleid,
					'ModuleId'=>"13",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
                    array(
                        'RoleId'=>$roleid,
					'ModuleId'=>"18",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
                    array(
                        'RoleId'=>$roleid,
					'ModuleId'=>"42",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                        'RoleId'=>$roleid,
					'ModuleId'=>"179",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"305",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"19",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"47",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"60",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    )
                );
                $this->db->insert_batch('UserPermission', $data1);
                ////  This Code For Insert ShiftMaster,DepartmentMaster,DesignationMaster Table End ////
                ///////////////////////////-----creating default user-start
                $shift = '0';
                $desg  = '0';
                $dept  = '0';
                $qry   = $this->db->query("select Id as shift from ShiftMaster where  OrganizationId=" . $org_id . " order by id limit 1");
                if ($r = $qry->result())
                    $shift = $r[0]->shift;
                ;
                $qry = $this->db->query("select Id as dept from DepartmentMaster where  OrganizationId=" . $org_id . " order by id limit 1");
                if ($r = $qry->result())
                    $dept = $r[0]->dept;
                ;
                $qry = $this->db->query("select Id as desg from DesignationMaster where  OrganizationId=" . $org_id . " order by id limit 1");
                if ($r = $qry->result())
                    $desg = $r[0]->desg;
                ;
                
                $qry = $this->db->query("insert into EmployeeMaster(FirstName,LastName,doj,countrycode,PersonalNo,Shift,OrganizationId,Department,Designation,CompanyEmail) values('$contact_person_name',' ','$date','$countrycode','" . encode5t($phone) . "',$shift,$org_id,$dept,$desg,'" . encode5t($email) . "')");
                if ($qry > 0) {
                    $emp_id = $this->db->insert_id();
                    $qry1   = $this->db->query("INSERT INTO `UserMaster`(`EmployeeId`,appSuperviserSts, `Password`, `Username`,`OrganizationId`,CreatedDate,LastModifiedDate,username_mobile,archive,HRSts,`Password_sts`) VALUES ($emp_id,1,'" . encode5t($password) . "','" . encode5t($email) . "',$org_id,'$start_date','$start_date','" . encode5t($phone) . "',1,1,1)");
                    if ($qry1 > 0)
                        $data['id'] = $emp_id;
                    $today = date('Y-m-d');
                     for ($i = 1; $i < 8; $i++)// create default weekly off
						$query = $this->db->query("INSERT INTO `ShiftMasterChild`(`ShiftId`,`Day`,`WeekOff`, `OrganizationId`, `ModifiedBy`, `ModifiedDate`) VALUES (?,?,'0,0,0,0,0',?,?,?)",array($shift,$i,$org_id,$emp_id,$today));
                      
                    $data['org_id'] = $org_id;
                }
                ///////////////////////////-----creating default user-end 
                $countryName = '';
                $query       = $this->db->query("SELECT Name FROM `CountryMaster` WHERE Id=$county");
                if ($row = $query->result())
                    $countryName = $row[0]->Name;
                if ($query1 > 0) {
                    //////////////////-------------activate mail body-strt
           
                  
                    return $data; 
                    // echo $admin_id =  $this->db->insert_id();
                }
				
				
		/*Work for saving referrer Id to referrels table*/
		

		        $referrerId = "0";
				$referringOrg=getOrgIdByEmpId($referrerId);
				$referrencedOrg=$org_id;
				$currentReferrerDiscount= "0";;
				$currentReferenceDiscount= "0";;
				$currentReferrerDiscountType="2";;
				$currentReferenceDiscountType="2";;
				$validFrom =  "00:00:00";;
				$validTo = "00:00:00";

		       $query = $this->db->query("select * from OrganizationTemp where id = $id ");

		       if($row = $query->row())
		         {
		         	$referrerId = $row->referrerId;
				    $referringOrg=getOrgIdByEmpId($referrerId);
				$referrencedOrg=$org_id;
				$currentReferrerDiscount=$row->referrerAmt;
				$currentReferenceDiscount=$row->referrenceAmt;
				$currentReferrerDiscountType="2";;
				$currentReferenceDiscountType="2";;
				$validFrom=$row->ReferralValidFrom;
				$validTo=$row->ReferralValidTo;

		         }
				
				
				$qry = $this->db->query("select * from licence_ubiattendance where OrganizationId=$referringOrg");
				$referrerDiscountValidUpTo='0000-00-00';
                if ($r = $qry->result())
				{
					$referrerDiscountValidUpTo=date("Y-m-d",strtotime($r[0]->end_date. ' +1 month'));
				}
				if($referrerId!="0" && $org_id!=0)
				{
					$this->db->query("INSERT INTO Referrals(ReferrerId, ReferringOrg, ReferenceId,ReferrencedOrg,DiscountForReferrer,DiscountForReferrence,ReferrerDiscountType,ReferenceDiscountType,DiscountType,ValidFrom,ValidTo,ReferrerDiscountValidUpTo,ReferrenceDate) VALUES ('$referrerId','$referringOrg','$emp_id','$referrencedOrg','$currentReferrerDiscount','$currentReferenceDiscount','$currentReferrerDiscountType','$currentReferenceDiscountType',2,'$validFrom','$validTo','$referrerDiscountValidUpTo','$date')");
					
				}
			
		/***************************************************/
				
            } else {
                $data['sts'] = 0;
                echo $data;
            }
            
        }
    }
	
 public function SendTempimage()
   {
	    $data=$_REQUEST['data'];
        $data=stripslashes($data);
        $decodedText = html_entity_decode($data);
        $decodedText=stripslashes($decodedText);
        $data=json_decode($decodedText,true);
		$orgid = 0;
		$empid = 0;
		$module = "";           ///it means it is attendance or visit
		$action =  ""  ;        /// it means it is timein or timeout
		$actionid = 0;   // timein or timeout id
		$pictureBase64 = "";
		$actionarray = array();
		$status = false;
		$localdbid = 123;
		for($i=0;$i<count($data);$i++){
			    $status = false;
				$orgid = $data[$i]["OrganizationId"];
				$empid = $data[$i]["EmployeeId"];
				$module = $data[$i]["Module"];           
				$action =  $data[$i]["Action"];       
				$actionid =$data[$i]["ActionId"];;  
				$pictureBase64 = $data[$i]["PictureBase64"];
			$new_name   = "https://ubitech.ubihrm.com/public/avatars/male.png";
			if($module == 'Attendance')
			{
				$new_name   = $action."_".$actionid."_".$empid . '_' . date('dmY') . ".jpg";
				 $pic=base64_decode($pictureBase64);
                if(LOCATION=='online')
                {
			     /* $result_save= S3::putObject($pic, 'ubiattendanceimages', 'attendance_images/'.$new_name, S3::ACL_PUBLIC_READ);
				   if(!$result_save)
				  {
					  continue;
				  }
				  $new_name= IMGPATH.'attendance_images/'.$new_name; */
				  if (!file_put_contents("tempimage/" . $new_name, $pic)){
			         continue; 
			      }
				  $file = TEMPIMAGE.$new_name;
                  exec("aws s3 mv $file s3://ubiattendanceimages/attendance_images/");
                 
                  

				  $new_name= IMGPATH.'attendance_images/'.$new_name;
                }
                else
                {
                file_put_contents('uploads/'. $new_name,$pic); 
				$new_name=IMGURL.$new_name; 
                }
                 $this->db->cache_delete_all();
                 $this->db->cache_off();
				if($action=='TimeIn')
				{
					$query = $this->db->query("update AttendanceMaster set EntryImage = '$new_name'  where id = $actionid  and EmployeeId = $empid ");
				}
				else
				{
					$query = $this->db->query("update AttendanceMaster set ExitImage = '$new_name'  where id = $actionid and EmployeeId = $empid ");
					
				}
				if($this->db->affected_rows()>0){
					$status = true;
					$localdbid = $data[$i]["Id"];
				}
				$actionarray[$i][$localdbid] = $status;
					
			}
		}
		echo json_encode($actionarray);
   }
	
	
	
	
    public function register_org()
    {
        $org_name            = isset($_REQUEST['org_name']) ? $_REQUEST['org_name'] : "";
        $contact_person_name = isset($_REQUEST['name']) ? $_REQUEST['name'] : "";
        $email               = isset($_REQUEST['email']) ? strtolower(trim($_REQUEST['email'])) : "";
        $password            = isset($_REQUEST['password']) ? $_REQUEST['password'] : "123456";
        $countrycode         = isset($_REQUEST['countrycode']) ? $_REQUEST['countrycode'] : "";
        $phone               = isset($_REQUEST['phone']) ? $_REQUEST['phone'] : "";
        $county              = isset($_REQUEST['country']) ? $_REQUEST['country'] : "0";
        $address             = isset($_REQUEST['address']) ? $_REQUEST['address'] : "";
        $platform             = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : "";
        
		
		
        //$password = ''.rand(100000,999999);
        //$password = $phone;
        $date                = date('Y-m-d H:i:s');
        //    $password = encrypt(make_rand_pass());
        $emp_id              = 0;
        $org_id              = 0;
        $data                = array();
        $data['f_name']      = $contact_person_name;
        $org                 = explode(" ", $org_name);
        //    $username=strtolower("admin@".$org[0].".com");
        $username            = strtolower($email);
        $counter             = 0;
        $sql              = "SELECT * FROM Organization where Email = '$email' AND cleaned_up != 1 AND delete_sts !=  1";
        $this->db->query($sql);
		if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false1"; // email id duplicacy
            echo json_encode($data);
            return;
        }
		
        $sql = "SELECT * FROM UserMaster where Username = '" . encode5t($email) . "'   ";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false3"; // user register with this email duplicacy
            echo json_encode($data);
            return;
        }
        
        $sql = "SELECT * FROM Organization where PhoneNumber = '$phone'  AND  cleaned_up != 1 AND delete_sts !=  1 ";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false2"; // phone no. duplicacy
            echo json_encode($data);
            return;
        }
        
	
        
        $sql = "SELECT * FROM UserMaster where username_mobile = '" . encode5t($phone) . "'";
        $this->db->query($sql);
        if ($this->db->affected_rows() > 0) {
            $counter++;
            $data['sts'] = "false4"; // user register with this phone duplicacy
            echo json_encode($data);
            return;
        }
        //$sql = "SELECT * FROM UserMaster where Username = '".encode5t($email)."' or username_mobile = '".encode5t($phone)."'";
        
        
        /*    $sql = "SELECT * FROM UserMaster where Username = '".encode5t($email)."' username_mobile = '".encode5t($phone)."'"; //or 
        $this->db->query($sql);
        if($this->db->affected_rows()>0){
        $data['sts']= "false3"; // 
        echo json_encode($data);
        }*/
        if ($counter > 0) {
            $data['sts'] = "false"; // 
            echo json_encode($data);
        } else {
            $data['sts'] = "true";
            $TimeZone    = 0;
            $query       = $this->db->query("SELECT * FROM `ZoneMaster` WHERE `CountryId`=$county");
            if ($row = $query->result())
                $TimeZone = $row[0]->Id;
            $days     = 1;
            $emplimit = 0;
			
			$addonbulkatt = 0;
			$addonlocationtrack = 0;
			$addonvisit = 0;
			$addongeofence = 0;
			$addonpayroll = 0;
			$addontimeoff = 0;
            $query22  = $this->db->query('SELECT * FROM ubitech_login limit 1');
            foreach ($query22->result_array() as $row22) {
                $days     = $row22['trial_days'];
                $emplimit = $row22['user_limit'];
				$addonbulkatt = $row22['bulk_attendance'];
				$addonlocationtrack = $row22['location_tracing'];
				$addonvisit = $row22['visit_punch'];
				$addongeofence = $row22['geo_fence'];
				$addonpayroll = $row22['payroll'];
				$addontimeoff = $row22['time_off'];
            }
            
            if ($countrycode == '' || $countrycode == '0')
                $countrycode = getCountryCodeById1($county);
            
            $query = $this->db->query("insert into Organization(Name,Email,countrycode,PhoneNumber,Country,Address,TimeZone,CreatedDate,LastModifiedDate,NoOfEmp,platform) values('$org_name','$email','$countrycode','" . $phone . "',$county,'$address',$TimeZone,'$date','$date',$emplimit,'$platform')");
            
            if ($query > 0) {
                
                /*     $postdata = http_build_query(
                array(
                'inq_title' => "",
                'inq_amount' => "",
                'lname' => "",
                'inq_source' => "",
                'inq_city' => "",
                'inq_state' => "",
                'inq_zipcode' => "",
                'inq_stage' => "New",
                'inq_type' => "",
                'inq_company' => "",
                'inq_industry' => "",
                'inq_website' => "",
                'inq_desc' => "",
                'org_id' => "ubitechsolutions.com",
                'product' => "Attendance Management Software",                            
                'fname' => $org_name,
                'email_id' => $email,
                'telephone_no' => $phone,
                'inq_address' => $address,
                'inq_mobile' => $phone,
                'inq_country' => $county                            
                )
                ); */
                /* 
                END Curl for SIA  RAAM  (CRM)
                
                
                */
                
                $org_id = $this->db->insert_id();
                $zone   = getTimeZone($org_id);
                date_default_timezone_set($zone);
                $date  = date('Y-m-d');
                $query = $this->db->query("update Organization set CreatedDate=?,LastModifiedDate=? where Id=?", array(
                    $date,
                    $date,
                    $org_id
                ));
                
                
                $epassword = encrypt($password);
                $query1    = $this->db->query("insert into admin_login(username,password,email,OrganizationId,name) values('$username','$epassword','$email',$org_id,'$contact_person_name')");
                // this code for insert days trial days start //
                
                
                $start_date = date('Y-m-d');
                
                // create default disable email alert
                $query33 = $this->db->query("INSERT INTO Alert_Settings(OrganizationId, Created_Date) VALUES ($org_id,'$start_date')");
                ///////////////////////////////////////
                
                $end_date = date('Y-m-d', strtotime("+" . $days . " days"));
				
                $query33  = $this->db->query("insert into licence_ubiattendance(OrganizationId,start_date,end_date,extended,user_limit,Addon_BulkAttn,Addon_LocationTracking,Addon_VisitPunch,Addon_GeoFence,Addon_Payroll,Addon_TimeOff) values($org_id,'$start_date','$end_date',1,$emplimit,$addonbulkatt,$addonlocationtrack,$addonvisit,$addongeofence,$addonpayroll,$addontimeoff)");
                // this code for insert days trial days end //
                
                //// This Code For Insert ShiftMaster,DepartmentMaster,DesignationMaster Table Start ////
                $data1 = array(
                    array(
                        'Name' => 'Trial Department',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Human Resource',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Finance',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Marketing',
                        'OrganizationId' => $org_id
                    )
                );
                $this->db->insert_batch('DepartmentMaster', $data1);
                $data1 = array(
                    array(
                        'Name' => 'Trial shift',
                        'TimeIn' => '09:00:00',
                        'TimeOut' => '18:00:00',
                        'OrganizationId' => $org_id
                    )
                );
                $this->db->insert_batch('ShiftMaster', $data1);
				
				/*$data1 = array(
                    array(
                        'Name' => 'Default Leave',
                        'LeaveDays' => 0,                        
                        'OrganizationId' => $org_id,
						'DefaultSts' => 1
                    )
                );
                $this->db->insert_batch('LeaveMaster', $data1);*/
				
                $data1 = array(
                    array(
                        'Name' => 'Trial Designation',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Manager',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'HR',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Clerk',
                        'OrganizationId' => $org_id
                    ),
                    array(
                        'Name' => 'Trial Designation',
                        'OrganizationId' => $org_id
                    )
                );
                $this->db->insert_batch('DesignationMaster', $data1);
				/////////////// User Permission ////////////////////
				$roleid = getDesignationId("Trial Designation",$org_id);
				$data1 = array(
                    array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"12",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,					
                    ),
                    array(
                        'RoleId'=>$roleid,
					'ModuleId'=>"13",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
                    array(
                        'RoleId'=>$roleid,
					'ModuleId'=>"18",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
                    array(
                        'RoleId'=>$roleid,
					'ModuleId'=>"42",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                        'RoleId'=>$roleid,
					'ModuleId'=>"179",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"305",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"19",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"47",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    ),
					array(
                    'RoleId'=>$roleid,
					'ModuleId'=>"60",
					'ViewPermission'=>1,
					'EditPermission'=>1,
					'DeletePermission'=>1,
					'AddPermission'=>1,
					'OrganizationId'=>$org_id,					
					'LastModifiedDate'=>$date,										
					'CreatedDate'=>$date,	
                    )
                );
                $this->db->insert_batch('UserPermission', $data1);
                ////  This Code For Insert ShiftMaster,DepartmentMaster,DesignationMaster Table End ////
                ///////////////////////////-----creating default user-start
                $shift = '0';
                $desg  = '0';
                $dept  = '0';
                $qry   = $this->db->query("select Id as shift from ShiftMaster where  OrganizationId=" . $org_id . " order by id limit 1");
                if ($r = $qry->result())
                    $shift = $r[0]->shift;
                ;
                $qry = $this->db->query("select Id as dept from DepartmentMaster where  OrganizationId=" . $org_id . " order by id limit 1");
                if ($r = $qry->result())
                    $dept = $r[0]->dept;
                ;
                $qry = $this->db->query("select Id as desg from DesignationMaster where  OrganizationId=" . $org_id . " order by id limit 1");
                if ($r = $qry->result())
                    $desg = $r[0]->desg;
                ;
                
                $qry = $this->db->query("insert into EmployeeMaster(FirstName,LastName,doj,countrycode,PersonalNo,Shift,OrganizationId,Department,Designation,CompanyEmail) values('$contact_person_name',' ','$date','$countrycode','" . encode5t($phone) . "',$shift,$org_id,$dept,$desg,'" . encode5t($email) . "')");
                if ($qry > 0) {
                    $emp_id = $this->db->insert_id();
                    $qry1   = $this->db->query("INSERT INTO `UserMaster`(`EmployeeId`,appSuperviserSts, `Password`, `Username`,`OrganizationId`,CreatedDate,LastModifiedDate,username_mobile,archive,HRSts) VALUES ($emp_id,1,'" . encode5t($password) . "','" . encode5t($email) . "',$org_id,'$start_date','$start_date','" . encode5t($phone) . "',1,1)");
                    if ($qry1 > 0)
                        $data['id'] = $emp_id;
                    $today = date('Y-m-d');
                     for ($i = 1; $i < 8; $i++)// create default weekly off
						$query = $this->db->query("INSERT INTO `ShiftMasterChild`(`ShiftId`,`Day`,`WeekOff`, `OrganizationId`, `ModifiedBy`, `ModifiedDate`) VALUES (?,?,'0,0,0,0,0',?,?,?)",array($shift,$i,$org_id,$emp_id,$today));
                      
                    $data['org_id'] = $org_id;
                }
                ///////////////////////////-----creating default user-end 
                $countryName = '';
                $query       = $this->db->query("SELECT Name FROM `CountryMaster` WHERE Id=$county");
                if ($row = $query->result())
                    $countryName = $row[0]->Name;
                if ($query1 > 0) {
                    //////////////////-------------activate mail body-strt
                    $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<div class=Section1>

					<div align=center>

					<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="550"
					 style="550px;border-collapse:collapse" align="center">
					 <tr style="height:328.85pt">
					  <td width=917 valign=top style="width:687.75px;padding:0in 0in 0in 0in;
					  height:328.85px">
					  <div align=center>
					  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="100%" style="width:100.0%;border-collapse:collapse">
					   <tr>
						<td valign=top style="background:#52BAD5;padding:0in 16.1pt 0in 16.1pt">
						<div align=center>
						<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						 width="100%" style="width:100.0%;border-collapse:collapse">
						 <tr>
						  <td valign=top style="padding:21.5pt 0in 21.5pt 0in">
						  <p class=MsoNormal align=center style="margin-bottom:0in;margin-bottom:
						  .0001pt;text-align:center;line-height:normal"><span style="font-size:
						  12.0pt;font-family:Arial,sans-serif"><img width=200 
						  id="Picture 1" src="http://ubitechsolutions.com/ubitechsolutions/Mailers/ubiAttendance/ubiAttendance/logo.png" alt="ubitech solutions"></span></p>
						  </td>
						 </tr>
						</table>
						</div>
						</td>
					   </tr>
					   <tr>
						<td valign=top style="background:#52BAD5;padding:0in 16.1pt 0in 16.1pt">
						<div align=center>
						<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						 width="100%" style="width:100.0%;border-collapse:collapse">
						 <tr>
						  <td valign=top style="padding:0in 0in 0in 0in">
						  <div align=center>
						  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						   width="100%" style="width:100.0%;border-collapse:collapse">
						   <tr>
							<td valign=top style="padding:0in 0in 0in 0in">
							<div align=center>
							<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
							 width="100%" style="width:100.0%;background:white">
							 <tr>
							  <td width="550" valign=top style="width:550px;padding:21.5pt 0in 0in 0in">
							  <p class=MsoNormal align=center style="margin-bottom:0in;margin-bottom:
							  .0001pt;text-align:center;line-height:normal"><span style="font-size:
							  12.0pt;font-family:Arial,sans-serif">&nbsp;</span></p>
							  </td>
							 </tr>
							</table>
							</div>
							</td>
						   </tr>
						  </table>
						  </div>
						  </td>
						 </tr>
						</table>
						</div>
						</td>
					   </tr>
					   <tr>
						<td valign=top style="padding:0in 0in 0in 0in">
						<div align=center>
						<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						 width="550" style="width:550px;border-collapse:collapse">
						 <tr>
						  <td valign=top style="padding:0in 0in 0in 0in">
						  <div align=center>
						  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
						   width="550" style="width:550px;border-collapse:collapse">
						   <tr>
							<td width=30 valign=top style="width:22.5pt;padding:0in 0in 0in 0in">
							<p class=MsoNormal align=right style="margin-bottom:0in;margin-bottom:
							.0001pt;text-align:right;line-height:normal"><span style="font-size:
							12.0pt;font-family:Arial,sans-serif"><img width=30 height=59
							id="Picture 2" src="http://ubitechsolutions.com/ubitechsolutions/Mailers/ubiAttendance/ubiAttendance/image002.jpg" alt=" "></span></p>
							</td>
							<td width="550" valign=top style="width:550px;padding:0in 37.6pt 0in 21.5pt">
							<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
							 align=center width="550" style="550px;border-collapse:collapse">
							 <tr>
							  <td valign=top style="padding:0in 0in 21.5pt 0in">
							  <p class=MsoNormal align=center style="margin-bottom:0in;margin-bottom:
							  .0001pt;text-align:left;line-height:22.55pt"><b><p style="font-size:20.0pt;font-family:Helvetica,sans-serif;
							  color:#606060;text-align:center;">Welcome - now let&#39;s get started!<br/>
							  </p>  	
								<p style="font-size:14.0pt;font-family:Helvetica,sans-serif;
							  color:#606060;text-align:center;">
								<a href="https://ubiattendance.ubihrm.com/index.php/services/activateAccount?iuser='.encrypt($emp_id).'">Verify now to start your trial</a>
								</p>
							
								<div align=center>
							  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
							   width="550" style="width:550px;border-collapse:collapse">
							   <tr>
								<td align="center" style="padding:0in 0in 0in 0in">
								<a href="https://ubiattendance.ubihrm.com/index.php/services/activateAccount?iuser='.encrypt($emp_id).'" target="_blank" style="font-size:20px;font-family:Helvetica,sans-serif;color:white;text-decoration:none">
								<p class=MsoNormal align=center style="margin-bottom:0in;								margin-bottom:.0001pt;text-align:center;line-height:normal; background:#52bad5;width: 250px;padding: 15px;">Verify your Account</span></b></span></p></a>
								</td>
							   </tr>
							  </table>
							  </div>
								<span
							  style="font-size:14.0pt;font-family:Helvetica,sans-serif;
							  color:#606060">
							  
							 <br/> Hello '.strtok($contact_person_name, " ").',
							  
								</span></b>
								<p style="text-align: left;" class="paragraph-text">
								Thanks for trying ubiAttendance. You&#39;re going to love it.<br/><br/>
								First thing&#39;s first:  <a href="https://ubiattendance.ubihrm.com/index.php/services/activateAccount?iuser='.encrypt($emp_id).'">Verify your Account</a> to start exploring our world class App.<br/>Enjoy your 15-day trial to your heart&#39;s content!<br/><br/><br/>Need more help?  <a href="mailto:ubiattendance@ubitechsolutions.com">Contact us</a> or <a target="_blank" href="https://www.youtube.com/channel/UCLplA-GWJOKZTwGlAaVKezg">View our Channel</a> and learn about key features and best practices.
								</p>
								
							  </p>
							  </td>

							 </tr>
							 <tr>
							 
							 </tr>
							 <tr>
							  <td valign=top style="padding:0in 0in 2.7pt 0in">
									Cheers,<br/>Team ubiAttendance<br/><a href="http://www.ubiattendance.com/" target="_blank">www.ubiattendance.com</a><br/> Tel/ Whatsapp: +91 70678 22132<br/>Email: ubiattendance@ubitechsolutions.com<br/>Skype: ubitech.solutions
							  </td>
							 </tr>
							 
							</table>

							</td>
						   </tr>
						  </table>
						  </div>
						  </td>
						 </tr>
						</table>
						</div>
						</td>
					   </tr>
					  </table>
					  </div>
					  </td>
					 </tr>
					</table>

					</div>


					</div>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "UbiAttendance- Account verification";
                    sendEmail_new($email, $subject, $message, $headers);
                  //-- sendEmail_new('vijay@ubitechsolutions.com', $subject, $message, $headers);
                 //--   sendEmail_new('ubiattendance@ubitechsolutions.com', $subject, $message, $headers);
					
                    //sendEmail_new('deeksha@ubitechsolutions.com', $subject, $message, $headers);
                    //////////////////-------------activate mail body-close
                    /////////////code by abhinav--CRM integration
					
                    $crn          = encode_vt5($org_id);
                    $country_name = getName("CountryMaster", "Name", "id", $county);
                    
                    
                   //$url = "http://ubitechsolutions.in/ubitech/UBICRM_SANDBOX/UbiAttendance_Integration.php";            
                     $url = "https://ubirecruit.com/UBICRMNEW/GetLeadJson/";
                    
				/*	$ch  = curl_init($url);
                    $arr = array(
                        'inq_salutation' => "",
                        'fname' => $contact_person_name,
                        'lname' => "",
                        'email_id' => $email,
                        'telephone_no' => "(" . $countrycode . ")" . $phone,
                        'inq_source' => "Mobile App registration",
                        'inq_address' => $address,
                        'inq_city' => $address,
                        'inq_state' => "",
                        'inq_country' => $country_name,
                        'inq_zipcode' => "",
                        'inq_stage' => "Trial",
                        'inq_type' => "",
                        'inq_company' => $org_name,
                        'inq_mobile' => "(+" . $countrycode . ")" . $phone,
                        'inq_industry' => "",
                        'inq_website' => "",
                        'inq_desc' => "CRN no. - " . $crn,
                        'product' => "ubiAttendance",
                        'orgid' => "==AUVZ0RW5GaKJFbaNVTWJVU"
                    );
					
                    $payload = json_encode($arr);
                    
                    //$arrval = str_replace("\\","",$arrval);
                    Trace($payload);
                    //attach encoded JSON string to the POST fields
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    
                    //set the content type to application/json
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type:application/json'
                    ));
                    
                    //return response instead of outputting
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    
                    //execute the POST request
                    $result = curl_exec($ch);
                    
                    //close cURL resource
                    curl_close($ch);
                    /////////////code by abhinav--CRM integration--close 
                    
                    */
                    echo json_encode($data); 
                    // echo $admin_id =  $this->db->insert_id();
                }
				
				
		/*Work for saving referrer Id to referrels table*/
		
		
		
				$referrerId = isset($_REQUEST['referrerId']) ? $_REQUEST['referrerId'] : "0";
				$referringOrg=getOrgIdByEmpId($referrerId);
				$referrencedOrg=$org_id;
				$currentReferrerDiscount=isset($_REQUEST['referrerAmt']) ? $_REQUEST['referrerAmt'] : "0";;
				$currentReferenceDiscount=isset($_REQUEST['referrenceAmt']) ? $_REQUEST['referrenceAmt'] : "0";;
				$currentReferrerDiscountType="2";;
				$currentReferenceDiscountType="2";;
				$validFrom=isset($_REQUEST['ReferralValidFrom']) ? $_REQUEST['ReferralValidFrom'] : "0";;
				$validTo=isset($_REQUEST['ReferralValidTo']) ? $_REQUEST['ReferralValidTo'] : "0";
				
				$qry = $this->db->query("select * from licence_ubiattendance where OrganizationId=$referringOrg");
				$referrerDiscountValidUpTo='0000-00-00';
                if ($r = $qry->result())
				{
					$referrerDiscountValidUpTo=date("Y-m-d",strtotime($r[0]->end_date. ' +1 month'));
				}
				if($referrerId!="0" && $org_id!=0)
				{
					$this->db->query("INSERT INTO Referrals(ReferrerId, ReferringOrg, ReferenceId,ReferrencedOrg,DiscountForReferrer,DiscountForReferrence,ReferrerDiscountType,ReferenceDiscountType,DiscountType,ValidFrom,ValidTo,ReferrerDiscountValidUpTo,ReferrenceDate) VALUES ('$referrerId','$referringOrg','$emp_id','$referrencedOrg','$currentReferrerDiscount','$currentReferenceDiscount','$currentReferrerDiscountType','$currentReferenceDiscountType',2,'$validFrom','$validTo','$referrerDiscountValidUpTo','$date')");
					
				}
			
		/***************************************************/
				
            } else {
                $data['sts'] = 0;
                echo json_encode($data);
            }
            
        }
    }
	
	
    public function activateAccount()
    {
        $empid    = isset($_REQUEST['iuser']) ? decrypt($_REQUEST['iuser']) : 0;
        $org_id   = 0;
        $email    = '(registered email id)';
        $contact  = '(registered contact no.)';
        $password = '';
        $name     = '';
        $query    = $this->db->query("select Username,username_mobile,Password,OrganizationId,(SELECT FirstName from  EmployeeMaster where  EmployeeMaster.Id=?)as FirstName from UserMaster where EmployeeId = ? order by Id limit 1", array(
            $empid,
            $empid
        ));
        if ($row = $query->result()) {
            $org_id   = $row[0]->OrganizationId;
            $email    = decode5t($row[0]->Username);
            $contact  = decode5t($row[0]->username_mobile);
            $password = decode5t($row[0]->Password);
            $name     = $row[0]->FirstName;
        }
        
        $updSts = 0;
        $sql    = "update UserMaster set archive=1,VisibleSts = 1 where EmployeeId = $empid";
        $query1 = $this->db->query($sql);
        $updSts = $this->db->affected_rows();
        $query  = $this->db->query("UPDATE `Organization` SET `mail_varified`=1 WHERE Id=(select OrganizationId from UserMaster where EmployeeId = ?)", array(
            $empid
        ));
        $updSts += $this->db->affected_rows();
        $this->db->close();
        if ($updSts) {
            $message = "Hello " . $name . "<br/><br/>
                Greetings from ubiAttendance Team…! <br/><br/>

                Congratulations! <b>'" . getOrgName($org_id) . "'</b> is successfully registered. You have been assigned the <b>Admin Rights</b>.<br/>

                <b>Company's Reference No. (CRN):</b> " . encode_vt5($org_id) . "<br/><br/>

                <b>Login details for Mobile App</b><br/>
                Username:    " . $email . " or " . $contact . "<br/>
                Password:    " . $password . "<br/><br/><br/>
				
               <b>Login details for Web Admin Panel (For Desktop/Laptop)</b><br/>
			    Login Link- https://ubiattendance.ubihrm.com/index.php/
                Username:    " . $email . "<br/>
                Password:    " . $password . "<br/><br/><br/>

                Cheers,<br/>
                Team ubiAttendance";
            $headers = 'From: <noreply@ubiattendance.com>' . "\r\n";
            $subject = $name . ", your ubiAttendance Login Details";
            sendEmail_new($email, $subject, $message, $headers);
            //--sendEmail_new('vijay@ubitechsolutions.com', $subject, $message, $headers);
            //--sendEmail_new('ubiattendance@ubitechsolutions.com', $subject, $message, $headers);
        }
        return $updSts;
    }
    public function activateOrg()
    {
        $org_id = isset($_REQUEST['iuser']) ? decrypt($_REQUEST['iuser']) : 0;
        $query1 = $this->db->query("update UserMaster set archive=1,VisibleSts = 1 where OrganizationId = ?", array(
            $org_id
        ));
        $updSts = $this->db->affected_rows();
        $query  = $this->db->query("UPDATE `Organization` SET `mail_varified`=1 WHERE Id=?", array(
            $org_id
        ));
        $updSts += $this->db->affected_rows();
        $this->db->close();
        return $updSts;
    }
    public function getAllDesg($orgid)
    {
        $query = $this->db->query("SELECT `Id`, `Name` FROM `DesignationMaster`  WHERE OrganizationId=? and archive = 1 order by name", array(
            $orgid
        ));
        echo json_encode($query->result());
    }
    
    public function getAllDept($orgid)
    {
        $query = $this->db->query("SELECT `Id`, `Name`,`archive` FROM `DepartmentMaster`  WHERE OrganizationId=? and archive = 1 order by name", array(
            $orgid
        ));
        echo json_encode($query->result());
    }
    
    public function DesignationMaster()
    {
        $res=0;
		$orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:0;
        $query = $this->db->query("SELECT `Id`, if(LENGTH(`Name`)>30 , concat(SUBSTR(Name, 1, 30), '....') , Name) as  Name , archive FROM `DesignationMaster`  WHERE OrganizationId=?  order by name", array(
            $orgid
        ));
        $date  = date('Y-m-d');

        foreach ($query->result() as $row){
		    $data['name']=$row->Name;
            $data['archive']=$row->archive;
      
            if( (strtoupper($data['name']) == "TRIAL DESIGNATION") && ($data['archive']) == '1'){
            $res = 1;

            }
        }

        if($res == 1){
            $query = $this->db->query("SELECT `Id`, if(LENGTH(`Name`)>30 , concat(SUBSTR(Name, 1, 30), '....') , Name) as  Name , archive FROM `DesignationMaster`  WHERE OrganizationId=?  order by name", array(
                $orgid
            ));
        }
        else{
            $query = $this->db->query("INSERT INTO `DesignationMaster`(`Name`, `OrganizationId`, `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`,`Description`, `archive`) VALUES (?,?,?,?,?,?,?,?,?)", array(
                'Trial Designation', $orgid,$date,'0',$date,'0','0','0','1'
            ));

            $query = $this->db->query("SELECT `Id`, if(LENGTH(`Name`)>30 , concat(SUBSTR(Name, 1, 30), '....') , Name) as  Name , archive FROM `DesignationMaster`  WHERE OrganizationId=?  order by name", array(
                $orgid
            ));

      }


        echo json_encode($query->result());
    }
    
    public function DepartmentMaster() // somya
    {
        $orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:0;
        $res = "";
        $query = $this->db->query("SELECT `Id`, if(LENGTH(`Name`)>30 , concat(SUBSTR(Name, 1, 30), '....') , Name) as  Name, archive FROM `DepartmentMaster`  WHERE OrganizationId=? order by name", array(
            $orgid
        ));
        $date  = date('Y-m-d');

        foreach ($query->result() as $row){
		    $data['name']=$row->Name;
            $data['archive']=$row->archive;
      
            if( (strtoupper($data['name']) == "TRIAL DEPARTMENT") && ($data['archive']) == '1'){
            $res = 1;

            }
        }

        if($res == 1){
            $query = $this->db->query("SELECT `Id`, if(LENGTH(`Name`)>30 , concat(SUBSTR(Name, 1, 30), '....') , Name) as  Name, archive FROM `DepartmentMaster`  WHERE OrganizationId=? order by name", array(
                $orgid
            ));
        }
        else{
            $query = $this->db->query("INSERT INTO `DepartmentMaster`(`Name`, `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `OrganizationId`,`archive`) VALUES (?,?,?,?,?,?,?,?)", array(
                "Trial Department",$date,'0',$date,'0','0',$orgid,'1'
            ));

            $query = $this->db->query("SELECT `Id`, if(LENGTH(`Name`)>30 , concat(SUBSTR(Name, 1, 30), '....') , Name) as  Name, archive FROM `DepartmentMaster`  WHERE OrganizationId=? order by name", array(
                $orgid
            ));

      }

       echo json_encode($query->result());
    }
    
    public function ClientMaster()
    {
        $orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:0;
        $query = $this->db->query("SELECT `Id`, `Company`, `Name`, `Contact`, `Email`, `Address`, `City`, `Country`,`Description`, `OrganizationId`,`status`, `createdBy`,`ModifiedDate`,`ModifiedById`,`Platform` FROM `ClientMaster`  WHERE OrganizationId=? and status in (1,2) order by Company", array(
            $orgid
        ));

        echo json_encode($query->result());
    }
    
    public function addClient(){
        $empid=isset($_REQUEST['empid'])?$_REQUEST['empid']:"";
        $orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:"";
        $companyName=isset($_REQUEST['comp_name'])?$_REQUEST['comp_name']:"";
        $contactPerson=isset($_REQUEST['name'])?$_REQUEST['name']:"";
        $address=isset($_REQUEST['address'])?$_REQUEST['address']:"";
        $country=isset($_REQUEST['country'])?$_REQUEST['country']:"";
        $city=isset($_REQUEST['city'])?$_REQUEST['city']:"";
        $countrycode=isset($_REQUEST['countrycode'])?$_REQUEST['countrycode']:"";
        $phone=isset($_REQUEST['phone'])?$_REQUEST['phone']:"";
        $email=isset($_REQUEST['email'])?$_REQUEST['email']:"";
        $desc=isset($_REQUEST['description'])?$_REQUEST['description']:"";
        $status=isset($_REQUEST['status'])?$_REQUEST['status']:'0';
        $platform=isset($_REQUEST['platform'])?$_REQUEST['platform']:"";
        $date = date('Y-m-d');
        $data['sts']="";
        $res = 0;
        $query = $this->db->query("SELECT `Id`, Company, Email, Contact FROM `ClientMaster` WHERE OrganizationId=? order by Name", array(
            $orgid
        ));
        
        foreach ($query->result() as $row){
            if(strtoupper($row->Company) == strtoupper($companyName)){
                $res = 1;
            }else if(strtoupper($row->Email) == strtoupper($email)){
                $res = 2;
            }else if($row->Contact == $phone){
                $res = 3;
            }
        }
        
        
        if($res == 1){
            $data['sts']="companynamealreadyexists";
        }else if($res == 2){
            $data['sts']="emailalreadyexists";
        }else if($res == 3){
            $data['sts']="contactalreadyexists";
        }else{
            $query1 = $this->db->query("INSERT INTO `ClientMaster`(`Company`, `Name`, `Contact`, `Email`, `Address`, `City`, `Country`,`Description`, `OrganizationId`,`status`, `createdBy`,`ModifiedDate`,`ModifiedById`,`Platform`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)", array(
                 $companyName,$contactPerson,$phone,$email,$address,$city,$countrycode,$desc,$orgid,$status,$empid,$date,$empid,$platform
            ));
            if($query1 > 0) {
                $data['sts']="true";
                $data['id']=$this->db->insert_id();
            }else{
                $data['sts']="false";
            }
        }
        
        echo json_encode($data);
    }
    
    public function editClient(){
        $clientid=isset($_REQUEST['clientid'])?$_REQUEST['clientid']:"";
        $empid=isset($_REQUEST['empid'])?$_REQUEST['empid']:"";
        $orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:"";
        $companyName=isset($_REQUEST['comp_name'])?$_REQUEST['comp_name']:"";
        $contactPerson=isset($_REQUEST['name'])?$_REQUEST['name']:"";
        $address=isset($_REQUEST['address'])?$_REQUEST['address']:"";
        $country=isset($_REQUEST['country'])?$_REQUEST['country']:"";
        $city=isset($_REQUEST['city'])?$_REQUEST['city']:"";
        $countrycode=isset($_REQUEST['countrycode'])?$_REQUEST['countrycode']:"";
        $phone=isset($_REQUEST['phone'])?$_REQUEST['phone']:"";
        $email=isset($_REQUEST['email'])?$_REQUEST['email']:"";
        $desc=isset($_REQUEST['description'])?$_REQUEST['description']:"";
        $platform=isset($_REQUEST['platform'])?$_REQUEST['platform']:"";
        $date = date('Y-m-d');
        $data['sts']="";
        $res = 0;
        //echo "SELECT `Id`, Company, Email, Contact FROM `ClientMaster` WHERE OrganizationId= $orgid and Id!=$clientid order by Name";
        $query = $this->db->query("SELECT `Id`, Company, Email, Contact FROM `ClientMaster` WHERE OrganizationId=$orgid and Id!=$clientid order by Name");
        
        foreach ($query->result() as $row){
            if(strtoupper($row->Company) == strtoupper($companyName)){
                $res = 1;
            }else if(strtoupper($row->Email) == strtoupper($email)){
                $res = 2;
            }else if($row->Contact == $phone){
                $res = 3;
            }
        }
        
        if($res == 1){
            $data['sts']="companynamealreadyexists";
        }else if($res == 2){
            $data['sts']="emailalreadyexists";
        }else if($res == 3){
            $data['sts']="contactalreadyexists";
        }else{
            $query1 = $this->db->query("UPDATE `ClientMaster` set `Company`=?, `Name`=?, `Contact`=?, `Email`=?, `Address`=?, `City`=?, `Country`=?,`Description`=?,`ModifiedDate`=?,`ModifiedById`=?,`Platform`=? where Id=? and OrganizationId=?", array(
                 $companyName,$contactPerson,$phone,$email,$address,$city,$countrycode,$desc,$date,$empid,$platform,$clientid,$orgid
            ));
            if($query1 > 0) {
                $data['sts']="true";
            }else{
                $data['sts']="false";
            }
        }
        
        echo json_encode($data);
    }

    public function shiftMaster()
    {
        $res=0;
		$orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:0;
        $query = $this->db->query("SELECT * FROM `ShiftMaster`  WHERE OrganizationId=?  order by Time(Timein)", array(
            $orgid
        ));

        $date  = date('Y-m-d');
    foreach ($query->result() as $row){
		    $data['name']=$row->Name;
            $data['archive']=$row->archive;
      
            if( (strtoupper($data['name']) == "TRIAL SHIFT") && ($data['archive']) == '1'){
            $res = 1;

            }
        }

        if($res == 1){
            $query = $this->db->query("SELECT * FROM `ShiftMaster`  WHERE OrganizationId=?  order by Time(Timein)", array(
                $orgid
            ));
        }
        else{
            $query = $this->db->query("INSERT INTO `ShiftMaster`(`Name`, `TimeIn`, `TimeOut`, `TimeInGrace`, `TimeOutGrace`, `TimeInBreak`, `TimeOutBreak`, `OrganizationId`, `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `BreakInGrace`, `BreakOutGrace`, `archive`,shifttype) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", array(
                'Trial Shift','09:00:00','18:00:00','17:00:00','17:00:00','00:00:00','00:00:00',$orgid,$date,0,$date,0,0,'00:00:00','$bog','1','1'
            ));
            $shift_id = $this->db->insert_id();
			 for ($i = 1; $i < 8; $i++)// create default weekly off
						$query1 = $this->db->query("INSERT INTO `ShiftMasterChild`(`ShiftId`,`Day`,`WeekOff`, `OrganizationId`, `ModifiedBy`, `ModifiedDate`) VALUES (?,?,'0,0,0,0,0',?,0,?)",array($shift_id,$i,$orgid,$date));

            $query = $this->db->query("SELECT * FROM `ShiftMaster`  WHERE OrganizationId=?  order by Time(Timein)", array(
                $orgid
            ));

      }
        echo json_encode($query->result());
    }

    
 public function updateEmp()
       {
        $f_name      = isset($_REQUEST['f_name']) ? ucfirst($_REQUEST['f_name']) : '';
        $l_name      = isset($_REQUEST['l_name']) ? ucfirst($_REQUEST['l_name']) : '';
        $password1   = encode5t(isset($_REQUEST['password']) ? $_REQUEST['password'] : '');
        $username    = isset($_REQUEST['username']) ? encode5t(strtolower($_REQUEST['username'])) : '';
        $shift       = isset($_REQUEST['shift']) ? $_REQUEST['shift'] : '';
        $designation = isset($_REQUEST['designation']) ? $_REQUEST['designation'] : '';
        $department  = isset($_REQUEST['department']) ? $_REQUEST['department'] : '';
        $contact     = isset($_REQUEST['contact']) ? encode5t($_REQUEST['contact']) : '';
        $org_id      = isset($_REQUEST['org_id']) ? $_REQUEST['org_id'] : '';
        $countrycode = isset($_REQUEST['countrycode']) ? $_REQUEST['countrycode'] : "";
        $country     = isset($_REQUEST['country']) ? $_REQUEST['country'] : 0;
        $admin       = isset($_REQUEST['admin']) ? $_REQUEST['admin'] : 0; // 1 if emp added by admin
		$uid       = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
		$empid       = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
        $data        = array();
        
        if($uid!=0)
        	$zone  = getEmpTimeZone($uid,$org_id); // to set the timezone by employee country.
        else	
            $zone  = getTimeZone($org_id);

       // $zone        = getTimeZone($org_id);
        date_default_timezone_set($zone);
        $date        = date('Y-m-d H:i:s');
        $data['id']  = 0;
        $data['sts'] = 0;
        $ml          = 0;
        $con         = 0;
        
        if ($username != '') {
            $sql = "SELECT Id FROM UserMaster where username = '" . $username . "' AND EmployeeId != '$empid'  ";
            $this->db->query($sql);
            $ml = $this->db->affected_rows();
        }
        if ($contact != '') {
            $sql = "SELECT Id FROM UserMaster where username_mobile = '$contact' AND  EmployeeId != '$empid'   ";
            $this->db->query($sql);
            $con = $this->db->affected_rows();
        }
        if ($con > 0) {
            $data['sts'] = 3; // if Contact already exist
        } else if ($ml > 0) {
            $data['sts'] = 2; // if email id already exist
        } else {
       
            $query = $this->db->query("  update EmployeeMaster set FirstName = ?, LastName=? ,PersonalNo = ?,Shift = ? ,Department = ?,Designation = ?,CompanyEmail =  ? ,LastModifiedDate=? ,LastModifiedById=?  WHERE  Id = ?" , array($f_name , $l_name, $contact , $shift , $department , $designation , $username  , $date , $uid , $empid) );
            if ($query > 0) {
               
                $query1 = $this->db->query("update `UserMaster` set  `Password` = ?, `Username` = ? , LastModifiedDate = ? , username_mobile = ? ,LastModifiedById=? where EmployeeId = ? " ,array($password1,$username, $date,$contact , $uid , $empid) );
				
				  if ($query > 0)
				  {
					   $data['sts'] = 1; // Update successfully
			 $date = date("y-m-d H:i:s");
             $orgid =$org_id ;
             $id =$uid ;
             $module = "Attendance App";
             $actionperformed = "<b>".getEmpName($empid)."</b>  details has been updated from <b> Attendance App  </b>";
             $activityby = 1;
             $query = $this->db->query("INSERT INTO ActivityHistoryMaster( LastModifiedDate,LastModifiedById,Module, ActionPerformed, OrganizationId,ActivityBy,adminid) VALUES (?,?,?,?,?,?,?)",array($date,$id,$module,$actionperformed,$orgid,$activityby,$id));
             
				  }
            }
        }
        echo json_encode($data);
    }
	
	
	 public function sendsms()
      {
		 $empid      = isset($_REQUEST['uid']) ? ucfirst($_REQUEST['uid']) : '';
        $sms      = isset($_REQUEST['sms']) ? ucfirst($_REQUEST['sms']) : '';
        $org_id      = isset($_REQUEST['org_id']) ? ucfirst($_REQUEST['org_id']) : '';
		$message = "<html>
                              <head>
                              <title>ubiAttendance</title>
                              </head>
                              <body> Hello i am " . getEmpName( $empid) . "  from ".getOrgName($org_id)." ,<br/>
                              <p>
                              <p>". $sms."</p>
                              <br/><br/>
                              <b>My Login Details are:</b><br />
                              Username(Phone#): " .getPhone( $empid) . "<br/>
                              Password: " . getPassword( $empid) . "<br/>
                              </b>
                              </p>
                             
                          </body>
                          </html>";
                            // Always set content-type when sending HTML email
                            $headers = "MIME-Version: 1.0" . "\r\n";
                            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                            // More headers
                            $headers .= 'From: <noreply@ubiattendance.com>' . "\r\n";
                            //$headers .= 'Cc: vijay@ubitechsolutions.com' . "\r\n";
                            $subject = "Enquiry on ubiattendance.";
                            //sendEmail_new($empmailid, $subject, $message, $headers);
                            echo  sendEmail_new('support@ubitechsolutions.com', $subject, $message, $headers);
	}
	
    public function registerEmp()
    {
        $f_name      = isset($_REQUEST['f_name']) ? ucfirst($_REQUEST['f_name']) : '';
        $l_name      = isset($_REQUEST['l_name']) ? ucfirst($_REQUEST['l_name']) : '';
        $password1   = encode5t(isset($_REQUEST['password']) ? $_REQUEST['password'] : '');
        $username    = isset($_REQUEST['username']) ? encode5t(strtolower($_REQUEST['username'])) : '';
        $shift       = isset($_REQUEST['shift']) ? $_REQUEST['shift'] : '';
        $designation = isset($_REQUEST['designation']) ? $_REQUEST['designation'] : '';
        $department  = isset($_REQUEST['department']) ? $_REQUEST['department'] : '';
        $contact     = isset($_REQUEST['contact']) ? encode5t($_REQUEST['contact']) : '';
        $org_id      = isset($_REQUEST['org_id']) ? $_REQUEST['org_id'] : '';
        $countrycode = isset($_REQUEST['countrycode']) ? $_REQUEST['countrycode'] : "";
        $country     = isset($_REQUEST['country']) ? $_REQUEST['country'] : 0;
        $admin       = isset($_REQUEST['admin']) ? $_REQUEST['admin'] : 0; // 1 if emp added by admin
		$uid       = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $data        = array();
        if($uid!=0)	
            $zone  = getEmpTimeZone($uid,$org_id); // to set the timezone by employee country.
       	else
          	$zone  = getTimeZone($org_id);
       
	    date_default_timezone_set($zone);
        $date        = date('Y-m-d H:i:s');
        $data['id']  = 0;
        $data['sts'] = 0;
        $ml          = 0;
        $con         = 0;
        
        if ($username != '') {
            $sql = "SELECT * FROM UserMaster where username = '" . $username . "'";
            $this->db->query($sql);
            $ml = $this->db->affected_rows();
        }
        if ($contact != '') {
            $sql = "SELECT * FROM UserMaster where username_mobile = '" . $contact . "' ";
            $this->db->query($sql);
            $con = $this->db->affected_rows();
        }
        if ($con > 0) {
            $data['sts'] = 3; // if Contact already exist
        } else if ($ml > 0) {
            $data['sts'] = 2; // if email id already exist
        } else {
            /*----------------default shift/dept/designation-------------------*/
            if ($shift == '' || $shift == '0') {
                $qry = $this->db->query("SELECT Id FROM `ShiftMaster` WHERE OrganizationId=$org_id  order by Id ASC limit 1");
                if ($r = $qry->result())
                    $shift = $r[0]->Id;
            }
            if ($department == '' || $department == '0') {
                $qry = $this->db->query("SELECT Id FROM `DepartmentMaster` WHERE OrganizationId=$org_id order by Id ASC limit 1 ");
                if ($r = $qry->result())
                    $department = $r[0]->Id;
            }
            if ($designation == '' || $designation == '0') {
                $qry = $this->db->query("SELECT Id FROM `DesignationMaster` WHERE OrganizationId=$org_id  order by Id ASC limit 1");
                if ($r = $qry->result())
                    $designation = $r[0]->Id;
            }
            /*----------------default shift/dept/designation-close-------------------*/
            $query = $this->db->query("insert into EmployeeMaster(FirstName,LastName,PersonalNo,Shift,OrganizationId,Department,Designation,CompanyEmail,countrycode,CurrentCountry,CreatedDate,doj) values('$f_name','$l_name','$contact',$shift,$org_id,$department,$designation,'$username','$countrycode',$country,'$date','$date')");
            if ($query > 0) {
                $emp_id = $this->db->insert_id();
                $query1 = $this->db->query("INSERT INTO `UserMaster`(`EmployeeId`, `Password`, `Username`,`OrganizationId`,CreatedDate,LastModifiedDate,username_mobile) VALUES ($emp_id,'$password1','$username',$org_id,'$date','$date','$contact')");
                if ($query1 > 0) {
                    $data['sts'] = 1;
                    $data['id']  = $emp_id;
                    if ($admin == 1) { //emp added by admin
                        ///////////////////mail drafted to admin
                        $message = "<html>
                        <head>
                        <title>ubiAttendance</title>
                        </head>
                        <body>Dear Admin,<br/>
                        <p>
                        Congratulations!! <b>" . $f_name . " " . $l_name . "</b> has been added to the Employees’ List of<b> " . getOrgName($org_id) . "</b>.
                        <br/> The details registered are:<br/><br/>
                        <b>
                        
                        Employee: " . $f_name . " " . $l_name . " <br/>
                        
                        Username(Phone#): " . $_REQUEST['contact'] . "<br/>                    
                        </b>
                        </p>
                        <p>
                            <a href='https://ubiattendance.ubihrm.com/index.php/services/useridcard/" . $org_id . "/" . $emp_id . "' target='_blank' >
                            Generate" . $f_name . " " . $l_name . "’s
                             QR code </a>                        
                        </p>
                        
                        
                        <h5>Regards,</h5>
                        <h5>Team ubiAttendance </h5>
                    </body>
                    </html>
                    ";
                        
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        // More headers
                        $headers .= 'From: <noreply@ubiattendance.com>' . "\r\n";
                        //$headers .= 'Cc: vijay@ubitechsolutions.com' . "\r\n";
                        $subject   = $f_name . " " . $l_name . " is registered on ubiAttendance.";
                        $adminMail = getAdminEmail($org_id);
                        //sendEmail_new($adminMail, $subject, $message, $headers);
                        //--sendEmail_new('vijay@ubitechsolutions.com', $subject, $message, $headers);
                        //--sendEmail_new('ubiattendance@ubitechsolutions.com', $subject, $message, $headers);
                        ///////////////////mail drafted to admin/
                        
                        
                        /*
                        
                        ///////////////////mail drafted to employee
                        $empmailid=$_REQUEST['username'];
                        if($empmailid!=''){ // trigger mail to employee
                        $message="<html>
                        <head>
                        <title>ubiAttendance</title>
                        </head>
                        <body>Dear ".$f_name." ".$l_name.",<br/>
                        <p>
                        Greetings from ubiAttendance Team!<br/><br/>
                        Congratulations!! You have been registered as an Employee of ".getOrgName($org_id)."<br/>
                        Kindly<a href='https://play.google.com/store/apps/details?id=org.ubitech.attendance'> Download ubiAttendance App</a> from Google Play Store. <br/><br/>
                        
                        Your Login Details:<br/>
                        Company Name: <b>".getOrgName($org_id)."</b><br/>
                        
                        Username(Phone#): <b>".$empmailid." or  ".$_REQUEST['contact']."</b><br/>
                        Password: ".$_REQUEST['password']."<br/><br/>                            
                        <br/>
                        <br/>
                        Any Questions? Please refer to our
                        <a href='http://www.ubitechsolutions.com/images/ubiAttendance User Guide (For Employees).pdf'> Employee Guide</a> for our online resources. Contact support@ubitechsolutions.com for any queries.
                        </p>
                        
                        
                        <br/>
                        
                        <h5>Cheers,</h5>
                        <h5>Team ubiAttendance </h5>
                        </body>
                        </html>
                        ";
                        
                        // Always set content-type when sending HTML email
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        // More headers
                        $headers .= 'From: <noreply@ubiattendance.com>' . "\r\n";
                        //$headers .= 'Cc: vijay@ubitechsolutions.com' . "\r\n";
                        $subject="Download the ubiAttendance App. .";
                        //    sendEmail_new($email,$subject,$message,$headers);
                        
                        sendEmail_new($empmailid,$subject,$message,$headers);
                        sendEmail_new('vijay@ubitechsolutions.com',$subject,$message,$headers);
                        
                        sendEmail_new('ubiattendance@ubitechsolutions.com',$subject,$message,$headers);
                        ///////////////////mail drafted to employee/
                        } // emp added by admn/
                        */
						
						$empMailId=$_REQUEST['username'];
						if($empMailId != ''){
						$message = "<html>
                              <head>
                              <title>ubiAttendance</title>
                              </head>
                              <body>Dear " . $f_name . " " . $l_name . ",<br/>
                              <p>
                              Congratulations!! You have been added to the Employees’ List of <b> " . getOrgName($org_id) . "</b>.
                              <br/><br/>
                              </b>Your Login Details are:<b/><br/><br/>
                              Username(Phone#): " . $_REQUEST['contact'] . "<br/>
                              Password: " . $_REQUEST['password'] . "<br/>
                              </b>
                              </p>
                              <p> 
                                  Get QR code by clicking <b> <a href='https://ubiattendance.ubihrm.com/index.php/services/useridcard/" . $org_id . "/" . $emp_id . "' target='_blank' >Generate QR Code</a></b>                        
                              </p>
                              <p>
                                  <a href='https://play.google.com/store/apps/details?id=org.ubitech.attendance'>Download</a> App on Google Play Store. You can refer to our <a href='http://www.ubitechsolutions.com/images/ubiAttendance%20User%20Guide%20(For%20Employees).pdf'>Get Started Guide</a> for quick onboarding. 
                              </p>
                              <h5>Regards,</br>
                              Team ubiAttendance </h5>
                          </body>
                          </html>
                          ";
                            // Always set content-type when sending HTML email
                            $headers = "MIME-Version: 1.0" . "\r\n";
                            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                            // More headers
                            $headers .= 'From: <noreply@ubiattendance.com>' . "\r\n";
							
                            //$headers .= 'Cc: vijay@ubitechsolutions.com' . "\r\n";
                            $subject = "You have registered on ubiAttendance.";
                            //    sendEmail_new($email,$subject,$message,$headers);
                            //sendEmail_new($empMailId, $subject, $message, $headers);
                            //sendEmail_new('abhinav@ubitechsolutions.com', $subject, $message, $headers);
						}
						
                    } else { // if emp get registered by himself
                        
                        ///////// mail drafted to admin
                        $message = "<html>
                        <head>
                        <title>ubiAttendance</title>
                        </head>
                        <body>Dear Admin,<br/>
                        <p>
                        Congratulations!! <b>" . $f_name . " " . $l_name . "</b> has been added to the Employees’ List of<b> " . getOrgName($org_id) . "</b>.
                        <br/><br/>
                        <b>
                        Employee: " . $f_name . " " . $l_name . " <br/>
                        Username(Phone#): " . $_REQUEST['contact'] . "<br/>    
                        Password: " . $_REQUEST['password'] . "<br/>                        
                        </b>
                        </p>
                        <p>
                            <a href='http://ubiattendance.ubihrm.com/index,php/services/useridcard/" . $org_id . "/" . $emp_id . "' target='_blank' >
                            Generate " . $f_name . " " . $l_name . "’s
                             QR code </a>                        
                        </p>
                        <h5>Regards,</h5>
                        <h5>Team ubiAttendance </h5>
                    </body>
                    </html>
                    ";
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        // More headers
                        $headers .= 'From: <noreply@ubiattendance.com>' . "\r\n";
                        //$headers .= 'Cc: vijay@ubitechsolutions.com' . "\r\n";
                        $subject   = $f_name . " " . $l_name . " is registered on ubiAttendance.";
                        //    sendEmail_new($email,$subject,$message,$headers);
                        $adminMail = getAdminEmail($org_id);
                        //sendEmail_new($adminMail, $subject, $message, $headers);
                        //--sendEmail_new('vijay@ubitechsolutions.com', $subject, $message, $headers);
                        //--sendEmail_new('ubiattendance@ubitechsolutions.com', $subject, $message, $headers);
                        ///////// mail drafted to admin/
                        
                        ///////// mail drafted to employee
                        $empmailid = $_REQUEST['username'];
                        if ($empmailid != '') { // trigger mail to employee
                            /*     $message="<html>
                            <head>
                            <title>ubiAttendance</title>
                            </head>
                            <body>Dear ".$f_name." ".$l_name.",<br/>
                            <p>
                            Congratulations!! You have been registered as an Employee of  ".getOrgName($org_id).".<br/><br/>
                            Kindly <a> Download ubiAttendance App from <a href='https://play.google.com/store/apps/details?id=org.ubitech.attendance'>Google Play Store</a>. 
                            <b>
                            <br/>
                            Login details:<br/>
                            
                            Username(Phone#): ".$empmailid." or  ".$_REQUEST['contact']."<br/>
                            Password: ".$_REQUEST['password']."<br/><br/>
                            
                            </b>     
                            <br/>
                            <br/>
                            
                            <h5>Cheers,</h5>
                            <h5>Team ubiAttendance </h5>
                            </body>
                            </html>
                            ";
                            */
                            $message = "<html>
                              <head>
                              <title>ubiAttendance</title>
                              </head>
                              <body>Dear " . $f_name . " " . $l_name . ",<br/>
                              <p>
                              Congratulations!! You have been added to the Employees’ List of <b> " . getOrgName($org_id) . "</b>.
                              <br/><br/>
                              </b>Your Login Details are:<b/><br/><br/>
                              Username(Phone#): " . $_REQUEST['contact'] . "<br/>
                              Password: " . $_REQUEST['password'] . "<br/>
                              </b>
                              </p>
                              <p> 
                                  Get QR code by clicking <b> <a href='https://ubiattendance.ubihrm.com/index.php/services/useridcard/" . $org_id . "/" . $emp_id . "' target='_blank' >Generate QR Code</a></b>                        
                              </p>
                              <p>
                                  <a href='https://play.google.com/store/apps/details?id=org.ubitech.attendance'>Download</a> App on Google Play Store. You can refer to our <a href='http://www.ubitechsolutions.com/images/ubiAttendance%20User%20Guide%20(For%20Employees).pdf'>Get Started Guide</a> for quick onboarding. 
                              </p>
                              <h5>Regards,</br>
                              Team ubiAttendance </h5>
                          </body>
                          </html>
                          ";
                            // Always set content-type when sending HTML email
                            $headers = "MIME-Version: 1.0" . "\r\n";
                            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                            // More headers
                            $headers .= 'From: <noreply@ubiattendance.com>' . "\r\n";
                            //$headers .= 'Cc: vijay@ubitechsolutions.com' . "\r\n";
                            $subject = "You have registered on ubiAttendance.";
                            //    sendEmail_new($email,$subject,$message,$headers);
                            //sendEmail_new($empmailid, $subject, $message, $headers);
                            //sendEmail_new('abhinav@ubitechsolutions.com', $subject, $message, $headers);
                            //--sendEmail_new('ubiattendance@ubitechsolutions.com', $subject, $message, $headers);
                            ///////// mail drafted to employee/
                        }
                        
                        
                    }
                } else {
                    $data['sts'] = 0;
                    $data['sts'] = 0;
                }
                //////////---------check users limit
                $query = $this->db->query("select count(id) as totalUsers,(select NoOfEmp from Organization where Organization.Id =$org_id) as ulimit,(select status from licence_ubiattendance where licence_ubiattendance.OrganizationId =$org_id) as orgstatus from UserMaster where OrganizationId = $org_id");
                if ($r = $query->result()) {
                    if ($r[0]->totalUsers >= $r[0]->ulimit) {
                       $range='1-20';
						if($r[0]->totalUsers<21)
							$range='1-20';
						else if($r[0]->totalUsers>=21 && $r[0]->totalUsers<41)
							$range='21-40';
						else if($r[0]->totalUsers>=41 && $r[0]->totalUsers<61)
							$range='41-60';
						else if($r[0]->totalUsers>=61 && $r[0]->totalUsers<81)
							$range='61-80';
						else if($r[0]->totalUsers>=81 && $r[0]->totalUsers<101)
							$range='81-100';
						else if($r[0]->totalUsers>=101 && $r[0]->totalUsers<121)
							$range='101-120';
						else
							$range='120+';
                        
                        
                        $sdate        = '-';
                        $edate        = '-';
                        $country      = 93;
                        $rate_per_day = 0;
                        $days         = 0;
                        $currency     = '';
                        $due          = 0;
						$orgstatus=$r[0]->orgstatus;
						
                        $query1       = $this->db->query("select start_date,end_date,due_amount,DATEDIFF(end_date,CURDATE())as days,(SELECT `Country` FROM `Organization` WHERE `Id` = $org_id)as country from licence_ubiattendance where OrganizationId  = $org_id");
                        if ($r1 = $query1->result()) {
                            $sdate    = $r1[0]->start_date;
                            $edate    = $r1[0]->end_date;
                            $days     = $r1[0]->days;
                            $due      = $r1[0]->due_amount;
                            $currency = $r1[0]->country == 93 ? 'INR' : 'USD';
                            $query2   = $this->db->query("SELECT  monthly  FROM `Attendance_plan_master` WHERE `range`='$range' and `currency`='$currency' ");
                            if ($r2 = $query2->result())
                                $rate_per_day = ($r2[0]->monthly) / 30;
                        }
                        
                        $payable_amt = 0;
                        $tax         = 0;
                        $total       = 0;
                        if ($currency == 'INR')
                            $tax = ($rate_per_day) * ($days) * (0.18);
							$payable_amt = $rate_per_day * $days;
							$payamtwidtax = round(($payable_amt+$tax),2);
							$total       = round(($due + $tax + $payable_amt),2);
                        
                        /////////////update due amount-start
                        $query1 = $this->db->query("UPDATE `licence_ubiattendance` SET `due_amount`=$total WHERE `OrganizationId` =$org_id");
                        /////////////update due amount-close
                    if($orgstatus==1){ 
						$subject = getOrgName($org_id)." -Billing details for changed users";
						$message = "<div style='color:black'>
					Greetings from ubiAttendance App<br/><br/>
					The no. of users in your ubiAttendance Plan have exceeded. We have updated your plan.  Below are the payment details for the additional Users. <br/>
					<h4 style='color:blue'>Plan Details:</h4>
					Company name: ".getOrgName($org_id)."<br/>
					Plan Start Date:".date('d-M-Y',strtotime($sdate))."<br/>
					Plan End Date:".date('d-M-Y',strtotime($edate))."<br/>
					User limit: ".$r[0]->ulimit."<br/>
					Registered Users: ".($r[0]->totalUsers)."<br/>
					<br/>
					<h4 style='color:blue'>Billing Details:</h4>
					Previous Dues: ".$due.' '.$currency." <br/>
					Amount Payable for additional Users: ".$payamtwidtax.' '.$currency."<br/>
					Amount Payable: ".$payamtwidtax." + ".$due." = ".$total." ".$currency." <br/>
					<br/>
					<a href='".URL."'>Update your plan now</a> so that there is no disruption in our services<br/><br/>";
					$message.="Cheers,<br/>
					Team ubiAttendance<br/><a target='_blank' href='http://www.ubiattendance.com'>www.ubiattendance.com</a><br/> Tel/ Whatsapp: +91 70678 22132<br/>Email: ubiattendance@ubitechsolutions.com<br/>Skype: ubitech.solutions</div>";
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        $headers .= 'From: <noreply@ubiattendance.com>' . "\r\n";
                        $adminMail = getAdminEmail($org_id);
                        //echo $message;
                       // sendEmail_new($adminMail, $subject, $message, $headers);
                   //--     sendEmail_new('vijay@ubitechsolutions.com', $subject, $message, $headers);
                    //--    sendEmail_new('ubiattendance@ubitechsolutions.com', $subject, $message, $headers);
                       //-- sendEmail_new('deeksha@ubitechsolutions.com', $subject, $message, $headers);
                    }
                    }
                }
                //////////---------check user's limit--close
            }
        }
        echo json_encode($data);
    }
    
    public function checkOrganization()
    {
		$data=array();
        $orgid = (int) isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $orgid = decode_vt5($orgid);
		$data['sts']=0;
        if (is_nan($orgid)) // org id is not a no. after decoding
            {
            $data['sts']=0;
        }else{
			$this->db->where('Id', $orgid);
			$query    = $this->db->get('Organization');
			$num_rows = $query->num_rows();
			if ($num_rows > 0) {
				$data['result'] = $query->result();
				$data['sts']=1;
			} else {
				$data['sts']=0;
			}
		}
		echo json_encode($data);
    }
    public function saveVisit(){
		$userid  = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
		$cid  = isset($_REQUEST['cid']) ? $_REQUEST['cid'] : 0;
        $client_name  = isset($_REQUEST['client']) ? $_REQUEST['client'] :'';
        $addr    = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $FakeLocationStatus   = isset($_REQUEST['FakeLocationStatus']) ? $_REQUEST['FakeLocationStatus'] : 0;
        
		//$zone    = getTimeZone($orgid);
		$zone    = getEmpTimeZone($userid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
		$stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $time   = date("H:i")=="00:00"?"23:59:00":date("H:i:s");
		$data=array();
		$data['msg']='Mark visit under process';
		$data['res']=0;
		$visitImage=getVisitImageStatus($orgid);
		
		//echo $visitImage;
	//	return false;
		//$client_name=$cid;//getClientName($cid);
		$new_name   ="https://ubitech.ubihrm.com/public/avatars/male.png";
		
		if($visitImage==1 || $visitImage=='1' ){ // true, image must be uploaded. false, optional image
	     
			 $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name)){
			Trace('image not uploaded--'.$userid);
			$result['status']=3;
			$result['errorMsg']='Error in moving the image. Try later.';
			$result['successMsg'] = '';
			echo json_encode($result);
			return;
			}	
			$new_name =IMGURL.$new_name;
			
				
		} // Go ahead if image is optional or image uploaded successfully
		
        
			
			$query=$this->db->query("update `checkin_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00'",array('Visit out not punched',$userid));
			
			
			$sql="INSERT INTO `checkin_master`(`FakeLocationStatusVisitIn`,`EmployeeId`, `location`, `latit`, `longi`, `time`, `date`, `client_name`, `ClientId`, `OrganizationId`, `checkin_img`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
			$query=$this->db->query($sql,array($FakeLocationStatus,$userid,$addr,$latit,$longi,$time,$date,$client_name,$cid,$orgid,$new_name));
			if($query>0){
				$data['res']='1';
				$data['msg']='Visit marked successfully.';
			}else{
				$data['res']='0';
				$data['msg']='Unable to mark visit, try later.';
			}
			echo json_encode($data);
		
	}
	
		public function saveVisitOut(){
		$visit_id  = isset($_REQUEST['visit_id']) ? $_REQUEST['visit_id'] : 0;
		$remark  = isset($_REQUEST['remark']) ? $_REQUEST['remark'] : 0;
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $addr   = isset($_REQUEST['addr']) ? $_REQUEST['addr'] : '0.0';
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : '0.0';
        $empid   = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : '0.0';
        //$zone    = getTimeZone($orgid);
        $zone    = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        $FakeLocationStatus   = isset($_REQUEST['FakeLocationStatus']) ? $_REQUEST['FakeLocationStatus'] : 0;
       
        date_default_timezone_set($zone);
		$stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $time   = date("H:i")=="00:00"?"23:59:00":date("H:i:s");
		$data=array();
		$data['msg']='Mark visit out under process';
		$data['res']=0;
		$new_name   = "https://ubitech.ubihrm.com/public/avatars/male.png";
		$visitImage=0;
		$visitImage=getVisitImageStatus( $orgid);
		if($visitImage){ // true, image must be uploaded. false, optional image
		    $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name)){
			Trace('image not uploaded--'.$userid);
			$result['status']=3;
			$result['errorMsg']='Error in moving the image. Try later.';
			$result['successMsg'] = '';
			echo json_encode($result);
			return;
			}	
			$new_name =IMGURL.$new_name;			
		} // Go ahead if image is optional or image uploaded successfully
	
        
			
			
			$query=$this->db->query("update `checkin_master` set FakeLocationStatusVisitOut=?, description=?, `location_out`=? ,`latit_out`=?,`longi_out`=?, `time_out`=?,`checkout_img`=? where Id=?",array($FakeLocationStatus,$remark,$addr,$latit,$longi,$time,$new_name,$visit_id));
			if($query>0){
				$data['res']='1';
				$data['msg']='Visit punched successfully.';
			}else{
				$data['res']='0';
				$data['msg']='Unable to punch visit. Try later.';
			}
		
		echo json_encode($data);
	}
	
    public function getPunchInfo()
    { 
		$uid=isset($_REQUEST['uid'])?$_REQUEST['uid']:0;
		$orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:0;
		//$zone=getTimeZone($orgid);
		$zone    = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
		
		date_default_timezone_set($zone);	
		$loginemp = isset($_REQUEST['loginemp'])?$_REQUEST['loginemp']:0;
		$adminstatus = getAdminStatus($loginemp);
		$cond = "";
		
		if($adminstatus == '2')
		{ 
	     	$dptid = getDepartmentIdByEmpID($loginemp);
			$cond = " AND Department = $dptid  ";
		}
		
		$today=isset($_REQUEST['date'])?$_REQUEST['date']:date('Y-m-d');
		$today=date('Y-m-d',strtotime($today));
		if($uid!=0){
			$query = $this->db->query("SELECT Id,EmployeeId,`location`,location_out,`time`,`time_out`,checkin_img,checkout_img, `client_name`, `description`,`latit`, `longi`, `latit_out`, `longi_out` FROM `checkin_master` WHERE EmployeeId=? and date=? AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0  ) order by time,Id",array($uid,$today));	
		}else{
			
			$query = $this->db->query("SELECT Id, EmployeeId,`location`,location_out,`time`,`time_out`,checkin_img,checkout_img, `client_name`, `description`,`latit`, `longi`, `latit_out`, `longi_out` FROM `checkin_master` WHERE OrganizationId=? and date=? AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0  $cond ) order by EmployeeId ",array($orgid,$today));	
		}
		$res=array();
		foreach ($query->result() as $row){
			$data=array();
			$data['Id']=$row->Id;
            $data['emp']=getEmpName($row->EmployeeId);
            $data['empId']=$row->EmployeeId;
			$data['loc_in']=$row->location;
			$data['loc_out']=$row->location_out;
			$data['time_in']=date('H:i',strtotime($row->time));
			$data['time_out']=date('H:i',strtotime($row->time_out));
			$data['latit']=$row->latit;
			$data['longi']=$row->longi;
			$data['longi_out']=$row->longi_out;
			$data['latit_in']=$row->latit_out;
			$data['client']=$row->client_name;
			$data['desc']=$row->description;
			$data['checkin_img']=$row->checkin_img;
			$data['checkout_img']=$row->checkout_img;
			$data['description']=$row->description;
			$res[]=$data;
		}
		echo json_encode($res);
		
		/*
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $today      = date('Y-m-d');
        $query      = $this->db->query("SELECT Id FROM `checkin_master` WHERE `EmployeeId`=? and `OrganizationId`=? and `time_out`='00:00:00' and date=? order by id desc limit 1 ", array(
            $uid,
            $orgid,
            $today
        ));
        $data       = array();
        $data['id'] = 0;
        if ($row = $query->result())
            $data['id'] = $row[0]->Id;
        echo json_encode($data);
		*/
    }
    
    public function getPunchedLocations()
    {
        $userid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $date   = isset($_REQUEST['cdate']) ? $_REQUEST['cdate'] : 0;
        
        $query = $this->db->query("SELECT  `location`,location_out ,`time`,`time_out`, `client_name`, `description` FROM `checkin_master` WHERE EmployeeId=? order by id desc", array(
            $userid
        ));
		//echo "SELECT  `location`,location_out ,`time`,`time_out`, `client_name`, `description` FROM `checkin_master` WHERE EmployeeId=$userid' order by id desc";
        echo json_encode($query->result());
    }
    public function saveImageOffline()
    {
        $userid  = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $addr    = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $aid     = isset($_REQUEST['aid']) ? $_REQUEST['aid'] : 0;
        $act     = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'TimeIn';
        $shiftId = isset($_REQUEST['shiftid']) ? $_REQUEST['shiftid'] : 0;
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $offlineTime   = isset($_REQUEST['offlineTime']) ? $_REQUEST['offlineTime'] : '0';
        $offlineDate   = isset($_REQUEST['offlineDate']) ? $_REQUEST['offlineDate'] : '0';

		$dept=getDepartmentIdByEmpID($userid);
		$desg=getDesignationIdByEmpID($userid);
		$hourltRate=getHourlyRateIdByEmpID($userid);
		
		if($shiftId==0)
			$shiftId=getShiftIdByEmpID($userid);
        ////////---------------checking and marking "timeOff" stop (if exist)
        if($userid!=0)
        	$zone  = getEmpTimeZone($userid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $today   = date("Y-m-d");
        
        $time   = date("H:i")=="00:00"?"23:59":date("H:i");       
        if($offlineTime!='0'){
            $time= $offlineTime=="00:00"?"23:59":$offlineTime;
            $date=$offlineDate; 
        }
        //AutoTimeOffEnd($userid, $orgid, $time, $date, $stamp, $addr, $latit, $longi); // auto timeOff end
        //AutoVisitOutEnd($userid, $orgid, $time, $addr, $latit, $longi);
        /////////// This query is from auto visit out/////////////
        //$query=$this->db->query("update `checkin_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00'",array('Auto Visit Out Punched',$userid));
        /////////// This query is from auto visit out/////////////
        //echo $time;
		
		
        $today  = date('Y-m-d');
       
        ////////---------------checking and marking "timeOff" stop (if exist)--/end
        $count      = 0;
        $errorMsg   = "";
        $successMsg = "";
        $status     = 0;
        $resCode    = 0;
        $serversts  = 1;
		$sto='00:00:00';
		$sti='00:00:00';
		$shifttype='';
		$data=array();
		$data['msg']='Mark visit under process';
		$data['res']=0;
		$attImage=0;
		$new_name   = "https://ubitech.ubihrm.com/public/avatars/male.png";
		$attImage=getAttImageStatus($orgid);
		if($attImage){ // true, image must be uploaded. false, optional image
			 $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name)){
			Trace('image not uploaded--'.$userid);
			$result['status']=3;
			$result['errorMsg']='Error in moving the image. Try later.';
			$result['successMsg'] = '';
			echo json_encode($result);
			return;
			}	
			$new_name =IMGURL.$new_name;
		} // Go ahead if image is optional or image uploaded successfully
		
		
     //   $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
    /*    if (move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name))
        
        //if(true)
            {*/
            $sql = '';
			//////----------------getting shift info
            $stype = 0;
            $sql1  = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;
			
            try {
                $result1 = $this->db->query($sql1);
                if ($row1 = $result1->row()) {
                    $stype = $row1->stype;
					$sti=$row1->TimeIn;
					$sto=$row1->TimeOut;
					$shifttype=$row1->shifttype;
                }
            }
            catch (Exception $e) {
                Trace('Error_3: ' . $e->getMessage());
            }
			if($shifttype==2 && $act=='TimeIn'){ // multi date shift case
				if($time<$sto){ // time in should mark in last day date
					try{
						$ldate   = date("Y-m-d",strtotime("-1 days"));
						$sql="select Id as ttl from AttendanceMaster where AttendanceDate='$ldate' and EmployeeId=$userid";
						$res=$this->db->query($sql);
						if($res->num_rows() > 0){// if attn already marked in previous date
							$date   = date("Y-m-d");
						}
						else
							$date   = date("Y-m-d",strtotime("-1 days"));
							
					}catch(Exception $e){
						
					}
				}
				//else  time in should mark in current day's date
			}
			
		//	echo $date;
		//	return false;
			
            //////----------------/gettign shift info
            Trace($act.' AID'.$aid.'UserId'.$userid);
            if($aid==0 && $act=='TimeOut'){
            	$sqlId = "select Id from  AttendanceMaster where EmployeeId=$userid and TimeOut='00:00:00' Order by AttendanceDate desc Limit 1";
            	$resId=$this->db->query($sqlId);
            	 if ($rowId = $resId->row()) {
                    $aid = $rowId->Id;
                }
                Trace('After Fetch: '.$act.' AID'.$aid.'UserId'.$userid);
            }
            
            if ($aid != 0 && $act!='TimeIn') //////////////updating path of employee profile picture in database/////////////
                {
                
                if ($stype < 0) //// if shift is end whthin same date
                    $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp',overtime =(SELECT subtime(subtime('$time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$date'
                WHERE id=$aid and `EmployeeId`=$userid  and date(AttendanceDate) = '$date' and TimeOut='00:00:00'"; //and SUBTIME(  `TimeOut` ,  `TimeIn` ) >'00:05:00'";
                else{
					//////getting timein information
					$sql="select Timein as timein_time, Attendancedate as timein_date from AttendanceMaster where Id=".$aid;
					$timein_date='';
					$timein_time='';
					$res=$this->db->query($sql);
					if($r= $res->result()){
							$timein_date=$r[0]->timein_date;
							$timein_time=$r[0]->timein_time;
					}
					//////getting timein information/
				/*	echo $timein_date.' '.$timein_time;
					echo '---';
					echo $date.' '.$time;
					echo '***';
					*/
					// shift hours
					$shiftHours='';
					$sql="select subtime('24:00:00',subtime(timein,timeout)) as shiftHours from ShiftMaster where Id=$shiftId";
					//$sql="select subtime('30:00:00','21:00:00') as shiftHours from ShiftMaster where id=$shiftId";
					$res=$this->db->query($sql);
					if($r= $res->result())
						$shiftHours=$r[0]->shiftHours;
					
					// time spent
			//		echo $timein_date.' '.$timein_time.'-------';
			//		echo $date.' '.$time.'-------';
					$start = date_create($timein_date.' '.$timein_time);
					$end = date_create($date.' '.$time);
					$diff=date_diff($end,$start);
					$hrs=0;
					if($diff->d==1)// if shift is running more than 24 hrs
						$hrs=24;
					$timeSpent=str_pad($hrs+ $diff->h, 2, "0", STR_PAD_LEFT).':'.str_pad($diff->i, 2, "0", STR_PAD_LEFT).':00';
					
					//echo 'TimeSpent:'.$timeSpent;
					//echo 'shiftHours:'.$shiftHours;
                    $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp' ,overtime = subtime('$timeSpent','$shiftHours'),timeoutdate='$date'
                WHERE id=$aid and `EmployeeId`=$userid and TimeOut='00:00:00' ORDER BY `AttendanceDate` DESC LIMIT 1";
                //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
				}
                 /*   $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp' ,overtime =(SELECT subtime(subtime('$time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$date'
                WHERE id=$aid and `EmployeeId`=$userid  ORDER BY `AttendanceDate` DESC LIMIT 1";*/
                //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
                //----------push check code
                try {
                    $push = "push/";
                    if (!file_exists($push))
                        mkdir($push, 0777, true);
                    $filename = $push . $orgid . ".log";
                    $fp       = fopen($filename, "a+");
                    fclose($fp);
                }
                catch (Exception $e) {
                    echo $e->getMessage();
                }
                //----------push check code
            } //LastModifiedDate
            else{
                ///-------- code for prevent duplicacy in a same day   code-001
                $sql = "select * from  AttendanceMaster where EmployeeId=$userid and AttendanceDate= '$today'";
                
                try {
                    $result1 = $this->db->query($sql);
                    if ($this->db->affected_rows() < 1) { ///////code-001 (ends)
                        $area = getAreaId($userid);
					   if($orgid=='10932'){      // only for welspun
                        	$area = getNearLocationOfEmp($latit, $longi,$userid);
                        }
                        $sql  = "INSERT INTO `AttendanceMaster`(`EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`,`ShiftId`,Dept_id,Desg_id,areaId,HourlyRateId, `OrganizationId`,
      `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `Overtime`, `EntryImage`, `checkInLoc`,`device`,latit_in,longi_in,timeindate)
      VALUES ($userid,'$date',1,'$time',$shiftId,$dept,$desg,$area,$hourltRate,$orgid,'$date',$userid,'$stamp',$userid,$userid,'00:00:00','" . $new_name . "','$addr','mobile','$latit','$longi','$today')";
      					Trace('User Attendance: '.$userid.' '.$sql);
                    } else
                        $sql = '';
                }
                catch (Exception $e) {
                    Trace('Error_2: ' . $e->getMessage());
                    $errorMsg = 'Message: ' . $e->getMessage();
                    $status   = 0;
                }
                
                
            }
            
            try {
                $query = $this->db->query($sql);
                if ($this->db->affected_rows() > 0 && $act == 'TimeIn') {
                    //----------push check code
                    try {
                        $push = "push/";
                        if (!file_exists($push))
                            mkdir($push, 0777, true);
                        $filename = $push . $orgid . ".log";
                        $fp       = fopen($filename, "a+");
                        fclose($fp);
                    }
                    catch (Exception $e) {
                        echo $e->getMessage();
                    }
                    //----------push check code
                    
                    
                    $resCode    = 0;
                    $status     = 1; // update successfully
                    $successMsg = "Image uploaded successfully.";
                    //////////////////----------------mail send if attndnce is marked very first time in org ever
                    $sql        = "SELECT  `Email`  FROM `Organization` WHERE `Id`=" . $orgid;
                    $to         = '';
                    $query1     = $this->db->query($sql);
                    if ($row = $query1->result()) {
                        $to = $row[0]->Email;
                    }
                    
                    //////////////////----------------/mail send if attndnce is marked very first time in org ever
                } else {
                    $status = 2; // no changes found
                    $errorMsg .= "Failed to upload Image./No Check In found today.";
                }
            }
            catch (Exception $e) {
                Trace('Error_1: ' . $e->getMessage());
                $errorMsg = 'Message: ' . $e->getMessage();
                $status   = 0;
            }
      /*  } else {
            Trace('image not uploaded--');
            $status   = 3; // error in uploading image
            $errorMsg = 'Message: error in uploading image';
        }*/
        $result['status']     = $status;
        $result['successMsg'] = $successMsg;
        $result['errorMsg']   = $errorMsg;
        //$result['location']=$addr;
        
        echo json_encode($result);
    }
    
    public function saveImageSandboxOld()
    {
        $userid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $addr = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $aid = isset($_REQUEST['aid']) ? $_REQUEST['aid'] : 0;
        $act = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'TimeIn';
        $shiftId = isset($_REQUEST['shiftid']) ? $_REQUEST['shiftid'] : 0;
        $orgid = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $latit = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        $FakeLocationStatus = isset($_REQUEST['FakeLocationStatus']) ? $_REQUEST['FakeLocationStatus'] : 0;
        $city   = isset($_REQUEST['city']) ? $_REQUEST['city'] : '';
        //$name   = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'User';
        $orgTopic   = isset($_REQUEST['globalOrgTopic']) ? $_REQUEST['globalOrgTopic'] : '';
        $name=getEmpName($userid);
        $FakeLocationStatusTimeIn = 0;
        $FakeLocationStatusTimeOut = 0;
        $deviceidmobile  = isset($_REQUEST['deviceidmobile']) ? $_REQUEST['deviceidmobile'] : '';
        $devicenamebrand  = isset($_REQUEST['devicenamebrand']) ? $_REQUEST['devicenamebrand'] : '';
        $verifieddevice='';
        $faceid = "";
        //$personid='bf159541-1e75-4cbd-acca-bcb8f4e07b3a';
        $persongroup_id = "";
        $suspicioustimein_status = "0";
        $suspicioustimeout_status = "0";
        $timein_confidence = "";
        $timeout_confidence = "";
        $profileimage="";
        $suspiciousdevice=0;
        $lateby = '00:00:00';
        $earlyby = '00:00:00';
        $earlyleaving = '00:00:00';
        $lateleaving = '00:00:00';
        $cond = "";
        $geofencePerm=getNotificationPermission($orgid,'OutsideGeofence');
        $SuspiciousSelfiePerm=getNotificationPermission($orgid,'SuspiciousSelfie');
        $SuspiciousDevicePerm=getNotificationPermission($orgid,'SuspiciousDevice');
        $zone = getEmpTimeZone($userid, $orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $stamp = date("Y-m-d H:i:s");
        $date = date("Y-m-d");
        $today = date("Y-m-d");
        $currDate=date("Y-m-d");
        $time = date("H:i") == "00:00" ? "23:59" : date("H:i");
        /*
        if(autoTimeOutReached($userid,$act)){
        	$data['error']="AutoTimeOutReached";

        	echo json_encode($data);
        	return;
        }
*/
        $sql= "select Addon_DeviceVerification from licence_ubiattendance where OrganizationId = $orgid";
		$query=$this->db->query($sql);
		if ($row = $query->row()) {
                     $deviceverificationperm = $row->Addon_DeviceVerification;
                      
                     }

         if($deviceverificationperm==1){            

		$sql= "select DeviceId from EmployeeMaster where Id = $userid";
		$query=$this->db->query($sql);
		 if ($row = $query->row()) {
                     $verifieddevice = $row->DeviceId;
                      if($verifieddevice==$deviceidmobile)
                 {
                 	$suspiciousdevice=0;
                 }
                 else
                 {
                    $suspiciousdevice=1;
                   
                    if($SuspiciousDevicePerm==9|| $SuspiciousDevicePerm==13||$SuspiciousDevicePerm==11|| $SuspiciousDevicePerm==15){
             	sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name\'s Attendance Device does not match", "");
             }
              if($SuspiciousDevicePerm==5 || $SuspiciousDevicePerm==13|| $SuspiciousDevicePerm==7|| $SuspiciousDevicePerm==15){
              	$query= $this->db->query("Select email from admin_login where OrganizationId=$orgid");
		foreach($query->result() as $row){
         $email= $row->email;


             $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$name.' Attendance Device is different from their registered Device ID
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "Suspicious Device(".$date.")";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   //sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
               }
               }
                 }
                     
                  
                 }
             }
             if($geofence=="Outside Geofence"){
             	if($geofencePerm==9|| $geofencePerm==13||$geofencePerm==11|| $geofencePerm==15){
             	sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name has punched Attendance outside Geofence", "");
             }
              if($geofencePerm==5 || $geofencePerm==13||$geofencePerm==7 || $geofencePerm==15){
              	$query= $this->db->query("Select email from admin_login where OrganizationId=$orgid");
		foreach($query->result() as $row){
         $email= $row->email;

             $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$name.' has punched Attendance outside Geofence
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "Outside Geofence(".$date.")";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   //sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
               }
               }
             }
	
        $reportNotificationSent = 0;
	
        $deviceverificationperm=0;
        $flag = '0';
        if ($act == 'TimeIn')
        {
            $FakeLocationStatusTimeIn = $FakeLocationStatus;
        }
        else
        {
            $FakeLocationStatusTimeOut = $FakeLocationStatus;
        }
        $dept = getDepartmentIdByEmpID($userid);
        $desg = getDesignationIdByEmpID($userid);
        $hourltRate = getHourlyRateIdByEmpID($userid);

        $reportNotificationSent = 0;
        $personid = "";
        $persistedfaceid = "0";

        
        $sql7 = "select PersonGroupId from licence_ubiattendance where OrganizationId = $orgid and Addon_FaceRecognition='1'";
        $query7 = $this
            ->db
            ->query($sql7);
        if ($row7 = $query7->row())
        {
            $persongroup_id = $row7->PersonGroupId;
            $flag = '1';

        }

        if ($flag == '1')
        {
            $sql4 = "select PersonId,FirstName,LastName from  EmployeeMaster where Id=$userid";
            $query4 = $this
                ->db
                ->query($sql4);
            if ($row4 = $query4->row())
            {
                $personid = $row4->PersonId;
                $firstname = $row4->FirstName;
                //$lastname = $row4->LastName;
                
            }

            if ($personid == "")
            {
                $personid = create_person($persongroup_id, $firstname);
                $sql5 = "update EmployeeMaster set PersonId = '$personid' where Id=$userid";
                $query5 = $this
                    ->db
                    ->query($sql5);

            }
        }

        if ($shiftId == 0) $shiftId = getShiftIdByEmpID($userid);
        
        // $zone    = getTimeZone($orgid);
        
        //echo $time;
        ////////---------------checking and marking "timeOff" stop (if exist)
        AutoTimeOffEnd($userid, $orgid, $time, $date, $stamp, $addr, $latit, $longi); // auto timeOff end
        //AutoVisitOutEnd($userid, $orgid, $time, $addr, $latit, $longi);
        /////////// This query is from auto visit out/////////////
        $query=$this->db->query("update `checkin_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00'",array('Visit out not punched',$userid));
        /////////// This query is from auto visit out/////////////
        $today = date('Y-m-d');

        ////////---------------checking and marking "timeOff" stop (if exist)--/end
        $count = 0;
        $orgname = "";
        $orgnameForNoti = "";
        $errorMsg = "";
        $successMsg = "";
        $status = 0;
        $resCode = 0;
        $serversts = 1;
        $sto = '00:00:00';
        $sti = '00:00:00';
        $shifttype = '';
        $data = array();
        $data['msg'] = 'Mark visit under process';
        $data['res'] = 0;
        $attImage = 0;
        $interval= '00:00:00';
        $lateby = '00:00:00';
        $earlyby = '00:00:00';
        $earlyleaving = '00:00:00';
        $lateleaving = '00:00:00';
        $cond="";
        $new_name = "https://ubitech.ubihrm.com/public/avatars/male.png";
        $attImage = getAttImageStatus($orgid);
        $img123    = isset($_FILES['file']) ? true : false;




		//$tempimagestatus =isset($_REQUEST['tempimagestatus'])?false:true;
//		 if($attImage){ // true, image must be uploaded. false, optional image
//			 $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
//			if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name)){
//			Trace('image not uploaded--'.$userid);
//			$result['status']=3;
//			$result['errorMsg']='Error in moving the image. Try later.';
//			$result['successMsg'] = '';
//			echo json_encode($result);
//			return;
//			}
//			$new_name =IMGURL.$new_name;
//		}
        /*
        if ($attImage)
        {
            $new_name = $userid . '_' . date('dmY_His') . ".jpg";
            if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name))
            {
                Trace('image not uploaded--' . $userid);
                $result['status'] = 3;
                $result['errorMsg'] = 'Error in moving the image. Try later.';
                $result['successMsg'] = '';
                echo json_encode($result);
                return;
            }
            $new_name = IMGURL . $new_name;
        }
        */
        // Go ahead if image is optional or image uploaded successfully
        

        //   $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
        /*    if (move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name))
        
        //if(true)
            {*/
        $sql = '';
        //////----------------getting shift info
        $stype = 0;
        $sql1 = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;

        try
        {
            $result1 = $this
                ->db
                ->query($sql1);
            if ($row1 = $result1->row())
            {
                $stype = $row1->stype;
                $sti = $row1->TimeIn;
                $sto = $row1->TimeOut;
                $shifttype = $row1->shifttype;
            }
        }
        catch(Exception $e)
        {
            Trace('Error_3: ' . $e->getMessage());
        }



        if ($shifttype == 2 && $act == 'TimeIn')
        { // multi date shift case
            if ($time < $sto)
            { // time in should mark in last day date
                try
                {
                    $ldate = date("Y-m-d", strtotime("-1 days"));
                    $sql = "select Id as ttl from AttendanceMaster where AttendanceDate='$ldate' and EmployeeId=$userid";
                    $res = $this
                        ->db
                        ->query($sql);
                    if ($res->num_rows() > 0)
                    { // if attn already marked in previous date
                        $date = date("Y-m-d");
                    }
                    else $date = date("Y-m-d", strtotime("-1 days"));

                }
                catch(Exception $e)
                {

                }
            }
            //else  time in should mark in current day's date
            
        }
        else if ($shifttype == 2 && $act == 'TimeOut')
        {
            if ($time < $sti)
            { // time in should mark in last day date
                try
                {

                    $date = date("Y-m-d", strtotime("-1 days"));
                }
                catch(Exception $e)
                {

                }
            }
        }

        //	echo $date;
        //	return false;
        //////----------------/gettign shift info
        Trace($act . ' AID' . $aid . 'UserId' . $userid);
        if ($aid == 0 && $act == 'TimeOut')
        {
            $sqlId = "select Id from  AttendanceMaster where EmployeeId=$userid and (TimeOut='00:00:00' OR TimeOut=TimeIn  Order by AttendanceDate desc Limit 1";
            $resId = $this
                ->db
                ->query($sqlId);
            if ($rowId = $resId->row())
            {
                $aid = $rowId->Id;
            }
            Trace('After Fetch: ' . $act . ' AID' . $aid . 'UserId' . $userid);
        }
        /*********
        EmployeeMaster
        ***********/



        if ($aid != 0 && $act != 'TimeIn') //////////////updating path of employee profile picture in database/////////////
        
        {

            if ($stype < 0)
            { //// if shift is end whthin same date
                /*face recognition code starts here */
                if ($flag == '1')
                {
                    $sql6 = "select PersistedFaceId from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                    if ($row6 = $query6->row())
                    {

                        if ($row6->PersistedFaceId == '0')
                        {
                            $fid = getfaceid($new_name);
                            if ($fid == '0')
                            {
                                $result['facerecog'] = '5';
                                $this
                                    ->db
                                    ->close();
                                echo json_encode($result);
                                return;

                            }
                            $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                            $sql6 = "update Persisted_Face set PersistedFaceId = '$persistedfaceid',profileimage='$new_name' where EmployeeId=$userid";
                            $query6 = $this
                                ->db
                                ->query($sql6);
                            persongrouptrain($persongroup_id);

                        }
                        //face id will be generated here
                        $faceid = getfaceid($new_name);
                        if ($faceid == '0')
                        {
                            $suspicioustimeout_status = '1';
                        }
                        else
                        {
                            //face verification will take place over here
                            $timeout_confidence = face_verify($faceid, $personid, $persongroup_id);
                            if ($timeout_confidence < '0.75') $suspicioustimeout_status = '1';
                        }
                    }
                    else
                    { //face will be added here
                        $fid = getfaceid($new_name);
                        if ($fid == '0')
                        {
                            $result['facerecog'] = '5';
                            $this
                                ->db
                                ->close();
                            echo json_encode($result);
                            return;

                        }
                        $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                        $sql6 = "insert into Persisted_Face(PersonId,	PersistedFaceId,EmployeeId,profileimage) values ('$personid','$persistedfaceid',$userid,'$new_name')";
                        $query6 = $this
                            ->db
                            ->query($sql6);
                        $faceid = $persistedfaceid;
                        persongrouptrain($persongroup_id);
                    }
                     $sql6 = "select profileimage from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                        if ($row6 = $query6->row())
                    {
                        $profileimage=$row6->profileimage;
                    }
                }
                /*face recognition code ends here */

                // $time = date("H:i") == "00:00" ? "23:59" : date("H:i");

           
              $query56478  = $this->db->query("SELECT TimeDiff(TimeOut,'$time')as timediff FROM ShiftMaster where Id= '$shiftId'");
                   
               
                 //var_dump($this->db->last_query());
               

                    if ($row56478=$query56478->row()) {
                  
                    	$cond = $row56478->timediff;
                    	//var_dump($cond);

                  if($row56478->timediff  < '00:00:00'){
                        $earlyleaving=$cond;
                  }
                  else{

                  	$lateleaving=$cond;
                  }
  
                    }
                    else{
                    $earlyleaving= '00:00:00';
                    $lateleaving='00:00:00';
                    }

// for same date timeout 
                $sql = "UPDATE `AttendanceMaster` SET `lateleaving`= '$lateleaving',`earlyleaving`='$earlyleaving', `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time',`SuspiciousDeviceTimeOutStatus`='$suspiciousdevice',`TimeOutDeviceName`='$devicenamebrand',`TimeOutDeviceId`='$deviceidmobile',TimeOutFaceId='$faceid',TimeOutConfidence='$timeout_confidence',SuspiciousTimeOutStatus='$suspicioustimeout_status',timeoutcity='$city',PersistedFaceTimeOut='$profileimage', LastModifiedDate='$stamp',overtime =(SELECT subtime(TIMEDIFF ( CONCAT('$date', ' ','$time'),CONCAT(AttendanceDate , '  ', timein)),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$currDate' WHERE id=$aid and `EmployeeId`=$userid   and (TimeOut='00:00:00' OR TimeOut=TimeIn)"; //and SUBTIME(  `TimeOut` ,  `TimeIn` ) >'00:05:00'";
                if($suspicioustimeout_status=='1'){
                	
                	if($SuspiciousSelfiePerm==9|| $SuspiciousSelfiePerm==13||$SuspiciousSelfiePerm==11|| $SuspiciousSelfiePerm==15){
             	sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name\'s Attendance Selfie does not match", "");
             }
              if($SuspiciousSelfiePerm==5 || $SuspiciousSelfiePerm==13|| $SuspiciousSelfiePerm==7|| $SuspiciousSelfiePerm==15){
              	$query= $this->db->query("Select email from admin_login where OrganizationId=$orgid");
		foreach($query->result() as $row){
         $email= $row->email;

             $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$name.' Attendance Selfie does not match with the Face ID
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "Suspicious Selfie(".$date.")";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                    //sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
                }
                
            }
            else
            {
                //////getting timein information
                $sql = "select Timein as timein_time, Attendancedate as timein_date from AttendanceMaster where Id=" . $aid;
                $timein_date = '';
                $timein_time = '';
                $res = $this
                    ->db
                    ->query($sql);
                if ($r = $res->result())
                {
                    $timein_date = $r[0]->timein_date;
                    $timein_time = $r[0]->timein_time;
                }
                //////getting timein information/
                /*	echo $timein_date.' '.$timein_time;
                echo '---';
                echo $date.' '.$time;
                echo '***';
                */
                // shift hours
                $shiftHours = '';
                $sql = "select subtime('24:00:00',subtime(timein,timeout)) as shiftHours from ShiftMaster where Id=$shiftId";
                //$sql="select subtime('30:00:00','21:00:00') as shiftHours from ShiftMaster where id=$shiftId";
                $res = $this
                    ->db
                    ->query($sql);
                if ($r = $res->result()) $shiftHours = $r[0]->shiftHours;

                // time spent
                //		echo $timein_date.' '.$timein_time.'-------';
                //		echo $date.' '.$time.'-------';
                $start = date_create($timein_date . ' ' . $timein_time);
                $end = date_create($date . ' ' . $time);
                $diff = date_diff($end, $start);
                $hrs = 0;
                if ($diff->d == 1) // if shift is running more than 24 hrs
                $hrs = 24;
                $timeSpent = str_pad($hrs + $diff->h, 2, "0", STR_PAD_LEFT) . ':' . str_pad($diff->i, 2, "0", STR_PAD_LEFT) . ':00';

                //echo 'TimeSpent:'.$timeSpent;
                //echo 'shiftHours:'.$shiftHours;
                /*face recognition code starts here */
                if ($flag == '1')
                {
                    $sql6 = "select PersistedFaceId from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                    if ($row6 = $query6->row())
                    {

                        if ($row6->PersistedFaceId == '0')
                        {
                            $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                            $sql6 = "update Persisted_Face set PersistedFaceId = '$persistedfaceid',profileimage='$new_name' where EmployeeId=$userid";
                            $query6 = $this
                                ->db
                                ->query($sql6);
                            persongrouptrain($persongroup_id);

                        }
                        //face id will be generated here
                        $faceid = getfaceid($new_name);
                        if ($faceid == '0')
                        {
                            $suspicioustimeout_status = '1';
                        }
                        else
                        {
                            //face verification will take place over here
                            $timeout_confidence = face_verify($faceid, $personid, $persongroup_id);
                            if ($timeout_confidence < '0.75') $suspicioustimeout_status = '1';
                        }
                    }
                    else
                    { //face will be added here
                        $fid = getfaceid($new_name);
                        if ($fid == '0')
                        {
                            $result['facerecog'] = '5';
                            $this
                                ->db
                                ->close();
                            echo json_encode($result);
                            return;

                        }
                        $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                        $sql6 = "insert into Persisted_Face(PersonId,	PersistedFaceId,EmployeeId,profileimage) values ('$personid','$persistedfaceid',$userid,'$new_name')";
                        $query6 = $this
                            ->db
                            ->query($sql6);
                        $faceid = $persistedfaceid;
                        persongrouptrain($persongroup_id);
                    }
                    $sql6 = "select profileimage from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                        if ($row6 = $query6->row())
                    {
                        $profileimage=$row6->profileimage;
                    }
                }
                /*face recognition code ends here */

                  $time = date("H:i") == "00:00" ? "23:59" : date("H:i");

              $query5647  = $this->db->query("SELECT TimeDiff(TimeOut,'$time')as timediff FROM ShiftMaster where Id= '$shiftId'");
                   
               
                 // var_dump($this->db->last_query());
               

                    if ($row5647=$query5647->row()) {
                  
                    	$cond = $row5647->timediff;

                  if($row5647->timediff  < '00:00:00'){
                        $earlyleaving=$cond;
                  }
                  else{

                  	$lateleaving=$cond;
                  }
  
                    }
                    else{
                    $earlyleaving= '00:00:00';
                    $lateleaving='00:00:00';
                    }

                $sql = "UPDATE `AttendanceMaster` SET  `lateleaving`= '$lateleaving',`earlyleaving`=$earlyleaving, `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time',`SuspiciousDeviceTimeOutStatus`='$suspiciousdevice',`TimeOutDeviceName`='$devicenamebrand',`TimeOutDeviceId`='$deviceidmobile',TimeOutFaceId='$faceid',timeoutcity='$city',TimeOutConfidence='$timeout_confidence',SuspiciousTimeOutStatus='$suspicioustimeout_status',PersistedFaceTimeOut='$profileimage', LastModifiedDate='$stamp' ,overtime = subtime('$timeSpent','$shiftHours'),timeoutdate='$currDate'
                WHERE id=$aid and `EmployeeId`=$userid and (TimeOut='00:00:00') ORDER BY `AttendanceDate` DESC LIMIT 1";
                //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
                if($suspicioustimeout_status=='1'){
                	if($SuspiciousSelfiePerm==9|| $SuspiciousSelfiePerm==13){
             	sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name\'s Attendance Selfie does not match", "");
             }
              if($SuspiciousSelfiePerm==5 || $SuspiciousSelfiePerm==13){
              	$query= $this->db->query("Select email from admin_login where OrganizationId=$orgid");
		foreach($query->result() as $row){
         $email= $row->email;

             $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$name.' Attendance Selfie does not match with the Face ID
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "Suspicious Selfie(".$date.")";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
               }
           }
                }
                
            }
            /*   $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp' ,overtime =(SELECT subtime(subtime('$time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$date'
                WHERE id=$aid and `EmployeeId`=$userid  ORDER BY `AttendanceDate` DESC LIMIT 1";*/
            //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
            //----------push check code

            try
            {
                $push = "push/";
                if (!file_exists($push)) mkdir($push, 0777, true);
                $filename = $push . $orgid . ".log";
                $fp = fopen($filename, "a+");
                fclose($fp);
            }
            catch(Exception $e)
            {
                echo $e->getMessage();
            }
            //----------push check code
            
        } //LastModifiedDate
        else
        {

            ///-------- code for prevent duplicacy in a same day   code-001
            $sql = "select * from  AttendanceMaster where EmployeeId=$userid and AttendanceDate= '$date'";
echo "select * from  AttendanceMaster where EmployeeId=$userid and AttendanceDate= '$today'".$date;die;
            try
            {
                $result1 = $this
                    ->db
                    ->query($sql);
                if ($this
                    ->db
                    ->affected_rows() < 1)
                { ///////code-001 (ends)
                    $area = getAreaId($userid);
                   // if ($orgid == '10932')
                   // { // only for welspun
                        $area = getNearLocationOfEmp($latit, $longi, $userid);
                   // }

                    /*face recognition code starts here */
                    if ($flag == '1')
                    {
                        $sql6 = "select PersistedFaceId from Persisted_Face where EmployeeId = $userid";
                        $query6 = $this
                            ->db
                            ->query($sql6);
                        if ($row6 = $query6->row())
                        {

                            if ($row6->PersistedFaceId == '0')
                            {
                                $fid = getfaceid($new_name);
                                if ($fid == '0')
                                {
                                    $result['facerecog'] = '5';
                                    $this
                                        ->db
                                        ->close();
                                    echo json_encode($result);
                                    return;

                                }
                                $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                                $sql6 = "update Persisted_Face set PersistedFaceId = '$persistedfaceid' ,profileimage='$new_name' where EmployeeId=$userid";
                                $query6 = $this
                                    ->db
                                    ->query($sql6);
                                persongrouptrain($persongroup_id);

                            }
                            //face id will be generated here
                            $faceid = getfaceid($new_name);
                            if ($faceid == '0')
                            {
                                $suspicioustimein_status = '1';

                            }
                            else
                            {
                                //face verification will take place over here
                                $timein_confidence = face_verify($faceid, $personid, $persongroup_id);
                                if ($timein_confidence < '0.75') $suspicioustimein_status = '1';
                            }

                        }
                        else
                        { //face will be added here
                            $fid = getfaceid($new_name);
                            if ($fid == '0')
                            {
                                $result['facerecog'] = '5';
                                $this
                                    ->db
                                    ->close();
                                echo json_encode($result);
                                return;

                            }
                            $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                            $sql6 = "insert into Persisted_Face(PersonId,	PersistedFaceId,EmployeeId,profileimage) values ('$personid','$persistedfaceid',$userid,'$new_name')";
                            $query6 = $this
                                ->db
                                ->query($sql6);
                            persongrouptrain($persongroup_id);
                            $faceid = $persistedfaceid;
                            if ($persistedfaceid == '0')
                            {
                                $suspicioustimein_status = '1';
                            }
                        }
                        $sql6 = "select profileimage from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                        if ($row6 = $query6->row())
                    {
                        $profileimage=$row6->profileimage;
                    }
                    }
                    /*face recognition code ends here */

                    		// calculation of late comer start

                    		$time = date("H:i") == "00:00" ? "23:59" : date("H:i");

              $query564  = $this->db->query("SELECT TimeDiff('$time',TimeIn)as timediff FROM ShiftMaster where Id= '$shiftId'");
                   
               
                 // var_dump($this->db->last_query());
               

                    if ($row564=$query564->row()) {
                  
                    	$cond = $row564->timediff;

                  if($row564->timediff  > '00:00:00'){
                        $lateby=$cond;
                  }
                  else{

                  	$earlyby=$cond;
                  }
  
                    }
                    else{
                    $lateby= '00:00:00';
                    $earlyby='00:00:00';
                    }

                    // calculation of late comer end 


                    $sql = "INSERT INTO `AttendanceMaster`(`lateby`,`earlyby`,`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`,`EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`,`TimeOut`,`ShiftId`,Dept_id,Desg_id,areaId,HourlyRateId, `OrganizationId`,
      `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `Overtime`, `EntryImage`, `checkInLoc`,`device`,latit_in,longi_in,timeindate,Platform,`TimeInDeviceName`,`TimeInDeviceId`,`SuspiciousDeviceTimeInStatus`,TimeInFaceId,SuspiciousTimeInStatus,TimeInConfidence,PersistedFaceTimeIn,timeincity)
      VALUES ('$lateby','$earlyby',$FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,$userid,'$date',1,'$time','00:00:00',$shiftId,$dept,$desg,$area,$hourltRate,$orgid,'$date',$userid,'$stamp',$userid,$userid,'00:00:00','" . $new_name . "','$addr','mobile','$latit','$longi','$today',' $platform','$devicenamebrand','$deviceidmobile', '$suspiciousdevice','$faceid','$suspicioustimein_status','$timein_confidence','$profileimage','$city')";
                    Trace('User Attendance: ' . $userid . ' ' . $sql);
                    if($suspicioustimein_status=='1'){
                	if($SuspiciousSelfiePerm==9|| $SuspiciousSelfiePerm==13||$SuspiciousSelfiePerm==11|| $SuspiciousSelfiePerm==15){
             	sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name\'s Attendance Selfie does not match", "");
             }
              if($SuspiciousSelfiePerm==5 || $SuspiciousSelfiePerm==13||$SuspiciousSelfiePerm==7|| $SuspiciousSelfiePerm==15){
              	$query= $this->db->query("Select email from admin_login where OrganizationId=$orgid");
		foreach($query->result() as $row){
         $email= $row->email;

             $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$name.' Attendance Selfie does not match with the Face ID
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "Suspicious Selfie(".$date.")";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   //sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
               }
               }
                }

                }
                else $sql = '';
            }
            catch(Exception $e)
            {
                Trace('Error_2: ' . $e->getMessage());
                $errorMsg = 'Message: ' . $e->getMessage();
                $status = 0;
            }
        }
        try
        {
            $query = $this
                ->db
                ->query($sql);
            if ($this
                ->db
                ->affected_rows() > 0 && $act == 'TimeIn')
            {
                //----------push check code
                try
                {
                    $push = "push/";
                    if (!file_exists($push)) mkdir($push, 0777, true);
                    $filename = $push . $orgid . ".log";
                    $fp = fopen($filename, "a+");
                    fclose($fp);
                }
                catch(Exception $e)
                {
                    echo $e->getMessage();
                }
                //----------push check code
                $resCode = 0;
                $status = 1; // update successfully
                $successMsg = "Image uploaded successfully.";
                //////////////////----------------mail send if attndnce is marked very first time in org ever
                $sql = "SELECT  `Email`,ReportNotificationSent,Name  FROM `Organization` WHERE `Id`=" . $orgid;
                $to = '';
                $query1 = $this
                    ->db
                    ->query($sql);
                if ($row = $query1->result())
                {
                    $to = $row[0]->Email;
                    $reportNotificationSent = $row[0]->ReportNotificationSent;
                    $orgname = $row[0]->Name;

                }

                //////////////////----------------/mail send if attndnce is marked very first time in org ever
                
            }
            else
            {
                $status = 2; // no changes found
                $errorMsg .= "Failed to upload Image/No Check In found today.";
            }
        }
        catch(Exception $e)
        {
            Trace('Error_1: ' . $e->getMessage());
            $errorMsg = 'Message: ' . $e->getMessage();
            $status = 0;
        }
        /*  } else {
            Trace('image not uploaded--');
            $status   = 3; // error in uploading image
            $errorMsg = 'Message: error in uploading image';
        }*/

        //emp
        $result['status'] = $status;
        $result['successMsg'] = $successMsg;
        $result['errorMsg'] = $errorMsg;
        //$result['location']=$addr;
        /***    Logic for sending first time in  push notification of employee to admin  ****/
        $EmployeeName = '';
        if ($reportNotificationSent == 0)
        {
            $query1 = $this
                ->db
                ->query("SELECT count(*) as count FROM `AttendanceMaster` as A inner join UserMaster as U where A.OrganizationId=$orgid and A.EmployeeId=U.EmployeeId and U.appSuperviserSts=0 ");
            if ($row = $query1->result())
            {
                $count = $row[0]->count;
                if ($count == 1)
                {
                    $sqlId = "select FirstName from  EmployeeMaster where Id=$userid";
                    $resId = $this
                        ->db
                        ->query($sqlId);
                    if ($rowId = $resId->row())
                    {
                        $EmployeeName = $rowId->FirstName;
                    }
                    $orgnameForNoti = ucwords($orgname);
                    $orgnameForNoti = preg_replace("/[^a-zA-Z]+/", "", $orgnameForNoti);
                    $orgnameForNoti = str_replace(".", "", $orgnameForNoti . $orgid);
                    sendManualPushNotification("('$orgnameForNoti' in topics) && ('admin' in topics) ", "Bingo! $EmployeeName has punched Time in.", "You can check his Attendance");
                    $this
                        ->db
                        ->query("update Organization set ReportNotificationSent=1 where Id=$orgid");
                }

            }
        }
        /***    Logic for sending first time in push notification of employee to admin   ****/
        $this
            ->db
            ->close();
        echo json_encode($result);
    }

    
    
    public function testNoti111(){
		//echo "updateReferralDiscountStatus";
		
		 sendManualPushNotification("('JituAbc50873' in topics) && ('admin' in topics) ","Bingo! Shashank has punched Time in.","You can check his Attendance",'reports');
		//sendManualPushNotification("('UbitechSolutionsPvtLtd10PushNotifications' in topics)","Bingo! Shashank has punched Time in.","You can check his Attendance",'reports');
		
		
	
    }


    /////// get attendance user permission ///////////////
	
	 /////------------------------------------------------------------------------    
    public function getUserPermission()
    {
        $uid     = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
		$roleid   = isset($_REQUEST['roleid']) ? $_REQUEST['roleid'] : 0;
        $result = array();
		$count=0; $errorMsg=""; $successMsg=""; $status=false;
		$res = array();
		//$id=decode5t($id);
		$this->db->select('*');		 
		$whereCondition="(RoleId = $roleid AND OrganizationId = $orgid and ModuleId in (select Id from ModuleMaster where AttendanceAppSts=1))";
		$this->db->where($whereCondition);
		$this->db->from('UserPermission');
		
		$query1 =$this->db->get();
		$count=$query1->num_rows();
		if($count>=1){
			$status=true;
			$successMsg=$count." record found";			
			foreach ($query1->result() as $row)
			{
				$modulename=getName('ModuleMaster','ModuleName','Id',$row->ModuleId);
				$res[$modulename] = (int)$row->ViewPermission;				
			}
        }
		
		$result["userpermission"] = $res;
		$result["orgpermission"] = "Pending";
        //echo json_encode($res);        
		return $result;
    }
    
	
    ///// end getting attendance user permission //////////
    /////--------------------------------------------------------- 
	/*public function getInfo1(){
		$uid=isset($_REQUEST['uid'])?$_REQUEST['uid']:0;
		$orgid=isset($_REQUEST['refno'])?$_REQUEST['refno']:0;
		$data=array();
			//////////////-------getting time zone
			   $sql="SELECT name
				FROM ZoneMaster
				WHERE id = ( 
				SELECT  `TimeZone` 
				FROM  `Organization` 
				WHERE id =$orgid
				LIMIT 1)";
				$zone='Asia/Kolkata';
				$result1 =$this->db->query($sql);
				if($row= $result1->row())
					$zone=$row->name;				
				date_default_timezone_set($zone); 
			//////////////-------/getting time zone
			$date=date('Y-m-d');
			$stype=0;
			 $sql = "SELECT Id,EmployeeCode,FirstName,LastName,shift FROM `EmployeeMaster` WHERE id=$uid";
			$result =$this->db->query($sql);
			foreach($result->result() as $row) {
					$data['shiftId']=$row->shift;
					$data['aid']=0;     //o means no attendance punched till now
					
					//////----------------gettig shift info
					
					$sql1="SELECT TIMEDIFF(  `TimeIn` ,  `TimeOut` ) AS stype
		FROM ShiftMaster where id=".$data['shiftId'];
						try{
							$result1 =$this->db->query($sql1);
							foreach( $result1->result() as $row1){
									$stype=$row1->stype;
							}
						}catch(Exception $e){}
					//////----------------/gettig shift info
					if($stype<0){ //// if shift is end whthin same date
					$sql1="SELECT id as aid,TimeOut FROM `AttendanceMaster` WHERE employeeid=$uid and `AttendanceDate`='$date'";
						try{
							$result1 =$this->db->query($sql1);
							if($row1= $result1->row()){
								$data['act']='TimeOut';
								$data['aid']=$row1->aid;
								if($row1->TimeOut!='00:00:00')
									$data['act']='Imposed';
							}
							else   
								$data['act']='TimeIn';	
						}catch(Exception $e){}
					}else{ 			/////// if shift is start and end in two diff dates
						$sql1="SELECT id as aid,TimeOut FROM `AttendanceMaster` WHERE employeeid=$uid and TimeIn !='00:00:00' and TimeOut='00:00:00' and `AttendanceDate`=DATE_SUB('$date', INTERVAL 1 DAY)";
						try{
								$result1 =$this->db->query($sql1);
								if($row1= $result1->row()){
									$data['act']='TimeOut';
									$data['aid']=$row1->aid;
									if($row1->TimeOut!='00:00:00')
										$data['act']='Imposed';
								}
								else {
								 $sql1="SELECT id as aid,TimeOut FROM `AttendanceMaster` WHERE employeeid=$uid and `AttendanceDate`='$date'";
									try{
										$result1 =$this->db->query($sql1);
										if($row1= $result1->row()){
											$data['act']='TimeOut';
											$data['aid']=$row1->aid;
											if($row1->TimeOut!='00:00:00')
												$data['act']='Imposed';
										}
										else   
											$data['act']='TimeIn';	
									}catch(Exception $e){}
									
								}									
						}catch(Exception $e){}
					}
				}
			
			
			$data['stype']=$stype;
			$data['data']=$date;
			echo json_encode($data); 
				
			}*/
            public function getInfoNew()
            {


 

               // echo getCurrentOrgStatus(41419);die;
        //echo"aaaaaaaaaaaaa";
                $uid     = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
                $orgid   = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
                if($orgid==0)
                    $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
                $data    = array();
                $date1="";


                //////////////-------getting time zone
          /*      $sql     = "SELECT name
                        FROM ZoneMaster
                        WHERE id = ( 
                        SELECT  `TimeZone` 
                        FROM  `Organization` 
                        WHERE id =$orgid
                        LIMIT 1)";
                $zone    = 'Asia/Kolkata';
                $result1 = $this->db->query($sql);
                if ($row = $result1->row())
                    $zone = $row->name;
                    */
                $zone    = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
                date_default_timezone_set($zone);
                //////////////-------/getting time zone
                $date   = date('Y-m-d');
                //echo date('Y-m-d', strtotime($date. ' + 10 days')); 
                $time=date('H:i:s');
                $stypeD=0;
                
                $data['sstatus']=0;
        
                $data['CurrentOrgStatus']=getCurrentOrgStatus($orgid);
        
                 $data['persistedface']="0";
        
                $query = $this->db->query("SELECT `PersistedFaceId`  FROM `Persisted_Face` WHERE  EmployeeId=?", array(
                    $uid
                ));
                if($row=$query->result())
                    $data['persistedface']=$row[0]->PersistedFaceId;
        
                $query = $this->db->query("SELECT `DeviceId`  FROM `EmployeeMaster` WHERE  Id=?", array(
                    $uid
                ));
                if($row=$query->result())
                    $data['deviceid']=$row[0]->DeviceId;
                
               
                $query = $this->db->query("SELECT `appSuperviserSts`  FROM `UserMaster` WHERE  EmployeeId=? and OrganizationId=?", array(
                    $uid,
                    $orgid
                ));
                if($row=$query->result())
                    $data['sstatus']=$row[0]->appSuperviserSts;
                
                $data['pwd']="";
                $query = $this->db->query("SELECT `Password` FROM `UserMaster` WHERE `EmployeeId`=? and OrganizationId=?", array(
                    $uid,
                    $orgid
                ));
                if($row=$query->result())
                    $data['pwd']=decode5t($row[0]->Password);
                
   ////////////////////////////////////////////////////sgCODE//////////////////////////////

   $archiveStatus='';
   $is_DelStatus='';

    $query = $this->db->query("SELECT `Is_Delete` , `archive` FROM `EmployeeMaster` WHERE  `id`=? and OrganizationId=?", array(
       $uid,$orgid
   ));                                          //SgCODE
   if ($row = $query->row()) {
       $archiveStatus = $row->archive;
       $is_DelStatus = $row->Is_Delete;
       if($archiveStatus == '0' || $is_DelStatus =="1" || $is_DelStatus =="2"){
       $data['inactivestatus'] = 'inactive';
        echo json_encode($data);
        return;
    }
 }

		$query = $this->db->query("SELECT `changepasswordStatus` FROM `admin_login` WHERE  OrganizationId=?", array(
            $orgid
         ));
         if($row=$query->result())
             $data['admin_password_sts']=($row[0]->changepasswordStatus);
 
 
         $query = $this->db->query("SELECT `Password_sts` FROM `UserMaster` WHERE `EmployeeId`=? and OrganizationId=?", array(
             $uid,
             $orgid
         ));
         if($row=$query->result())
             $data['password_sts']=($row[0]->Password_sts);
 
         ///////////////////////////////////////////////sgCODE/////////////////////////////////////
		

//echo date('Y-m-d', strtotime($Date. ' + 10 days')); 

              $data["covid_first"] = '0';
              $data["covid_second"] = '0';
		$query = $this->db->query("SELECT * FROM `Covid19EveryDayTest` WHERE EmployeeId = $uid AND Date = '$date' and OrganizationId = $orgid ");
		if($this->db->affected_rows()==0)
			$data["covid_second"] = '1';

		$query = $this->db->query("SELECT * FROM `Covid19Every7DaysTest` WHERE EmployeeId = $uid and OrganizationId = $orgid ORDER BY Date DESC");
		if($this->db->affected_rows()==0)
			$data["covid_first"] = '1';
		else if($this->db->affected_rows()>0){
			if($row=$query->result()){
			$date1=$row[0]->NextDate;
			if($date>=$date1)
				$data['covid_first']='1';

		   }
		}
		

		// print_r($date1);
		// die();
		

                //EmployeeMaster
                
                $data['mail_varified']=0;
                $query = $this->db->query("SELECT `mail_varified`,Country,CreatedDate,Name,(select Name from CountryMaster where Id=Country) as CountryName  FROM `Organization` WHERE Id=?", array(
                    $orgid
                ));
               
                //CountryName
                $data['ReferrerDiscount']="1%";
                $data['ReferrenceDiscount']="1%";
                $data['ReferralValidity']="";
                $data['ReferralValidFrom']="";
                $data['ReferralValidTo']="";
                $queryReferral = $this->db->query("SELECT * FROM `CurrentReferrenceAmounts`");
                if($rowReferral=$queryReferral->result()){
                    //print_r($rowReferral);
                    if($rowReferral[0]->currencyreferrer==0)
                    $data['ReferrerDiscount']="Rs. ".$rowReferral[0]->ReferrerAmount;
                    else if($rowReferral[0]->currencyreferrer==1)
                    $data['ReferrerDiscount']="$".$rowReferral[0]->ReferrerAmount;
                    else if($rowReferral[0]->currencyreferrer==2)
                    $data['ReferrerDiscount']=$rowReferral[0]->ReferrerAmount."%";
                    
                    if($rowReferral[0]->currencyreference==0)
                    $data['ReferrenceDiscount']="Rs.".$rowReferral[0]->ReferrenceAmount;
                    else if($rowReferral[0]->currencyreference==1)
                    $data['ReferrenceDiscount']="$".$rowReferral[0]->ReferrenceAmount;
                    else if($rowReferral[0]->currencyreference==2)
                    $data['ReferrenceDiscount']=$rowReferral[0]->ReferrenceAmount."%";
                
                    $data['ReferralValidity']=date("Y-m-d",strtotime($rowReferral[0]->ValidTo));
                    $data['ReferralValidFrom']=date("Y-m-d",strtotime($rowReferral[0]->ValidFrom));
                    $data['ReferralValidTo']=date("Y-m-d",strtotime($rowReferral[0]->ValidTo));
                
                    //$data['ModifiedDate']= getCountryCodeById1($row[0]->Country);
                }
                //EmployeeMaster
                
                if($row=$query->result()){
                    $data['mail_varified']=$row[0]->mail_varified;
                    $data['orgcountry']=$row[0]->Country;
                    $data['CountryName']=str_replace(' ', '', $row[0]->CountryName);
                    
                    $data['CreatedDate']=$row[0]->CreatedDate;
                    $data['countrycode']= getCountryCodeById1($row[0]->Country);
                    $data['OrgName']=$row[0]->Name;
                    
                    $string=$data['OrgName'];
                    $string=ucwords($string);
        
                    $string = str_replace('', '-', $string); // Replaces all spaces with hyphens.
        
                    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
                    
                    $data['OrgTopic']=$string.$orgid;
                    
                }
                
                $data['registeremp'] = 0;
                $query = $this->db->query("Select count(Id) as count from EmployeeMaster where OrganizationId = $orgid and Is_delete != 2 ");
                if($row = $query->row())
                 $data['registeremp'] = $row->count;
                
                $data['Addon_BulkAttn']=0;
                $data['Addon_GeoFence']=0;
                $data['Addon_Payroll']=0;
                $data['Addon_Tracking']=0;
                $data['Addon_VisitPunch']=0;
                $data['Addon_TimeOff']=0;
                $data['Addon_flexi_shif']=0;
                $data['Addon_offline_mode']=0;
                $data['Addon_AutoTimeOut']=0;
                $data['Addon_FaceRecognition']=0;
                $data['Addon_DeviceVerification']=0;
                $data['addon_livelocationtracking']=0;
                $data['addon_COVID19']=0;
                $data['User_limit'] = 0;
                $data['Addon_advancevisit']=0;
                $data['buysts']= 0;
                $data['visitImage']=getVisitImageStatus($orgid);
                $data['attImage']=getAttImageStatus($orgid);
                $data['ableToMarkAttendance']=ableToMarkAttendance($orgid , $uid);
                $query = $this->db->query("SELECT `Addon_BulkAttn`,addon_livelocationtracking, status,`Addon_LocationTracking`, `Addon_VisitPunch`, `Addon_GeoFence`, `Addon_Payroll`,Addon_TimeOff ,`Addon_flexi_shif` ,`Addon_offline_mode` , Addon_AutoTimeOut , Addon_FaceRecognition, Addon_DeviceVerification,addon_COVID19, user_limit,Addon_advancevisit FROM `licence_ubiattendance` WHERE OrganizationId=?", array($orgid));
                //Organization
                if($row=$query->result()){
                    $data['Addon_BulkAttn']=$row[0]->Addon_BulkAttn;
                    $data['Addon_Payroll']=$row[0]->Addon_Payroll;
                    $data['Addon_Tracking']=$row[0]->Addon_LocationTracking;
                    $data['Addon_VisitPunch']=$row[0]->Addon_VisitPunch;
                    $data['Addon_GeoFence']=$row[0]->Addon_GeoFence;
                    $data['Addon_TimeOff']=$row[0]->Addon_TimeOff;
                    $data['Addon_flexi_shif']=$row[0]->Addon_flexi_shif;
                    $data['Addon_offline_mode']=$row[0]->Addon_offline_mode;
                    $data['Addon_AutoTimeOut']=$row[0]->Addon_AutoTimeOut;
                    $data['Addon_FaceRecognition']=$row[0]->Addon_FaceRecognition;
                    $data['Addon_DeviceVerification']=$row[0]->Addon_DeviceVerification;
                    $data['addon_livelocationtracking']=$row[0]->addon_livelocationtracking;
                    $data['addon_COVID19']=$row[0]->addon_COVID19;
                    $data['buysts']= $row[0]->status;
                    $data['User_limit'] = $row[0]->user_limit;
                    $data['Addon_advancevisit']=$row[0]->Addon_advancevisit;
                }
                $data['areaId'] =0;
                $data['assign_lat'] = 0;
                $data['assign_long'] = 0;
                $data['assign_radius'] = 0;
                $sql = "SELECT Id,EmployeeCode,FirstName,Designation , Department,LastName,shift,area_assigned,ImageName,OrganizationId,Is_Delete,archive,InPushNotificationStatus,OutPushNotificationStatus,livelocationtrack FROM `EmployeeMaster` WHERE id=$uid";
                $result = $this->db->query($sql);
                foreach ($result->result() as $row) {
                
                 /*   $data['areaId'] = $row->area_assigned;
                    $areada = json_decode(getAreaInfo($row->area_assigned),true);
                    $data['assign_lat'] = $areada['lat'];
                    $data['assign_long'] = $areada['long'];
                    $data['assign_radius'] = floatval($areada['radius']);*/
                    $data['TrackLocationEnabled'] = $row->livelocationtrack;
                    $data['departmentid'] = $row->Department;
                    $data['designationid'] = $row->Designation;
                    $data['OutPushNotificationStatus'] = $row->OutPushNotificationStatus;
                    $data['InPushNotificationStatus'] = $row->InPushNotificationStatus;
                    $data['departmentname'] = getDepartment($row->Department);
                    $data['areaId'] = ($row->area_assigned != "")?$row->area_assigned:'0';
                    $areada = json_decode(getAreaInfo($data['areaId']),true);
                    if($areada != '0')
                    {
                       $data['assign_lat'] = $areada['lat'];
                       $data['assign_long'] = $areada['long'];
                       $data['assign_radius'] = floatval($areada['radius']);
                   }
                    $data['shiftId'] = $row->shift;
                    $data['nextWorkingDay']=date_format(date_create(nextWorkingDayAfterToday($row->shift)),"m/d/Y");
                    $data['Is_Delete'] = $row->Is_Delete;
                    if($row->archive == '0'){
                        $data['Is_Delete'] = '1';
                    }
                    $data['aid']     = 0; //o means no attendance punched till now
                    if ($row->ImageName != "") {
                            $dir             = "public/uploads/" . $row->OrganizationId . "/" . $row->ImageName;
                            $data['profile'] = "https://ubitech.ubihrm.com/" . $dir;
                        } else {
                            $data['profile'] = "http://ubiattendance.ubihrm.com/assets/img/avatar.png";
                        }
                    //////----------------gettig shift info
                    $data['ShiftTimeIn'] = getShiftTimeInByEmpID($row->Id);
                    $data['ShiftTimeOut'] = getShiftTimeOutByEmpID($row->Id);
                  /*  $sql1 = "SELECT TIMEDIFF(  `TimeIn` ,  `TimeOut` ) AS stype
                FROM ShiftMaster where id=" . $data['shiftId'];*/
                $sql1 = "SELECT TIMEDIFF(  `TimeIn` ,  `TimeOut` ) AS stypeD,shifttype AS stype,TimeIn as startShiftTime FROM ShiftMaster where id=" . $data['shiftId'];
                $stype=1;
                $stypeD=0;
                $startShiftTime='00:00:00';
                    try {
                        $result1 = $this->db->query($sql1);
                        if ($row1=$result1->result()) {
                            $stypeD = $row1[0]->stypeD;
                            $stype = $row1[0]->stype;
                            $startShiftTime=$row1[0]->startShiftTime;
                        }
                    }
                    catch (Exception $e) {
                    }
                    //////----------------/gettig shift info
                    if ($stype <= 1) { //// if shift is end whthin same date
                       // if($data['Addon_AutoTimeOut']==0) // This orgainzation have a auto timeout
                       if(true)
                       {
                                $sql1 = "SELECT Id as aid,TimeOut,TimeIn FROM `AttendanceMaster` WHERE employeeid=$uid and `AttendanceDate`='$date'";
                                try {
                                    $result1 = $this->db->query($sql1);
                                    if ($row1 = $result1->row()) {
                                        $data['act'] = 'TimeOut';
                                        $data['timeoutdate'] = 'curdate';
                                        $data['aid'] = $row1->aid;
                                        if ($row1->TimeOut != '00:00:00'&&$row1->TimeOut != $row1->TimeIn)
                                            $data['act'] = 'Imposed';
                                        if(autoTimeOutReached($uid,"TimeOut"))
                                        	$data['act'] = 'Imposed';
                                    }else
                                    {
                                        $data['act'] = 'TimeIn';
                                        if(autoTimeOutReached($uid,"TimeIn"))
                                        	$data['act'] = 'Imposed';
                                    }
                            
                            
                            // check last date timeout marked or not ,   if not marked then autotimeout
                               $lastday=date('Y-m-d', strtotime('-1 days'));
                               $query =  $this->db->query("UPDATE AttendanceMaster set TimeOut=TimeIn ,device= 'Auto Time Out' ,timeoutdate = '$lastday' where TimeIn!='00:00:00' and TimeOut='00:00:00' and AttendanceDate='$lastday' and AttendanceDate!=CURDATE()   and employeeid=$uid ");
                               
                                    //	$query3 = $this->db->prepare($sql3);
                                    ////	$query3->execute(array('Auto Time Out'));
                            
                                    
                                }
                                catch (Exception $e) {
                                }
                      }
                      else   //  this organization not have a auto timeout Will will not deal with it in the newer logic
                      {                  
                              $nextday=date('Y-m-d', strtotime('+1 days'));			  
                              $sql1 = "SELECT Id as aid,AttendanceDate,TimeOut,TimeIn FROM `AttendanceMaster` WHERE employeeid=$uid and  Id = (select MAX(Id) from AttendanceMaster WHERE EmployeeId=$uid)  AND AttendanceStatus in (1,4,8) AND (TimeOut =  '00:00:00' OR TimeOut=TimeIn)   ";
                              $result1 = $this->db->query($sql1);
                              if($this->db->affected_rows()>0)
                              { 
                                        try {
                                            if ($row1 = $result1->row()) {
                                                $data['act'] = 'TimeOut';
                                                if(autoTimeOutReached($uid,"TimeOut"))
                                                	$data['act']='Imposed';

                                                if( $row1->AttendanceDate < $date)
                                                $data['timeoutdate'] = 'nextdate';
                                                $data['aid'] = $row1->aid;
                                                if ($row1->TimeOut != '00:00:00'&&$row1->TimeOut!=$row1->TimeIn)
                                                    $data['act'] = 'Imposed';
                                            }else
                                                $data['act'] = 'TimeIn';
                                        }
                                        catch (Exception $e) {
                                        }
                             }
                                else
                                {
                                         $sql1 = "SELECT Id as aid,TimeOut FROM `AttendanceMaster` WHERE employeeid=$uid and `AttendanceDate`='$date'";
                                            try {
                                                $result1 = $this->db->query($sql1);
                                                if ($row1 = $result1->row()) {
                                                    $data['act'] = 'TimeOut';
                                                    $data['timeoutdate'] = 'curdate';
                                                    $data['aid'] = $row1->aid;
                                                    if ($row1->TimeOut != '00:00:00')
                                                        $data['act'] = 'Imposed';
                                                }else
                                                    $data['act'] = 'TimeIn';
                                            }
                                            catch (Exception $e) {
                                            }          
                                }
                      }
                    } else { 	/////// if shift is start and end in two diff dates
                                //$sql1="SELECT id as aid,TimeOut,AttendanceDate FROM `AttendanceMaster` WHERE employeeid=$uid and TimeIn !='00:00:00' and TimeOut='00:00:00' and `AttendanceDate`=DATE_SUB('$date', INTERVAL 1 DAY)";
                                
                                $sql1="SELECT Id as aid,TimeOut,AttendanceDate,TimeIn FROM `AttendanceMaster` WHERE employeeid=$uid and TimeIn !='00:00:00' and (TimeOut='00:00:00' OR TimeOut=TimeIn) and  (`AttendanceDate`>=DATE_SUB('$date', INTERVAL 1 DAY) or `AttendanceDate`='$date') and Id = (select MAX(Id) from AttendanceMaster WHERE EmployeeId=$uid) order by Id desc limit 1";
                                try{
                                        $result1 =$this->db->query($sql1);
                                        if($row1= $result1->row()){
                                            if($row1->AttendanceDate!=$date){ // yes att
                                                if($time>=$startShiftTime){
                                                    $data['act']='TimeIn';
                                                    $data['aid']=0;
                                                    if(autoTimeOutReached($uid,"TimeIn"))
                                                    	$data['act']='Imposed';
                                                }else{
                                                    $data['act']='TimeOut';
                                                    $data['aid']=$row1->aid;
                                                    if(autoTimeOutReached($uid,"TimeOut")){
                                                    	$data['act']='Imposed';
                                                    	$data['aid']=0;

                                                    }
                                                }
                                            }else{ // today att
                                                $data['act']='TimeOut';
                                                $data['aid']=$row1->aid;
                                                    if(autoTimeOutReached($uid,"TimeOut")){
                                                    	$data['act']='Imposed';
                                                    	$data['aid']=0;

                                                    }

                                            }
                                            if($row1->TimeOut!='00:00:00'&&$row1->TimeOut!=$row1->TimeIn)
                                                $data['act']='Imposed';
                                        }
                                        else {
                                         $sql1="SELECT id as aid,TimeOut,TimeIn FROM `AttendanceMaster` WHERE employeeid=$uid and `AttendanceDate`='$date'";
                                            try{
                                                $result1 =$this->db->query($sql1);
                                                if($row1= $result1->row()){
                                                    $data['act']='TimeOut';
                                                    $data['aid']=$row1->aid;
                                                    if(autoTimeOutReached($uid,"TimeOut")){
                                                    	$data['act']='Imposed';
                                                    	$data['aid']=0;

                                                    }
                                                    if($row1->TimeOut!='00:00:00'&&$row1->TimeOut!=$row1->TimeIn)
                                                        $data['act']='Imposed';
                                                }
                                                else{
                                                	$data['act']='TimeIn';
                                                	if(autoTimeOutReached($uid,"TimeIn")){
                                                    	$data['act']='Imposed';
                                                    	

                                                    }
                                                }   
                                                    	
                                            }catch(Exception $e){}
                                            
                                        }									
                                }catch(Exception $e){}
                            
                }
                }



                $data['areaIds'] = "";
                 
               //  if( $data['Addon_GeoFence']==1) // only for welspun
               //      {
			 $ids = $data['areaId'];
			 $data['areaId'] = '0';
			  $query = $this->db->query("SELECT Id,Lat_Long , Radius	from Geo_Settings where id in ($ids) and OrganizationId  = $orgid AND  Lat_Long  !='' ");
			 $a = array();
			 foreach($query->result() as $row)
			 {
				$Id_data = array();
				$arr = explode(",",$row->Lat_Long);
				 $Id_data['lat'] = $arr[0];
				 $Id_data['long'] =$arr[1];
				 $Id_data['radius'] = $row->Radius	;
				 $Id_data['id'] = $row->Id;
				 $a[] =  $Id_data;
			 } 
			 $data['areaIds'] =  json_encode($a);
		// }
                $data['leavetypeid'] = $this->getLeaveTypeId($orgid);
                $data['stype'] = $stypeD;
                $data['data']  = $date;
                $data['TimeInTime']='00:00:00';
                if($data['aid']!=0){
                    $sql1="SELECT TimeIn FROM `AttendanceMaster` WHERE Id='".$data['aid']."'";
                    try{
                        $result1 =$this->db->query($sql1);
                        if($row1= $result1->row()){
                            $data['TimeInTime']=$row1->TimeIn;
                            
                            
                        }
                        
                            
                    }catch(Exception $e){}
        
                }
                
               
                
                 $this->db->close();
                echo json_encode($data);
                 
            }


    public function getInfo()
            {
               // echo getCurrentOrgStatus(41419);die;
            
               
                $uid     = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
                $orgid   = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
                if($orgid==0)
                    $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
                $data    = array();
                $date1="";
 
                //////////////-------getting time zone
          /*      $sql     = "SELECT name
                        FROM ZoneMaster
                        WHERE id = ( 
                        SELECT  `TimeZone` 
                        FROM  `Organization` 
                        WHERE id =$orgid
                        LIMIT 1)";
                $zone    = 'Asia/Kolkata';
                $result1 = $this->db->query($sql);
                if ($row = $result1->row())
                    $zone = $row->name;
                    */
                $zone    = getEmpTimeZone($uid,$orgid); // to set the timezone bsy employee country.

				
                
                date_default_timezone_set($zone);

                //////////////-------/getting time zone
                $date   = date('Y-m-d');
                //echo date('Y-m-d', strtotime($date. ' + 10 days')); 
                $time=date('H:i:s');
                $stypeD=0;
                
                $data['sstatus']=0;
        
                $data['CurrentOrgStatus']=getCurrentOrgStatus($orgid);
        
                 $data['persistedface']="0";

        
                $query = $this->db->query("SELECT `PersistedFaceId`  FROM `Persisted_Face` WHERE  EmployeeId=?", array(
                    $uid
                ));
                if($row=$query->result())
                    $data['persistedface']=$row[0]->PersistedFaceId;

                ///////////// Push Notification ///////////////

                $data['UnderTime']='';
$data['Visit']='';
$data['OutsideGeofence']='';
$data['FakeLocation']='';
$data['FaceIdReg']='';
$data['FaceIdDisapproved']='';
$data['SuspiciousSelfie']='';
$data['SuspiciousDevice']='';
 $data['DisapprovedAtt']='';
$data['AttEdited']='';
$data['ChangedPassword']='';
$data['TimeOffStart']='';
$data['TimeOffEnd']='';

                $query = $this->db->query("SELECT `UnderTime`, `Visit`, `OutsideGeofence`, `FakeLocation`, `FaceIdReg`, `FaceIdDisapproved`, `SuspiciousSelfie`, `SuspiciousDevice`, `DisapprovedAtt`, `AttEdited`, `ChangedPassword`, `TimeOffStart`, `TimeOffEnd`  FROM `NotificationStatus` WHERE  OrganizationId=?", array(
                    $orgid
                ));
                if($row=$query->result()){
                    $data['UnderTime']=$row[0]->UnderTime;
                    $data['Visit']=$row[0]->Visit;
                    $data['OutsideGeofence']=$row[0]->OutsideGeofence;
                    $data['FakeLocation']=$row[0]->FakeLocation;
                    $data['FaceIdReg']=$row[0]->FaceIdReg;
                    $data['FaceIdDisapproved']=$row[0]->FaceIdDisapproved;
                    $data['SuspiciousSelfie']=$row[0]->SuspiciousSelfie;
                    $data['SuspiciousDevice']=$row[0]->SuspiciousDevice;
                    $data['DisapprovedAtt']=$row[0]->DisapprovedAtt;
                    $data['AttEdited']=$row[0]->AttEdited;
                    $data['ChangedPassword']=$row[0]->ChangedPassword;
                    $data['TimeOffStart']=$row[0]->TimeOffStart;
                    $data['TimeOffEnd']=$row[0]->TimeOffEnd;
                }

                 $data['UnderTimeMessage']='';
$data['VisitMessage']='';
$data['OutsideGeofenceMessage']='';
$data['FakeLocationMessage']='';
$data['FaceIdRegMessage']='';
$data['FaceIdDisapprovedMessage']='';
$data['SuspiciousSelfieMessage']='';
$data['SuspiciousDeviceMessage']='';
 $data['DisapprovedAttMessage']='';
$data['AttEditedMessage']='';
$data['ChangedPasswordMessage']='';
$data['TimeOffStartMessage']='';
$data['TimeOffEndMessage']='';

                $query = $this->db->query("SELECT `UnderTimeMessage`, `VisitMessage`, `OutsideGeofenceMessage`, `FakeLocationMessage`, `FaceIdRegMessage`, `FaceIdDisapprovedMessage`, `SuspiciousSelfieMessage`, `SuspiciousDeviceMessage`, `DisapprovedAttMessage`, `AttEditedMessage`, `ChangedPasswordMessage`, `TimeOffStartMessage`, `TimeOffEndMessage`  FROM `NotificationMessage` WHERE  id=?", array(1));
                if($row=$query->result()){
                    $data['UnderTimeMessage']=$row[0]->UnderTimeMessage;
                    $data['VisitMessage']=$row[0]->VisitMessage;
                    $data['OutsideGeofenceMessage']=$row[0]->OutsideGeofenceMessage;
                    $data['FakeLocationMessage']=$row[0]->FakeLocationMessage;
                    $data['FaceIdRegMessage']=$row[0]->FaceIdRegMessage;
                    $data['FaceIdDisapprovedMessage']=$row[0]->FaceIdDisapprovedMessage;
                    $data['SuspiciousSelfieMessage']=$row[0]->SuspiciousSelfieMessage;
                    $data['SuspiciousDeviceMessage']=$row[0]->SuspiciousDeviceMessage;
                    $data['DisapprovedAttMessage']=$row[0]->DisapprovedAttMessage;
                    $data['AttEditedMessage']=$row[0]->AttEditedMessage;
                    $data['ChangedPasswordMessage']=$row[0]->ChangedPasswordMessage;
                    $data['TimeOffStartStatusMessage']=$row[0]->TimeOffStartMessage;
                    $data['TimeOffEndStatusMessage']=$row[0]->TimeOffEndMessage;
                }
                

                 ///////// Push Notification /////////////////
        
                $query = $this->db->query("SELECT `DeviceId`  FROM `EmployeeMaster` WHERE  Id=?", array(
                    $uid
                ));
                if($row=$query->result())
                    $data['deviceid']=$row[0]->DeviceId;
                
               
                $query = $this->db->query("SELECT `appSuperviserSts`  FROM `UserMaster` WHERE  EmployeeId=? and OrganizationId=?", array(
                    $uid,
                    $orgid
                ));
                if($row=$query->result())
                    $data['sstatus']=$row[0]->appSuperviserSts;
                
                $data['pwd']="";
                $query = $this->db->query("SELECT `Password` FROM `UserMaster` WHERE `EmployeeId`=? and OrganizationId=?", array(
                    $uid,
                    $orgid
                ));
                if($row=$query->result())
                    $data['pwd']=decode5t($row[0]->Password);
                
   ////////////////////////////////////////////////////sgCODE//////////////////////////////

   $archiveStatus='';
   $is_DelStatus='';

    $query = $this->db->query("SELECT `Is_Delete` , `archive` FROM `EmployeeMaster` WHERE  `id`=? and OrganizationId=?", array(
       $uid,$orgid
   ));                                          //SgCODE
   if ($row = $query->row()) {
       $archiveStatus = $row->archive;
       $is_DelStatus = $row->Is_Delete;
       if($archiveStatus == '0' || $is_DelStatus =="1" || $is_DelStatus =="2"){
       $data['inactivestatus'] = 'inactive';
        echo json_encode($data);
        return;
    }
 }

		$query = $this->db->query("SELECT `changepasswordStatus` FROM `admin_login` WHERE  OrganizationId=?", array(
            $orgid
         ));
         if($row=$query->result())
             $data['admin_password_sts']=($row[0]->changepasswordStatus);
 
 
         $query = $this->db->query("SELECT `Password_sts` FROM `UserMaster` WHERE `EmployeeId`=? and OrganizationId=?", array(
             $uid,
             $orgid
         ));
         if($row=$query->result())
             $data['password_sts']=($row[0]->Password_sts);
 
         ///////////////////////////////////////////////sgCODE/////////////////////////////////////
		

//echo date('Y-m-d', strtotime($Date. ' + 10 days')); 

              $data["covid_first"] = '0';
              $data["covid_second"] = '0';
		$query = $this->db->query("SELECT * FROM `Covid19EveryDayTest` WHERE EmployeeId = $uid AND Date = '$date' and OrganizationId = $orgid ");
		if($this->db->affected_rows()==0)
			$data["covid_second"] = '1';

		$query = $this->db->query("SELECT * FROM `Covid19Every7DaysTest` WHERE EmployeeId = $uid and OrganizationId = $orgid ORDER BY Date DESC");
		if($this->db->affected_rows()==0)
			$data["covid_first"] = '1';
		else if($this->db->affected_rows()>0){
			if($row=$query->result()){
			$date1=$row[0]->NextDate;
			if($date>=$date1)
				$data['covid_first']='1';

		   }
		}
		

		// print_r($date1);
		// die();
		

                //EmployeeMaster
                
                $data['mail_varified']=0;
                $query = $this->db->query("SELECT `mail_varified`,Country,CreatedDate,Name,(select Name from CountryMaster where Id=Country) as CountryName  FROM `Organization` WHERE Id=?", array(
                    $orgid
                ));
               
                //CountryName
                $data['ReferrerDiscount']="1%";
                $data['ReferrenceDiscount']="1%";
                $data['ReferralValidity']="";
                $data['ReferralValidFrom']="";
                $data['ReferralValidTo']="";
                $queryReferral = $this->db->query("SELECT * FROM `CurrentReferrenceAmounts`");
                if($rowReferral=$queryReferral->result()){
                    //print_r($rowReferral);
                    if($rowReferral[0]->currencyreferrer==0)
                    $data['ReferrerDiscount']="Rs. ".$rowReferral[0]->ReferrerAmount;
                    else if($rowReferral[0]->currencyreferrer==1)
                    $data['ReferrerDiscount']="$".$rowReferral[0]->ReferrerAmount;
                    else if($rowReferral[0]->currencyreferrer==2)
                    $data['ReferrerDiscount']=$rowReferral[0]->ReferrerAmount."%";
                    
                    if($rowReferral[0]->currencyreference==0)
                    $data['ReferrenceDiscount']="Rs.".$rowReferral[0]->ReferrenceAmount;
                    else if($rowReferral[0]->currencyreference==1)
                    $data['ReferrenceDiscount']="$".$rowReferral[0]->ReferrenceAmount;
                    else if($rowReferral[0]->currencyreference==2)
                    $data['ReferrenceDiscount']=$rowReferral[0]->ReferrenceAmount."%";
                
                    $data['ReferralValidity']=date("Y-m-d",strtotime($rowReferral[0]->ValidTo));
                    $data['ReferralValidFrom']=date("Y-m-d",strtotime($rowReferral[0]->ValidFrom));
                    $data['ReferralValidTo']=date("Y-m-d",strtotime($rowReferral[0]->ValidTo));
                
                    //$data['ModifiedDate']= getCountryCodeById1($row[0]->Country);
                }
                //EmployeeMaster
                
                if($row=$query->result()){
                    $data['mail_varified']=$row[0]->mail_varified;
                    $data['orgcountry']=$row[0]->Country;
                    $data['CountryName']=str_replace(' ', '', $row[0]->CountryName);
                    
                    $data['CreatedDate']=$row[0]->CreatedDate;
                    $data['countrycode']= getCountryCodeById1($row[0]->Country);
                    $data['OrgName']=$row[0]->Name;
                    
                    $string=$data['OrgName'];
                    $string=ucwords($string);
        
                    $string = str_replace('', '-', $string); // Replaces all spaces with hyphens.
        
                    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
                    
                    $data['OrgTopic']=$string.$orgid;
                    
                }
                
                $data['registeremp'] = 0;
                $query = $this->db->query("Select count(Id) as count from EmployeeMaster where OrganizationId = $orgid and Is_delete != 2 ");
                if($row = $query->row())
                 $data['registeremp'] = $row->count;
                
                $data['Addon_BulkAttn']=0;
                $data['Addon_GeoFence']=0;
                $data['Addon_Payroll']=0;
                $data['Addon_Tracking']=0;
                $data['Addon_VisitPunch']=0;
                $data['Addon_TimeOff']=0;
                $data['Addon_flexi_shif']=0;
                $data['Addon_offline_mode']=0;
                $data['Addon_AutoTimeOut']=0;
                $data['Addon_FaceRecognition']=0;
                $data['Addon_DeviceVerification']=0;
                $data['addon_livelocationtracking']=0;
                $data['addon_COVID19']=0;
                $data['User_limit'] = 0;
                $data['Addon_advancevisit']=0;
                $data['Addon_BasicLeave']=0;
                $data['buysts']= 0;
                $data['visitImage']=getVisitImageStatus($orgid);
                $data['attImage']=getAttImageStatus($orgid);
                $data['ableToMarkAttendance']=ableToMarkAttendance($orgid , $uid);
                $query = $this->db->query("SELECT `Addon_BulkAttn`,addon_livelocationtracking, status,`Addon_LocationTracking`, `Addon_VisitPunch`, `Addon_GeoFence`, `Addon_Payroll`,Addon_TimeOff ,`Addon_flexi_shif` ,`Addon_offline_mode` , Addon_AutoTimeOut , Addon_FaceRecognition, Addon_DeviceVerification,addon_COVID19, user_limit,Addon_advancevisit,Addon_BasicLeave FROM `licence_ubiattendance` WHERE OrganizationId=?", array($orgid));
                //Organization
                if($row=$query->result()){
                    $data['Addon_BulkAttn']=$row[0]->Addon_BulkAttn;
                    $data['Addon_Payroll']=$row[0]->Addon_Payroll;
                    $data['Addon_Tracking']=$row[0]->Addon_LocationTracking;
                    $data['Addon_VisitPunch']=$row[0]->Addon_VisitPunch;
                    $data['Addon_GeoFence']=$row[0]->Addon_GeoFence;
                    $data['Addon_TimeOff']=$row[0]->Addon_TimeOff;
                    $data['Addon_flexi_shif']=$row[0]->Addon_flexi_shif;
                    $data['Addon_offline_mode']=$row[0]->Addon_offline_mode;
                    $data['Addon_AutoTimeOut']=$row[0]->Addon_AutoTimeOut;
                    $data['Addon_FaceRecognition']=$row[0]->Addon_FaceRecognition;
                    $data['Addon_DeviceVerification']=$row[0]->Addon_DeviceVerification;
                    $data['addon_livelocationtracking']=$row[0]->addon_livelocationtracking;
                    $data['addon_COVID19']=$row[0]->addon_COVID19;
                    $data['Addon_BasicLeave']=$row[0]->Addon_BasicLeave;
                    $data['buysts']= $row[0]->status;
                    $data['User_limit'] = $row[0]->user_limit;
                    $data['Addon_advancevisit']=$row[0]->Addon_advancevisit;
                }
                $data['areaId'] =0;
                $data['assign_lat'] = 0;
                $data['assign_long'] = 0;
                $data['assign_radius'] = 0;
                $sql = "SELECT Id,EmployeeCode,FirstName,Designation , Department,LastName,shift,area_assigned,ImageName,OrganizationId,Is_Delete,archive,InPushNotificationStatus,OutPushNotificationStatus,livelocationtrack FROM `EmployeeMaster` WHERE id=$uid";
                $result = $this->db->query($sql);
                foreach ($result->result() as $row) {

                	///////// Employee Topic Push Notifications ///////////////////

                	$EmployeeId = $row->Id;
                	$data['FirstName'] = $row->FirstName;

                	$string1=$data['FirstName'];
                    $string1=ucwords($string1);
        
                    $string1 = str_replace('', '-', $string1); // Replaces all spaces with hyphens.
        
                    $string1 = preg_replace('/[^A-Za-z0-9\-]/', '', $string1);
                    
                    $data['EmployeeTopic']=$string1.$EmployeeId;

                    ///////// Employee Topic Push Notifications ///////////////////
                
                 /*   $data['areaId'] = $row->area_assigned;
                    $areada = json_decode(getAreaInfo($row->area_assigned),true);
                    $data['assign_lat'] = $areada['lat'];
                    $data['assign_long'] = $areada['long'];
                    $data['assign_radius'] = floatval($areada['radius']);*/
                    $data['TrackLocationEnabled'] = $row->livelocationtrack;
                    $data['departmentid'] = $row->Department;
                    $data['designationid'] = $row->Designation;
                    $data['OutPushNotificationStatus'] = $row->OutPushNotificationStatus;
                    $data['InPushNotificationStatus'] = $row->InPushNotificationStatus;
                    $data['departmentname'] = getDepartment($row->Department);
                    $data['areaId'] = ($row->area_assigned != "")?$row->area_assigned:'0';
                    $areada = json_decode(getAreaInfo($data['areaId']),true);
                    if($areada != '0')
                    {
                       $data['assign_lat'] = $areada['lat'];
                       $data['assign_long'] = $areada['long'];
                       $data['assign_radius'] = floatval($areada['radius']);
                   }
                    $data['shiftId'] = $row->shift;
                    $data['nextWorkingDay']=date_format(date_create(nextWorkingDayAfterToday($row->shift)),"m/d/Y");
                    $data['Is_Delete'] = $row->Is_Delete;
                    if($row->archive == '0'){
                        $data['Is_Delete'] = '1';
                    }
                    $data['aid']     = 0; //o means no attendance punched till now
                    if ($row->ImageName != "") {
                            $dir             = "public/uploads/" . $row->OrganizationId . "/" . $row->ImageName;
                            $data['profile'] = "https://ubitech.ubihrm.com/" . $dir;
                        } else {
                            $data['profile'] = "http://ubiattendance.ubihrm.com/assets/img/avatar.png";
                        }
                    //////----------------gettig shift info
                    $data['ShiftTimeIn'] = getShiftTimeInByEmpID($row->Id);
                    $data['ShiftTimeOut'] = getShiftTimeOutByEmpID($row->Id);
                  /*  $sql1 = "SELECT TIMEDIFF(  `TimeIn` ,  `TimeOut` ) AS stype
                FROM ShiftMaster where id=" . $data['shiftId'];*/
                $sql1 = "SELECT TIMEDIFF(  `TimeIn` ,  `TimeOut` ) AS stypeD,shifttype AS stype,TimeIn as startShiftTime FROM ShiftMaster where id=" . $data['shiftId'];
                $stype=1;
                $stypeD=0;
                $startShiftTime='00:00:00';
                    try {
                        $result1 = $this->db->query($sql1);
                        if ($row1=$result1->result()) {
                            $stypeD = $row1[0]->stypeD;
                            $stype = $row1[0]->stype;
                            $startShiftTime=$row1[0]->startShiftTime;
                        }
                    }
                    catch (Exception $e) {
                    }
                    //////----------------/gettig shift info
                    if ($stype <= 1) { //// if shift is end whthin same date
                        if($data['Addon_AutoTimeOut']==0) // This orgainzation have a auto timeout
                       {
                                $sql1 = "SELECT Id as aid,TimeOut,TimeIn FROM `AttendanceMaster` WHERE employeeid=$uid and `AttendanceDate`='$date'";
                                try {
                                    $result1 = $this->db->query($sql1);
                                    if ($row1 = $result1->row()) {
                                        $data['act'] = 'TimeOut';
                                        $data['timeoutdate'] = 'curdate';
                                        $data['aid'] = $row1->aid;
                                        if ($row1->TimeOut != '00:00:00'&&$row1->TimeOut != $row1->TimeIn)
                                            $data['act'] = 'Imposed';
                                    }else
                                        $data['act'] = 'TimeIn';
                            
                            
                            // check last date timeout marked or not ,   if not marked then autotimeout
                               $lastday=date('Y-m-d', strtotime('-1 days'));
                               $query =  $this->db->query("UPDATE AttendanceMaster set TimeOut=TimeIn ,device= 'Auto Time Out' ,timeoutdate = '$lastday' where TimeIn!='00:00:00' and TimeOut='00:00:00' and AttendanceDate='$lastday' and AttendanceDate!=CURDATE()   and employeeid=$uid ");
                               
                                    //	$query3 = $this->db->prepare($sql3);
                                    ////	$query3->execute(array('Auto Time Out'));
                            
                                    
                                }
                                catch (Exception $e) {
                                }
                      }
                      else   //  this organization not have a auto timeout
                      {                  
                              $nextday=date('Y-m-d', strtotime('+1 days'));			  
                              $sql1 = "SELECT Id as aid,AttendanceDate,TimeOut FROM `AttendanceMaster` WHERE employeeid=$uid and  Id = (select MAX(Id) from AttendanceMaster WHERE EmployeeId=$uid)  AND AttendanceStatus in (1,4,8) AND TimeOut =  '00:00:00'   ";
                              $result1 = $this->db->query($sql1);
                              if($this->db->affected_rows()>0)
                              { 
                                        try {
                                            if ($row1 = $result1->row()) {
                                                $data['act'] = 'TimeOut';
                                                if( $row1->AttendanceDate < $date)
                                                $data['timeoutdate'] = 'nextdate';
                                                $data['aid'] = $row1->aid;
                                                if ($row1->TimeOut != '00:00:00')
                                                    $data['act'] = 'Imposed';
                                            }else
                                                $data['act'] = 'TimeIn';
                                        }
                                        catch (Exception $e) {
                                        }
                             }
                                else
                                {
                                         $sql1 = "SELECT Id as aid,TimeOut FROM `AttendanceMaster` WHERE employeeid=$uid and `AttendanceDate`='$date'";
                                            try {
                                                $result1 = $this->db->query($sql1);
                                                if ($row1 = $result1->row()) {
                                                    $data['act'] = 'TimeOut';
                                                    $data['timeoutdate'] = 'curdate';
                                                    $data['aid'] = $row1->aid;
                                                    if ($row1->TimeOut != '00:00:00')
                                                        $data['act'] = 'Imposed';
                                                }else
                                                    $data['act'] = 'TimeIn';
                                            }
                                            catch (Exception $e) {
                                            }          
                                }
                      }
                    } else { 	/////// if shift is start and end in two diff dates
                                //$sql1="SELECT id as aid,TimeOut,AttendanceDate FROM `AttendanceMaster` WHERE employeeid=$uid and TimeIn !='00:00:00' and TimeOut='00:00:00' and `AttendanceDate`=DATE_SUB('$date', INTERVAL 1 DAY)";
                                
                                $sql1="SELECT Id as aid,TimeOut,AttendanceDate FROM `AttendanceMaster` WHERE employeeid=$uid and TimeIn !='00:00:00' and TimeOut='00:00:00' and  (`AttendanceDate`>=DATE_SUB('$date', INTERVAL 1 DAY) or `AttendanceDate`='$date') and Id = (select MAX(Id) from AttendanceMaster WHERE EmployeeId=$uid) order by Id desc limit 1";
                                try{
                                        $result1 =$this->db->query($sql1);
                                        if($row1= $result1->row()){
                                            if($row1->AttendanceDate!=$date){ // yes att
                                                if($time>=$startShiftTime){
                                                    $data['act']='TimeIn';
                                                    $data['aid']=0;
                                                }else{
                                                    $data['act']='TimeOut';
                                                    $data['aid']=$row1->aid;
                                                }
                                            }else{ // today att
                                                $data['act']='TimeOut';
                                                $data['aid']=$row1->aid;
                                            }
                                            if($row1->TimeOut!='00:00:00')
                                                $data['act']='Imposed';
                                        }
                                        else {
                                         $sql1="SELECT id as aid,TimeOut FROM `AttendanceMaster` WHERE employeeid=$uid and `AttendanceDate`='$date'";
                                            try{
                                                $result1 =$this->db->query($sql1);
                                                if($row1= $result1->row()){
                                                    $data['act']='TimeOut';
                                                    $data['aid']=$row1->aid;
                                                    if($row1->TimeOut!='00:00:00')
                                                        $data['act']='Imposed';
                                                }
                                                else   
                                                    $data['act']='TimeIn';	
                                            }catch(Exception $e){}
                                            
                                        }									
                                }catch(Exception $e){}
                            
                }
                }
                $data['areaIds'] = "";
                   
               //  if( $data['Addon_GeoFence']==1) // only for welspun
               //      {
			 $ids = $data['areaId'];
			 $data['areaId'] = '0';
			  $query = $this->db->query("SELECT Id,Lat_Long , Radius	from Geo_Settings where id in ($ids) and OrganizationId  = $orgid AND  Lat_Long  !='' ");
			 $a = array();
			 foreach($query->result() as $row)
			 {
				$Id_data = array();
				$arr = explode(",",$row->Lat_Long);
				 $Id_data['lat'] = $arr[0];
				 $Id_data['long'] =$arr[1];
				 $Id_data['radius'] = $row->Radius	;
				 $Id_data['id'] = $row->Id;
				 $a[] =  $Id_data;
			 } 
			 $data['areaIds'] =  json_encode($a);
		// }
                $data['leavetypeid'] = $this->getLeaveTypeId($orgid);
                $data['stype'] = $stypeD;
                $data['data']  = $date;
                $data['TimeInTime']='00:00:00';
                if($data['aid']!=0){
                    $sql1="SELECT TimeIn FROM `AttendanceMaster` WHERE Id='".$data['aid']."'";
                    try{
                        $result1 =$this->db->query($sql1);
                        if($row1= $result1->row()){
                            $data['TimeInTime']=$row1->TimeIn;
                            
                            
                        }
                        
                            
                    }catch(Exception $e){}
        
                }
               /* 
              if(autoTimeOutReached($uid,"TimeOut")||autoTimeOutReached($uid,"TimeIn")){
                	$data['act']='TimeIn';
                	$data['aid']=0;
             	//echo "Hiiiiiiii";
//die; 
                }
                */
                 $this->db->close();
                echo json_encode($data);
                
            }


   
    public function saveOfflineVisits()
    {
        $data=$_REQUEST['data'];
        $data=stripslashes($data);
        //echo $data;die;
        $decodedText = html_entity_decode($data);
        $decodedText=stripslashes($decodedText);
       // print_r (json_decode($decodedText));die;
        $data=json_decode($decodedText,true);
        $statusArray=Array();
        $batchData=Array();
		 $this->db->cache_delete_all();
        $this->db->cache_off();
       $query=$this->db->query("update `checkin_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00' ",array('Visit out not punched',$data[0]["EmployeeId"]));
        
        for($i=0;$i<count($data);$i++){  ///-----------Iterating every coming record--------------------
            $sql='';
            $defaultUserImage="iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAgY0hSTQAAeiYAAICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAADPVJREFUeF7tnedy3TgMhe33f7QkTq9OL05xek/+aOfTDm5omiQoiZKAa+2MxptLFYo4aIcgdfjr16/uQPmv6/6dcnh4qJ2+tXsaAQDw8+fP/gj/P/yN3+WQ38O/pbb4vuG9Ute1bpfnp+4r75truwjtBzUvqQ1QDIpawKQAFwusBLj4+lI/t7Z/ShyOxUGs6aEANO2JNXislrUUTgqMKRCVrF5s3da6vuW45O7VAyDW2FCwc2hZyn3krMYQl1ASlOe2Ghebs6axQsfj0LsATXNLAisBKOVeNKsypV27dgmN8vaMIgBKJj7nm3OaFlsVDXRj270JYO3+qhagxnQOCfrWfuHt+WctvgqAmkg9F0Nsg513r1bG5lwQWBJmLTcQv9zmm+0CYQNAIQi2oqVz9qMIAC1DGJJzz/kSF+nemjUd2j4oC9DIkzG5aMpdaGmnxiPsMyCGClhzx6ODwLHxwD4Lx+O7ZQGQQ1pO8J5e/vfv3x3Hnz9/dof8Jn89vc+Uvl4IAITCZrC+f//effnypfv06VP34cOH/vj48WP/72/fvvXMqICDa6cMsPVrRwHA+kuFAv/x40cv2JOTk+7JkyfdvXv3ulu3bnXXr1/vrl692h9HR0f932vXrnU3b97s7t692z1+/Lh78+ZNDxS53z6CQQ0CUwGXRfcQmnA0/P37992zZ8+627dv98K9fPnyuePKlStdfHDepUuX+oM2gPLw4cPu9PS0r5nAMlhXgCH9cw8A0U6EgxkXoaPVCBGBipD5rfaQawQQXHf//v0eWEMG2Pq5KgBSL6ClYVp7i0ERjf/69Wv3+vXrXjiYcNF0BFgr7JrzuB/3xpq8ePGiqkKqhkYvTarJ9RofM6XdHQBE4/HNaPuNGzd6DRdNrxHmlHPEMvBsCRbDzCE1PZ4izERoZgFg1c/Tr1evXvWCz2l7yreHFqFFO88mNsD6EB8ASAJOb4HiqCxAY6NamPj4HgwswR2DLoLPaXILAafuIc+TtjDGIFi8c+dODwZPgaIbACB8/DyDrpnwpQAQPkeCRYBAMOrFEowCwBwargUyx8fHveZrwqd9DQCIi6GPcA1rWMkxclGDwBoeoBTk1ESyuY5zXzSJqLt1VF8DpLHn0FdIJPoeAkGj0bX2MQLWrtkBwCJiGUBYPNI7TatF88cKreV19NWLFTgDgBiBAopUqlJCayseAAA8evTIlfYLEOELiAU0DdSyrdLYt2g74wJKOWnuYWOIIm1QpP3z58877S9F/GFbzlIs/TuxAPMP2ruaAkCNv17KVYjvDwM/LQ7Q2luaee1ewhNoAFi7/VwQqAlYa2/1QjwHP+oVAICRiSjS11ZjMsd9TAIA7YdZE5o3JGBKmmfJAtAXgleZTp5DeC3uqaaBLR4y9B4AAEYtFrYmYK1dM9st2+kLgSDFJpZJIZNpIAMG358jfsKArqXQWt5L+giQNwAMrL1nwJ4+fboBYOC4DbW0nG+WB5BJn5ZaueS9xAJQQFKyAKbSQEs8gHD/ml+P25fO90vPA3CuAGCFBwCIDx48OOcChoJhSY1PBazCBrqIAcIKFW1mboyvGXLNvgCANBA2cwPAiGBmHywAPAY1i64AMERT5zw3FQR6cwGsP4AJdAGApSjeGtAwYCzM8M4DsMCEOsGad17rHLNEEEUg3gGAG7OkWCmQmeQBsABU29aWgK0Z7eeeTd+pZdA0e+MBEgFiOBcw1O9b4QEAAGsRXQHACg8gpWBU2IYAGAqGNS0DAHj+/LnpAPAMFWyJBwAALNMmivYMgJcvX24A0ExgqZ11AJ4BwIym5RQwaQGmCKzltakZQW8uwJUFsJSuyHoANCgldA/1AMQATGm7sQCWAIAlYeAIorwCgH5DBFkb19hKJ3kAC52mDwygNt2bmoWzkgrSt7dv3xZTQRM8gAWBh8hE+9+9e9fXBI4BwJTC0alxRhy0smJYKoNTi2nCsQ/bS4tywrHK1XCEWV18Tni9yaLQVEl4btFnvChkzdw/9WwAAZitxgLmACAl4TEJ5BUAwghuAKisCWCgmENnuzbN/KfaLVoAywtFzVkA/BM+k1U1QwFgTfhitXgXq9PCJgHAYBE85VJAi4IuLV6VwpCWRFmre5kEAEFgTAPXLg+zBg5AbB4A1tJA0E1J2BgL0DKNy0X1Q9JM+oM1y7mAEg8gs7OlNK5JGmgNAASCzKWnCkJikqf1+sHWAOJ+UhmUGmcTAGjlT1rdJ7c2MMXweeABagpDWo3d0PuYjAEAABSqCDz0/yULoGnvGvEBfbI8LWwWAOHmUJ4BULM8bKjWtjzfJAB4QYKmmAvQXMAaGq4FhBBa8hGKloJrdS+zAOAFWRvgge0rVQaTzdTUWrYS6ND7mAVArjLYop8vkUCWJ4J2JWHW0sAQxfH+wBoALLTTB/Y0Zg5Ao4BNpIFWAYAVYHUtTJrszB1+AcQiUSQ7mSN8+q6ZfxMAGOo3ljxfQMAHGrAGMk0cp4ip4pGlg0KET9BH2ufl2wFmY4C4Qog9+EOCyBoAhPJF66WvSyrK2Ge5AIC8HANLUBWmg1aYQPoEeeXpYxG7IHAsepa+DgvAnjthddDSZj43QYTpt74raEperiyArBmU7eMtCJ8+4PuJT1JFn0srydDnuQNAagvZtYGA+RfCZ6gA1j6/B4DVNDAeHCwAZjZeNDokDpiDJ+CeuRk/bWxNpIFaJ9dGafh80itZMGKFB6Af8h3BeKy0sTUBAEsCrumL7CBmZZ6AfnhYCOo+CJQXYOuV1JdCNfM+V6zAc+UjUTUAtnSOqyCQgQuXjVuwAPTBw4aQOdC5BABUa80HJOfS+PC+Xj4Ns1cAkJVDa+8ihvazG6hsB6sFfJZMv/TFnQUQNwAjuCYhhPDZDNr6fL8GOlc8QPgycO4EXvEsoBYItmrnPjW7gGlWwUQaqHVSQ9Fa7QSE8Z7CrQRcqvLhGbW1ftrYmgDAWgKc8lyE//fv3/7Tspji1PTwXEGg7ALK8+mH1aXfNePrMgYQ+hrhkxGE3xaeS+gx3QwZxRfBCAA9zgK6DgLpPBXDovlrLBwVVwP4mJuw/nm4vUoDGWyZhtV8/pwWQdwOnAQWocbkWjvHpQuw9kUxSQkBpqfZVfrqCgAEW3ySPTb9c2p57b291gS44wFkIqiUpmnLteZoD61AmBW4SAO1TlrwWxA/4vtzRaE1peFazDClnfQwjgW0sd14gMrdw6QQpLQwRBNerTkfe54AE4bSS3WwixhA9g2OhW9hOjgGiywOoXbRAwhMAkDYNQaQg+8HhaXgORewtgUQMAACloaxLFzewSpbaAYADJAMFn4Rhg3BE/SFVG8ofCsCT7kMQMD+Bnz9jJlLwBC+o4W4ykQaKIMCnXp6etoXV1L0CcMmCy1DQVsWesodSOEKk0fwFycnJ7tFoxaswioWQIQu+wJTUMlWagxgvAo4HlRPAAgpagEzfwE3YADwWLs11xIuygOI4HlpUjpq6amoCTVdE/A+tPMOYhmwdlgF+cawWAVJD6fuA6hdvwgAQjNPikSuLFx+XN2r+fia9jmIntoJpyEA5VwBPy6CT8wwwylVT4ybJsCp7bO5gNDME9BRPcOsGS8sZj6VxpUEHLdZTAPHcAgCBMaFgPf4+LgvNZM9BuaMFZoDIDTzRL9E8WzqEAo9TuNSgpXfculfrn2MACxdI+6Bv8RFxEe4B8mQWmcP574ZJLNZuW1Nc9SldJBonvSNPFiCuhqzWDonFHZKWFq7JQHX9iV0D8RJxEtSc9AyaJxkAWIzTwonCzfR+JRgasAwxYfXDrCn8wQMuAfiJzaiwD20AEIRADltT5n5OG+PzfwcQZQnIbboqwAhdg+hIg51EdUACB+CTyJ1ETM/JJLfLMBR7xqnHpI9iHsg3hKrMMQyqC5AboZvJzIlqJPcXcx8iqzZLMB0IWsgCbMHzoVTgHqWVLIGCEkAxIQN+an49tSMXOzrc6lcLo1rlQVoA7bP7WHQiDsmVsBKMytZKl0/kwXgP0ANExehiUfTc0FdmKaNHWAtip/aPrZfHq+TsYpdhNQrxlPUOwAI64TgmcUSwkYjXzYAzG/qxwIxJpiYf5B9DM+sCwAVBHYwUClePmfSw8heC+601G7jAeYFkoABUg4lF3bxgOVN+Am0PhfUlciXlOBKgNGAMrV9rLZclOsECASLKH7vAkjnUhsu1GjlBoB5NXcOYIqCMjF3kNtvR3x7rgO5NC+MCTYm0C44cPVkdgealm8AsCvEqdYB2R9owVkNADYewC9IdgAomestC/ArYM1KbABowMtrg2y5fQPABoBx5m3LAsaNmzVr0CQI3GYD/YJhA8DmAvLo1TiC3CzdRgX7sQhNLMDGA/gReOyutyxgcwH/o3dpIqhUBXQR29bKDlazAJQtMTfNwf+H/5bfW7XH97P2b959LQD8BylGHOK4D8ttAAAAAElFTkSuQmCC";
            $offlineTableRecordId=isset($data[$i]["Id"])?$data[$i]["Id"]:0;
            $EmployeeId=isset($data[$i]["EmployeeId"])?$data[$i]["EmployeeId"]:0;
            $VisitInLatitude=isset($data[$i]["VisitInLatitude"])?$data[$i]["VisitInLatitude"]:"";
            $VisitInLongitude=isset($data[$i]["VisitInLongitude"])?$data[$i]["VisitInLongitude"]:"";
            $VisitInTime=isset($data[$i]["VisitInTime"])?$data[$i]["VisitInTime"]:"";
            $VisitInDate=isset($data[$i]["VisitInDate"])?$data[$i]["VisitInDate"]:"";
          
            $VisitOutLatitude=isset($data[$i]["VisitOutLatitude"])?$data[$i]["VisitOutLatitude"]:"";
            $VisitOutLongitude=isset($data[$i]["VisitOutLongitude"])?$data[$i]["VisitOutLongitude"]:"";
            $VisitOutTime=isset($data[$i]["VisitOutTime"])?$data[$i]["VisitOutTime"]:"";
            $VisitOutDate=isset($data[$i]["VisitOutDate"])?$data[$i]["VisitOutDate"]:"";
            $ClientName=isset($data[$i]["ClientName"])?$data[$i]["ClientName"]:"";
            $VisitInDescription=isset($data[$i]["VisitInDescription"])?$data[$i]["VisitInDescription"]:"";
            $VisitOutDescription=isset($data[$i]["VisitOutDescription"])?$data[$i]["VisitOutDescription"]:"";
            $OrganizationId=isset($data[$i]["OrganizationId"])?$data[$i]["OrganizationId"]:"";
            $Skipped=isset($data[$i]["Skipped"])?$data[$i]["Skipped"]:0;
            $VisitInImage=isset($data[$i]["VisitInImage"])?$data[$i]["VisitInImage"]:"";
            $VisitOutImage=isset($data[$i]["VisitOutImage"])?$data[$i]["VisitOutImage"]:"";
            $VisitInAddress=isset($data[$i]["VisitInAddress"])?$data[$i]["VisitInAddress"]:"";
            $VisitOutAddress=isset($data[$i]["VisitOutAddress"])?$data[$i]["VisitOutAddress"]:"";
            $FakeLocationStatusVisitIn=isset($data[$i]["FakeLocationStatusVisitIn"])?$data[$i]["FakeLocationStatusVisitIn"]:0;
            $FakeLocationStatusVisitOut=isset($data[$i]["FakeLocationStatusVisitOut"])?$data[$i]["FakeLocationStatusVisitOut"]:0;

            
            
            
            $device='mobile offline';
            $statusArray[$i][$offlineTableRecordId]='Success';
            
            //echo "EntryImage: $EntryImage  ExitImage: $ExitImage";


            /*----------------------------- Shift Calculation---------------------------------------*/
            if($VisitOutTime=='00:00:00'){
                $VisitOutLatitude="";
                $VisitOutLongitude="";
                $VisitOutAddress="";

            }
            
                         //  echo 'SQL'.$sql;
                $new_name_visit_in="";
                //$milliseconds = substr(round(microtime(true)),4);
                $milliseconds = uniqid();
                $new_name_visit_in   = $EmployeeId . '_' . date('dmY_His')."_".$milliseconds. ".jpg";
                $pic=base64_decode($VisitInImage);

                if(LOCATION=='online')
                    {
                     // $result_save= S3::putObject($pic, 'ubiattendanceimages', 'visits/'.$new_name_visit_in, S3::ACL_PUBLIC_READ);
					   file_put_contents("tempvisitimage/" . $new_name_visit_in, $pic);
				       $file = TEMPVISITIMAGE.$new_name_visit_in;
				       exec("aws s3 mv $file s3://ubiattendanceimages/visits/");
					   $VisitInUrl=IMGPATH.'visits/'.$new_name_visit_in;
			           $VisitInUrl=IMGPATH.'visits/'.$new_name_visit_in;
                      
                    }
                    else{
                    file_put_contents('uploads/'.$new_name_visit_in,$pic);
                    $VisitInUrl=IMGURL.$new_name_visit_in;   
                  }             
                $VisitOutUrl='';
                
                if($Skipped==1){
                    $VisitOutUrl='';
                }
                else{
                    $new_name_visit_out="";
                    //$milliseconds = substr(round(microtime(true)),4);
                    $milliseconds = uniqid();
                    $new_name_visit_out   = $EmployeeId . '_' . date('dmY_His') ."_".$milliseconds. ".jpg";
                    $pic=base64_decode($VisitOutImage);
                    
                    if(LOCATION=='online')
                    {
                      $result_save= S3::putObject($pic, 'ubiattendanceimages', 'visits/'.$new_name_visit_out, S3::ACL_PUBLIC_READ);
                      $VisitOutUrl=IMGPATH.'visits/'.$new_name_visit_out;
                    }
                    else
                    {
                      file_put_contents('uploads/'. $new_name_visit_out,$pic);
                      $VisitOutUrl=IMGURL.$new_name_visit_out;                
                    }
                }
                
                $this->db->cache_delete_all();
                $this->db->cache_off();

                $batchData[$i]=array(
                'EmployeeId'=>$EmployeeId, 
                'location'=>$VisitInAddress, 
                'latit'=>$VisitInLatitude, 
                'longi'=>$VisitInLongitude, 
                'time'=>$VisitInTime, 
                'location_out'=>$VisitOutAddress, 
                'latit_out'=>$VisitOutLatitude, 
                'longi_out'=>$VisitInLongitude, 
                'time_out'=>$VisitOutTime, 
                'date'=>$VisitInDate, 
                'client_name'=>$ClientName, 
                'ClientId'=>0, 
                'description'=>$VisitOutDescription, 
                'descriptionIn'=>'', 
                'OrganizationId'=>$OrganizationId, 
                'skipped'=>$Skipped, 
                'checkin_img'=>$VisitInUrl, 
                'checkout_img'=>$VisitOutUrl, 
                'FakeLocationStatusVisitIn'=>$FakeLocationStatusVisitIn, 
                'FakeLocationStatusVisitOut'=>$FakeLocationStatusVisitOut,

                );

                /*
              $query = $this->db->query(
                "
                INSERT INTO `checkin_master`( 
                `EmployeeId`, 
                `location`, 
                `latit`, 
                `longi`, 
                `time`, 
                `location_out`, 
                `latit_out`, 
                `longi_out`, 
                `time_out`, 
                `date`, 
                `client_name`, 
                `ClientId`, 
                `description`, 
                `descriptionIn`, 
                `OrganizationId`, 
                `skipped`, 
                `checkin_img`, 
                `checkout_img`, 
                `FakeLocationStatusVisitIn`, 
                `FakeLocationStatusVisitOut`)
                
                VALUES 
                (
                    
                    $EmployeeId,
                    '$VisitInAddress',
                    '$VisitInLatitude',
                    '$VisitInLongitude',
                    '$VisitInTime',
                    '$VisitOutAddress',
                    '$VisitOutLatitude',
                    '$VisitOutLongitude',
                    '$VisitOutTime',
                    '$VisitInDate',
                    '$ClientName',
                    '0',
                    '$VisitOutDescription',
                    '',
                    $OrganizationId,
                    $Skipped,
                    '$VisitInUrl',
                    '$VisitOutUrl',
                    '$FakeLocationStatusVisitIn',
                    '$FakeLocationStatusVisitOut'
                    )
                
                "

              );
              */
            
            
        
        }
      //  $this->db->cache_delete_all();
       // $this->db->cache_off();
       // $query=$this->db->query("update `checkin_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00'",array('Visit out not punched',$data[0]["EmployeeId"]));
       $this->db->insert_batch('checkin_master', $batchData);
        echo json_encode($statusArray);
    }
    


    public function saveOfflineQRData()
    {
        $data=$_REQUEST['data'];
        $data=stripslashes($data);
        //echo $data;die;
        $decodedText = html_entity_decode($data);
        $decodedText=stripslashes($decodedText);
       // print_r (json_decode($decodedText));die;
        $data=json_decode($decodedText,true);
        $statusArray=Array();
        
        for($i=0;$i<count($data);$i++){  ///-----------Iterating every coming record--------------------
            $sql='';
            $UserName = encode5t(isset($data[$i]['UserName']) ? trim(strtolower($data[$i]['UserName'])) : '');
            $Password = isset($data[$i]['Password']) ? $data[$i]['Password'] : '';
           //echo "user ".$UserName." pass:".$Password; die;
            $query = $this->db->query("SELECT * FROM `UserMaster` , EmployeeMaster WHERE (Username=? or username_mobile=?)and Password=? and EmployeeMaster.id=UserMaster.`EmployeeId` and UserMaster.archive=1 and EmployeeMaster.archive=1 and EmployeeMaster.Is_Delete=0 and EmployeeMaster.OrganizationId not in(502,1074)", array(
                $UserName,
                $UserName,
                $Password
            )); // custom app- (502 for RAKP) 1074-Erawan
            //echo($query->num_rows());
           if ($query->num_rows()>0) {

            $row= $query->result();

            $offlineTableRecordId=isset($data[$i]["Id"])?$data[$i]["Id"]:0;
            $Time=isset($data[$i]["Time"])?$data[$i]["Time"]:'';
            
            $OrganizationId=$row[0]->OrganizationId;
            $action=$data[$i]["Action"];
            $FakeLocationStatus=isset($data[$i]["FakeLocationStatus"])?$data[$i]["FakeLocationStatus"]:0;
            $FakeLocationStatusTimeIn=0;
            $FakeLocationStatusTimeOut=0;
           // echo 'Action :'.$action.'  Time :'.$time;
            $pictureBase64=$data[$i]["PictureBase64"];
            $Latitude=$data[$i]["Latitude"];
            $Longitude=$data[$i]["Longitude"];
            $Address=isset($data[$i]["Address"])?$data[$i]["Address"]:$Latitude.','.$Longitude;
            $FakeTimeStatus=isset($data[$i]["FakeTimeStatus"])?$data[$i]["FakeTimeStatus"]:0;
            
            
            $EmployeeId=$row[0]->EmployeeId;
            $AttendanceDate= $data[$i]["Date"]; 
            $AttendanceStatus=1;
            $CreatedById=$data[$i]['SupervisorId'];
            $LastModifiedById=0;
            $TimeIn='';
            $TimeCol='';
            $device='mobile offline';
            $EmployeeRecord=getEmployeeForOffline($EmployeeId);
            $timeindate='0000-00-00';
            $timeoutdate='0000-00-00';
            $statusArray[$i][$offlineTableRecordId]='Success';
            $ShiftType=0;
            if($EmployeeRecord!=false){
                $ShiftId=$EmployeeRecord->Shift;
                $Dept_id=$EmployeeRecord->Department;
                $Desg_id=$EmployeeRecord->Designation;
                $areaId=$EmployeeRecord->area_assigned;
                $HourlyRateId=$EmployeeRecord->hourly_rate;
                $OwnerId=$EmployeeRecord->OwnerId;
               // echo 'Employee Record like desg shift etc found';
                
            }
            else{
                $statusArray[$i][$offlineTableRecordId]='Employee Id Not Found';
               // echo 'error while finding shift ';
            
            }


            
            $new_name="";
            $EntryImage='';
            $ExitImage='';
            $FakeTimeInTimeStatus=0;
            $FakeTimeOutTimeStatus=0;
            
            if($action==0){// Time In is synced
                $new_name="";
                //$milliseconds = round(microtime(true) * 100000);
                $milliseconds = uniqid();
                $new_name   = $EmployeeId . '_' . date('dmY_His')."_".$milliseconds. ".jpg";
                $TimeCol="TimeIn";
                $TimeIn=$Time;
                $TimeOut='00:00:00';
                
                 if(LOCATION=='online')
                $EntryImage=IMGPATH.'attendance_images/'.$new_name;
                else
                 $EntryImage=IMGURL . $new_name;
                $ExitImage='';
                $checkInLoc=$Address;
                $checkOutLoc='';
                $FakeLocationStatusTimeIn=$FakeLocationStatus;
                $latit_in=$Latitude;
                $longi_in=$Longitude;
                $latit_out='0.0';
                $longi_out='0.0';
                $timeindate=$AttendanceDate;
                $timeoutdate='0000-00-00';
                $FakeTimeInTimeStatus=$FakeTimeStatus;

            }
            else if($action==1){// Time Out is synced
                $new_name="";
                //$milliseconds = round(microtime(true) * 100000);
                $milliseconds = uniqid();
                $new_name   = $EmployeeId . '_' . date('dmY_His')."_".$milliseconds. ".jpg";
                
                $TimeCol="TimeOut";
                $TimeOut=$Time;
                $TimeIn='00:00:00';
               if(LOCATION=='online')
                $ExitImage=IMGPATH.'attendance_images/'.$new_name;
                else
                $ExitImage=IMGURL.$new_name;
                
                $EntryImage='';
                $checkOutLoc=$Address;
                $checkInLoc='';
                
                $latit_out=$Latitude;
                $longi_out=$Longitude;
                $latit_in='0.0';
                $longi_in='0.0';
                $timeindate='0000-00-00';
                $timeoutdate=$AttendanceDate;
               
                $FakeLocationStatusTimeOut=$FakeLocationStatus;
                $FakeTimeOutTimeStatus=$FakeTimeStatus;

            }
            else{
                $statusArray[$i][$offlineTableRecordId]='Wrong Action Synced';//Wrong data synced
               // echo 'Wrong action';
            }

           //echo "EntryImage: $EntryImage  ExitImage: $ExitImage";


            /*----------------------------- Shift Calculation---------------------------------------*/
            
            $time   = $Time=="00:00:00"?"23:59:00":$Time;
            $shiftId=$ShiftId;
            $stype = 0;
            $sql1  = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;
			
            try {
                $result1 = $this->db->query($sql1);
                if ($row1 = $result1->row()) {
                    $stype = $row1->stype;
					$sti=$row1->TimeIn;
					$sto=$row1->TimeOut;
                    $shifttype=$row1->shifttype;
                    $ShiftType=$shifttype;
                    //echo $ShiftId;;
                }
            }
            catch (Exception $e) {
                $statusArray[$i][$offlineTableRecordId]='Error finding shift information';
            }
            if($shifttype==2 && $action==0){ // multi date shift case
                //echo "inside shift type check";
				if(strtotime($time)<strtotime($sto)){ // time in should mark in last day date
					try{
                  //      echo "changing time in date to previous";
						$ldate   = date('Y-m-d', strtotime('-1 day', strtotime($AttendanceDate)));
                        $AttendanceDate=$ldate;	
                    //    echo $AttendanceDate;
					}catch(Exception $e){
						$statusArray[$i][$offlineTableRecordId]='Error calculating previous day in multi date shift';
					}
				}
				//else  time in should mark in current day's date
            }
            else if($shifttype==2 && $action==1){
                if($time>$sti){ // time in should mark in last day date
					try{
                        //echo "changing time out date to previous";
						$ldate   = date('Y-m-d', strtotime('-1 day', strtotime($AttendanceDate)));
                        $AttendanceDate=$ldate;	
                       // echo $AttendanceDate;
					}catch(Exception $e){
						$statusArray[$i][$offlineTableRecordId]='Error calculating previous day in multi date shift';
					}
				}
            }


            

                

            
           


            
            $insertSql  = "INSERT INTO `AttendanceMaster`(`FakeTimeInTimeStatus`,`FakeTimeOutTimeStatus`,`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`, `EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`, `TimeOut`, `ShiftId`, `Dept_id`, `Desg_id`, `areaId`, `OrganizationId`,  `CreatedById`, `OwnerId`,  `device`, `EntryImage`, `ExitImage`, `checkInLoc`, `CheckOutLoc`, `timeindate`, `timeoutdate`, `latit_in`, `longi_in`, `latit_out`, `longi_out`, `HourlyRateId`) VALUES ($FakeTimeInTimeStatus,$FakeTimeOutTimeStatus,$FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,'$EmployeeId', '$AttendanceDate', '$AttendanceStatus', '$TimeIn', '$TimeOut', '$ShiftId', '$Dept_id', '$Desg_id', '$areaId', '$OrganizationId',  '$CreatedById', '$OwnerId',  '$device', '$EntryImage', '$ExitImage', '$checkInLoc', '$checkOutLoc', '$timeindate', '$timeoutdate', '$latit_in', '$longi_in', '$latit_out', '$longi_out', '$HourlyRateId')";
            $insertOnlyTimeOutSql  = "INSERT INTO `AttendanceMaster`(`FakeTimeInTimeStatus`,`FakeTimeOutTimeStatus`,`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`, `EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`, `TimeOut`, `ShiftId`, `Dept_id`, `Desg_id`, `areaId`, `OrganizationId`,  `CreatedById`, `OwnerId`,  `device`, `EntryImage`, `ExitImage`, `checkInLoc`, `CheckOutLoc`, `timeindate`, `timeoutdate`, `latit_in`, `longi_in`, `latit_out`, `longi_out`, `HourlyRateId`) VALUES ($FakeTimeInTimeStatus,$FakeTimeOutTimeStatus,$FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,'$EmployeeId', '$AttendanceDate', '$AttendanceStatus', '$TimeOut', '$TimeOut', '$ShiftId', '$Dept_id', '$Desg_id', '$areaId', '$OrganizationId',  '$CreatedById', '$OwnerId',  '$device', '$EntryImage', '$ExitImage', '$checkInLoc', '$checkOutLoc', '$timeindate', '$timeoutdate', '$latit_in', '$longi_in', '$latit_out', '$longi_out', '$HourlyRateId')";
            

            $attendanceMarked=checkIfAttendanceAlreadyMarked($OrganizationId,$EmployeeId,$AttendanceDate,$action);
            $updateTimeOutSql='';
            $updateTimeInAfterTimeOutSql='';
            

            if($attendanceMarked!=false){ //A record Exists in database 
                if ($stype < 0) //// if shift is end whthin same date
                {
                    $updateTimeOutSql = "UPDATE `AttendanceMaster` SET `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `TimeOut`='$TimeOut',`device`='$device',`ExitImage`='$ExitImage',`CheckOutLoc`='$checkOutLoc',`latit_out`='$latit_out',`longi_out`='$longi_out',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;
                    $updateTimeInAfterTimeOutSql="UPDATE `AttendanceMaster` SET `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `TimeIn`='$TimeIn',`EntryImage`='$EntryImage',`checkInLoc`='$checkInLoc',`latit_in`='$latit_in',`longi_in`='$longi_in',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;
                }
                else{
                    $updateTimeOutSql= "UPDATE `AttendanceMaster` SET `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatus`=$FakeLocationStatus, `TimeOut`='$TimeOut',`device`='$device',`ExitImage`='$ExitImage',`CheckOutLoc`='$checkOutLoc',`latit_out`='$latit_out',`longi_out`='$longi_out',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;
                    $updateTimeInAfterTimeOutSql="UPDATE `AttendanceMaster` SET `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatus`=$FakeLocationStatus,`TimeIn`='$TimeIn',`EntryImage`='$EntryImage',`checkInLoc`='$checkInLoc',`latit_in`='$latit_in',`longi_in`='$longi_in',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;

                }
               // echo 'attendance found '.$attendanceMarked->Id;
                if($action==1) // Time Out is to be marked and time in is already imposed
                {
                    // check if timeout is not smaller than time in

                    if(validateTimeMultiDateShift($attendanceMarked->AttendanceDate.' '.$attendanceMarked->TimeIn,$AttendanceDate.' '.$TimeOut))
                    {
                            //echo 'time out is to be marked';
                        if($attendanceMarked->device=='Auto Time out' || $attendanceMarked->device=='Auto Time Out' || $attendanceMarked->device=='Manager'){
                            // mark time out
                            $sql=$updateTimeOutSql;
                    //           echo 'Auto timeout found';
                        }
                        else if($attendanceMarked->TimeOut=='00:00:00' || $attendanceMarked->TimeOut==null || $attendanceMarked->TimeOut== '')
                        {
                            // mark time out
                    //     echo 'auto time out not found';
                            $sql=$updateTimeOutSql;
                        
                        }
                        else{
                            $statusArray[$i][$offlineTableRecordId]='Time Out already marked';
                        //   echo 'Attendance already marked';
                        }
                    }
                    else{
                        $statusArray[$i][$offlineTableRecordId]="Time Out is earlier than Time In";
                    }

                   
                }
                else if($action==0){ // We got a record in database( because time in time out was entered before) but we have to sync time in
                   // echo 'time in is to be marked'; 

                  // echo validateTimeMultiDateShift($AttendanceDate.' '.$TimeIn,$attendanceMarked->AttendanceDate.' '.$attendanceMarked->TimeOut);
                   if( validateTimeMultiDateShift($AttendanceDate.' '.$TimeIn,$attendanceMarked->AttendanceDate.' '.$attendanceMarked->TimeOut))
                   {
                    
                        if($attendanceMarked->TimeIn=='00:00:00' || $attendanceMarked->TimeIn==$attendanceMarked->TimeOut || $attendanceMarked->TimeIn==null || $attendanceMarked->TimeIn== '')
                        {
                            // update time in     
                        //  echo 'Time in updated';
                            $sql=$updateTimeInAfterTimeOutSql;
                        }
                        else{
                        //  echo 'time in already marked';
                            $statusArray[$i][$offlineTableRecordId]='Time In already marked';
                        }
                    }
                    else{
                        $statusArray[$i][$offlineTableRecordId]="Time In is later than Time Out";
                    }
                       
                }
                else{
                    $statusArray[$i][$offlineTableRecordId]="Wrong action synced";
                }
                
                
            }
            else{  // A new record is to be created
                if($action==1){  // record does not exist but time out is to be marked
                    // insert record but timeIn should be equal to time out
                    $sql=$insertOnlyTimeOutSql;
                }
                else{
                    $sql=$insertSql;
                }
            }

            if($statusArray[$i][$offlineTableRecordId]=='Success'){
            //    echo 'SQL'.$sql;
                $pic=base64_decode($pictureBase64);

                //echo IMGURL .'shashank'. $new_name;
             if(LOCATION=='online')
                {
			      $result_save= S3::putObject($pic, 'ubiattendanceimages', 'attendance_images/'.$new_name, S3::ACL_PUBLIC_READ);
                }
                else
                {
                  file_put_contents('uploads/'. $new_name,$pic);
                }
                $this->db->cache_delete_all();
                $this->db->cache_off();
                $query = $this->db->query($sql);
                if ($query > 0) {
                    //inserted successfully
                }
                else{
                    $statusArray[$i][$offlineTableRecordId]=='Error';
                    $query = $this->db->query("INSERT INTO `OfflineAttendanceNotSynced`(`FakeTimeStatus`,`FakeLocationStatus`,`EmployeeId`, `OrganizationId`, `SyncDate`, `OfflineMarkedDate`, `Time`, `Action`, `Latitude`, `Longitude`, `ReasonForFailure`) VALUES ($FakeTimeStatus,$FakeLocationStatus,'".$row[0]->EmployeeId."', '".$row[0]->OrganizationId."', '".$currentDate."', '".$data[$i]["Date"]."', '".$data[$i]["Time"]."', '".$data[$i]["Action"]."', '".$data[$i]["Latitude"]."', '".$data[$i]["Longitude"]."', 'Database Insertion Error')");

                }
            }
            else{
              //  echo 'SQL'.$sql;
              $new_name="";
                 $milliseconds = uniqid();
                $new_name   = $EmployeeId . '_' . date('dmY_His') ."_".$milliseconds. ".jpg";
                $pic=base64_decode($pictureBase64);
                $url= ""; 
                if(LOCATION=='online')
                {
			      $result_save= S3::putObject($pic, 'ubiattendanceimages', 'attendance_images/'.$new_name, S3::ACL_PUBLIC_READ);
			      $url= IMGPATH.'attendance_images/'.$new_name; ; 
                }
                else
                {
                  file_put_contents('uploads/'. $new_name,$pic);
                  $url=IMGURL.$new_name; 
                }
                
                //file_put_contents('uploads/'. $new_name,$pic);
                               
              $zone    = getTimeZone($data[$i]["OrganizationId"]);
            date_default_timezone_set($zone);
            $currentDate  = date("Y-m-d H:i:s");
             $this->db->cache_delete_all();
                $this->db->cache_off();
                    
              $query = $this->db->query("INSERT INTO `OfflineAttendanceNotSynced`(`FakeTimeStatus`,`FakeLocationStatus`,`EmployeeId`, `OrganizationId`, `SyncDate`, `OfflineMarkedDate`, `Time`, `Action`, `Latitude`, `Longitude`, `ReasonForFailure`,`image`) VALUES ($FakeTimeStatus,$FakeLocationStatus,'".$row[0]->EmployeeId."', '".$row[0]->OrganizationId."', '".$currentDate."', '".$data[$i]["Date"]."', '".$data[$i]["Time"]."', '".$data[$i]["Action"]."', '".$data[$i]["Latitude"]."', '".$data[$i]["Longitude"]."', '".$statusArray[$i][$offlineTableRecordId]."','".$url."')");
              
            }

        }
        
        }

        echo json_encode($statusArray);
    }
    
	public function updateTimeOut()
	{
		$lastday=date('Y-m-d', strtotime('-1 days'));
		$uid     = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid   = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $data    = array();
        //////////////-------getting time zone
        $sql     = "SELECT name FROM ZoneMaster WHERE id = (  SELECT  `TimeZone`   FROM  `Organization`   WHERE id =$orgid LIMIT 1)";
        $zone    = 'Asia/Kolkata';
        $result1 = $this->db->query($sql);
        if ($row = $result1->row())
            $zone = $row->name;
        date_default_timezone_set($zone);
		 $query =  $this->db->query("UPDATE AttendanceMaster set TimeOut=TimeIn ,device= 'Auto Time Out' ,timeoutdate =timeindate where TimeIn!='00:00:00' and TimeOut='00:00:00' and AttendanceDate='$lastday' and AttendanceDate!=CURDATE()   and employeeid=$uid ");
	}
	public function updateTimeOutNew()
	{
		$lastday=date('Y-m-d', strtotime('-1 days'));
		$uid     = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid   = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $data    = array();
        $name= getEmpName($uid);
        //////////////-------getting time zone
        $sql     = "SELECT name FROM ZoneMaster WHERE id = (  SELECT  `TimeZone`   FROM  `Organization`   WHERE id =$orgid LIMIT 1)";
        $zone    = 'Asia/Kolkata';
        $result1 = $this->db->query($sql);
        if ($row = $result1->row())
            $zone = $row->name;
        date_default_timezone_set($zone);
		 $query =  $this->db->query("UPDATE AttendanceMaster set TimeOut=TimeIn ,device= 'Auto Time Out' ,timeoutdate =timeindate where TimeIn!='00:00:00' and TimeOut='00:00:00' and AttendanceDate='$lastday' and AttendanceDate!=CURDATE()   and employeeid=$uid ");

		 $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$name.' had not punched Time Out on '.$lastday.'. Kindly regularize
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "Missed Time Punch";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   //sendEmail_new($email, $subject, $message, $headers);
                   sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
	}
	public function sendMailByAppToAdmin()
	{
      
		$subject     = isset($_REQUEST['subject']) ? $_REQUEST['subject'] : '';
		$content     = isset($_REQUEST['content']) ? $_REQUEST['content'] : '';
		$orgid     = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : '';
		//$email     = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';

		$query= $this->db->query("Select email from admin_login where OrganizationId=$orgid");
		foreach($query->result() as $row){
         $email= $row->email;
		

		$message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$content.'
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                   // $subject = "Missed Time Punch";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   // sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   // sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                   // sendEmail_new('pulkit@ubitechsolutions.com', $subject, $message, $headers);
                   // sendEmail_new('shakir@ubitechsolutions.com', $subject, $message, $headers);
                   // sendEmail_new('devendra@ubitechsolutions.com', $subject, $message, $headers);
               }
	}
	
	
    
   public function saveOfflineDataOld()
    {
        $data=$_REQUEST['data'];
        $data=stripslashes($data);
        //echo $data;die;
        $decodedText = html_entity_decode($data);
        $decodedText=stripslashes($decodedText);
       // print_r (json_decode($decodedText));die;
        $data=json_decode($decodedText,true);
        $statusArray=Array();
        
        for($i=0;$i<count($data);$i++){  ///-----------Iterating every coming record--------------------
            $sql='';
            $offlineTableRecordId=isset($data[$i]["Id"])?$data[$i]["Id"]:0;
            $Time=isset($data[$i]["Time"])?$data[$i]["Time"]:'';
            
            $OrganizationId=$data[$i]["OrganizationId"];
            $action=$data[$i]["Action"];
            $FakeLocationStatus=isset($data[$i]["FakeLocationStatus"])?$data[$i]["FakeLocationStatus"]:0;
            $FakeLocationStatusTimeIn=0;
            $FakeLocationStatusTimeOut=0;
           // echo 'Action :'.$action.'  Time :'.$time;
            $pictureBase64=$data[$i]["PictureBase64"];
            $Latitude=$data[$i]["Latitude"];
            $Longitude=$data[$i]["Longitude"];
            $Address=isset($data[$i]["Address"])?$data[$i]["Address"]:$Latitude.','.$Longitude;
            $FakeTimeStatus=isset($data[$i]["FakeTimeStatus"])?$data[$i]["FakeTimeStatus"]:0;
            
            
            $EmployeeId=$data[$i]["UserId"];
            $AttendanceDate= $data[$i]["Date"]; 
            $AttendanceStatus=1;
            $CreatedById=$EmployeeId;
            $LastModifiedById=0;
            $TimeIn='';
            $TimeCol='';
            $device='mobile offline';
            $EmployeeRecord=getEmployeeForOffline($EmployeeId);
            $timeindate='0000-00-00';
            $timeoutdate='0000-00-00';
            $statusArray[$i][$offlineTableRecordId]='Success';
            


            $ShiftType=0;
            if($EmployeeRecord!=false){
                $ShiftId=$EmployeeRecord->Shift;
                $Dept_id=$EmployeeRecord->Department;
                $Desg_id=$EmployeeRecord->Designation;
                $areaId=$EmployeeRecord->area_assigned;
                $HourlyRateId=$EmployeeRecord->hourly_rate;
                $OwnerId=$EmployeeRecord->OwnerId;
               // echo 'Employee Record like desg shift etc found';
                
            }
            else{
                $statusArray[$i][$offlineTableRecordId]='Employee Id Not Found';
               // echo 'error while finding shift ';
            
            }


            
            $new_name="";
            $EntryImage='';
            $ExitImage='';
            $FakeTimeInTimeStatus=0;
            $FakeTimeOutTimeStatus=0;
            
            if($action==0){// Time In is synced
                $new_name="";
                //$milliseconds = substr(round(microtime(true)),4);
                $milliseconds = uniqid();
                $new_name   = $EmployeeId . '_' . date('dmY_His')."_".$milliseconds.".jpg";
                $TimeCol="TimeIn";
                $TimeIn=$Time;
                $TimeOut='00:00:00';
                
                if(LOCATION=='online')
                $EntryImage=IMGPATH.'attendance_images/'.$new_name;
                else
                 $EntryImage=IMGURL . $new_name;
                 
                $ExitImage='';
                $checkInLoc=$Address;
                $checkOutLoc='';
                $FakeLocationStatusTimeIn=$FakeLocationStatus;
                $latit_in=$Latitude;
                $longi_in=$Longitude;
                $latit_out='0.0';
                $longi_out='0.0';
                $timeindate=$AttendanceDate;
                $timeoutdate='0000-00-00';
                $FakeTimeInTimeStatus=$FakeTimeStatus;
                if($pictureBase64==""||$pictureBase64==NULL||empty($pictureBase64)){
                    $EntryImage="https://ubitech.ubihrm.com/public/avatars/male.png";
                    
                }    

            }
            else if($action==1){// Time Out is synced
                $new_name="";
                //$milliseconds = substr(round(microtime(true)),4);
                $milliseconds = uniqid();
                $new_name   = $EmployeeId . '_' . date('dmY_His')."_".$milliseconds. ".jpg";
                
                $TimeCol="TimeOut";
                $TimeOut=$Time;
                $TimeIn='00:00:00';
                
                if(LOCATION=='online')
                $ExitImage=IMGPATH.'attendance_images/'.$new_name;
                else
                $ExitImage=IMGURL.$new_name;
                
                $EntryImage='';
                $checkOutLoc=$Address;
                $checkInLoc='';
                
                $latit_out=$Latitude;
                $longi_out=$Longitude;
                $latit_in='0.0';
                $longi_in='0.0';
                $timeindate='0000-00-00';
                $timeoutdate=$AttendanceDate;
               
                $FakeLocationStatusTimeOut=$FakeLocationStatus;
                $FakeTimeOutTimeStatus=$FakeTimeStatus;
                if($pictureBase64==""||$pictureBase64==NULL||empty($pictureBase64)){
                    $ExitImage="https://ubitech.ubihrm.com/public/avatars/male.png";

                }
            }
            else{
                $statusArray[$i][$offlineTableRecordId]='Wrong Action Synced';//Wrong data synced
               // echo 'Wrong action';
            }

            

           //echo "EntryImage: $EntryImage  ExitImage: $ExitImage";


            /*----------------------------- Shift Calculation---------------------------------------*/
            
            $time   = $Time=="00:00:00"?"23:59:00":$Time;
            $shiftId=$ShiftId;
            $stype = 0;
            $sql1  = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;
			
            try {
                $result1 = $this->db->query($sql1);
                if ($row1 = $result1->row()) {
                    $stype = $row1->stype;
					$sti=$row1->TimeIn;
					$sto=$row1->TimeOut;
                    $shifttype=$row1->shifttype;
                    $ShiftType=$shifttype;
                    //echo $ShiftId;;
                }
            }
            catch (Exception $e) {
                $statusArray[$i][$offlineTableRecordId]='Error finding shift information';
            }
            if($shifttype==2 && $action==0){ // multi date shift case
                //echo "inside shift type check";
				if(strtotime($time)<strtotime($sto)){ // time in should mark in last day date
					try{
                  //      echo "changing time in date to previous";
						$ldate   = date('Y-m-d', strtotime('-1 day', strtotime($AttendanceDate)));
                        $AttendanceDate=$ldate;	
                    //    echo $AttendanceDate;
					}catch(Exception $e){
						$statusArray[$i][$offlineTableRecordId]='Error calculating previous day in multi date shift';
					}
				}
				//else  time in should mark in current day's date
            }
            else if($shifttype==2 && $action==1){
                if($time>$sti){ // time in should mark in last day date
					try{
                        //echo "changing time out date to previous";
						$ldate   = date('Y-m-d', strtotime('-1 day', strtotime($AttendanceDate)));
                        $AttendanceDate=$ldate;	
                       // echo $AttendanceDate;
					}catch(Exception $e){
						$statusArray[$i][$offlineTableRecordId]='Error calculating previous day in multi date shift';
					}
				}
            }


            

                

            
           


            
            $insertSql  = "INSERT INTO `AttendanceMaster`(`FakeTimeInTimeStatus`,`FakeTimeOutTimeStatus`,`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`, `EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`, `TimeOut`, `ShiftId`, `Dept_id`, `Desg_id`, `areaId`, `OrganizationId`,  `CreatedById`, `OwnerId`,  `device`, `EntryImage`, `ExitImage`, `checkInLoc`, `CheckOutLoc`, `timeindate`, `timeoutdate`, `latit_in`, `longi_in`, `latit_out`, `longi_out`, `HourlyRateId`) VALUES ($FakeTimeInTimeStatus,$FakeTimeOutTimeStatus,$FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,'$EmployeeId', '$AttendanceDate', '$AttendanceStatus', '$TimeIn', '$TimeOut', '$ShiftId', '$Dept_id', '$Desg_id', '$areaId', '$OrganizationId',  '$CreatedById', '$OwnerId',  '$device', '$EntryImage', '$ExitImage', '$checkInLoc', '$checkOutLoc', '$timeindate', '$timeoutdate', '$latit_in', '$longi_in', '$latit_out', '$longi_out', '$HourlyRateId') ON DUPLICATE KEY UPDATE EmployeeId=EmployeeId";
			
            $insertOnlyTimeOutSql  = "INSERT INTO `AttendanceMaster`(`FakeTimeInTimeStatus`,`FakeTimeOutTimeStatus`,`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`, `EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`, `TimeOut`, `ShiftId`, `Dept_id`, `Desg_id`, `areaId`, `OrganizationId`,  `CreatedById`, `OwnerId`,  `device`, `EntryImage`, `ExitImage`, `checkInLoc`, `CheckOutLoc`, `timeindate`, `timeoutdate`, `latit_in`, `longi_in`, `latit_out`, `longi_out`, `HourlyRateId`) VALUES ($FakeTimeInTimeStatus,$FakeTimeOutTimeStatus,$FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,'$EmployeeId', '$AttendanceDate', '$AttendanceStatus', '$TimeOut', '$TimeOut', '$ShiftId', '$Dept_id', '$Desg_id', '$areaId', '$OrganizationId',  '$CreatedById', '$OwnerId',  '$device', '$EntryImage', '$ExitImage', '$checkInLoc', '$checkOutLoc', '$timeindate', '$timeoutdate', '$latit_in', '$longi_in', '$latit_out', '$longi_out', '$HourlyRateId') ON DUPLICATE KEY UPDATE EmployeeId=EmployeeId";
            

            $attendanceMarked=checkIfAttendanceAlreadyMarked($OrganizationId,$EmployeeId,$AttendanceDate,$action);
            $updateTimeOutSql='';
            $updateTimeInAfterTimeOutSql='';
            

            if($attendanceMarked!=false){ //A record Exists in database 
                if ($stype < 0) //// if shift is end whthin same date
                {
                    $updateTimeOutSql = "UPDATE `AttendanceMaster` SET `timeoutdate` = '$timeoutdate',  `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, AttendanceStatus = 1,  `TimeOut`='$TimeOut',`device`='$device',`ExitImage`='$ExitImage',`CheckOutLoc`='$checkOutLoc',`latit_out`='$latit_out',`longi_out`='$longi_out',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;
					
                    $updateTimeInAfterTimeOutSql="UPDATE `AttendanceMaster` SET `timeindate` = '$timeindate',`FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut,`device`='$device', `TimeIn`='$TimeIn',`EntryImage`='$EntryImage',`checkInLoc`='$checkInLoc',AttendanceStatus = 1,`latit_in`='$latit_in',`longi_in`='$longi_in',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;
                }
                else{
                    $updateTimeOutSql= "UPDATE `AttendanceMaster` SET  `timeoutdate` = '$timeoutdate',  `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatus`=$FakeLocationStatus, `TimeOut`='$TimeOut', AttendanceStatus = 1, `device`='$device',`ExitImage`='$ExitImage',`CheckOutLoc`='$checkOutLoc',`latit_out`='$latit_out',`longi_out`='$longi_out',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;
					
                    $updateTimeInAfterTimeOutSql="UPDATE `AttendanceMaster` SET `timeindate` = '$timeindate', `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`device`='$device',`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatus`=$FakeLocationStatus,`TimeIn`='$TimeIn',`EntryImage`='$EntryImage',`checkInLoc`='$checkInLoc',AttendanceStatus = 1,`latit_in`='$latit_in',`longi_in`='$longi_in',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;

                } 
               // echo 'attendance found '.$attendanceMarked->Id;
                if($action==1) // Time Out is to be marked and time in is already imposed
                {
                    // check if timeout is not smaller than time in

                    if(validateTimeMultiDateShift($attendanceMarked->AttendanceDate.' '.$attendanceMarked->TimeIn,$AttendanceDate.' '.$TimeOut))
                    {
                            //echo 'time out is to be marked';
                        if($attendanceMarked->device=='Auto Time out' || $attendanceMarked->device=='Auto Time Out' || $attendanceMarked->device=='Manager' || $attendanceMarked->device=='Absentee Cron'|| $attendanceMarked->device=='Self-desktop'|| $attendanceMarked->device=='AppManager'|| $attendanceMarked->device=='Cron'|| $attendanceMarked->device=='TimeOut marked by Admin' || $attendanceMarked->device=='Biometric'|| $attendanceMarked->device=='Biometrics'){
                            // mark time out
                            $sql=$updateTimeOutSql;
                    //           echo 'Auto timeout found';
                        }
                        else if($attendanceMarked->TimeOut=='00:00:00' || $attendanceMarked->TimeOut==null || $attendanceMarked->TimeOut== '')
                        {
                            // mark time out
                    //     echo 'auto time out not found';
                            $sql=$updateTimeOutSql;
                        
                        }
                        else{
                            $statusArray[$i][$offlineTableRecordId]='Time Out already marked';
                        //   echo 'Attendance already marked';
                        }
                    }
                    else{
                        $statusArray[$i][$offlineTableRecordId]="Time Out is earlier than Time In";
                    }

                   
                }
                else if($action==0){ // We got a record in database( because time in time out was entered before) but we have to sync time in
                   // echo 'time in is to be marked'; 

                  // echo validateTimeMultiDateShift($AttendanceDate.' '.$TimeIn,$attendanceMarked->AttendanceDate.' '.$attendanceMarked->TimeOut);
                   if( validateTimeMultiDateShift($AttendanceDate.' '.$TimeIn,$attendanceMarked->AttendanceDate.' '.$attendanceMarked->TimeOut))
                   {
                    
                        if($attendanceMarked->TimeIn=='00:00:00' || $attendanceMarked->TimeIn==$attendanceMarked->TimeOut || $attendanceMarked->TimeIn==null || $attendanceMarked->TimeIn== '')
                        {
                            // update time in     
                        //  echo 'Time in updated';
                            $sql=$updateTimeInAfterTimeOutSql;
                        }
                        else{
                        //  echo 'time in already marked';
                            $statusArray[$i][$offlineTableRecordId]='Time In already marked';
                        }
                    }
                    else{
                        $statusArray[$i][$offlineTableRecordId]="Time In is later than Time Out";
                    }
                       
                }
                else{
                    $statusArray[$i][$offlineTableRecordId]="Wrong action synced";
                }
                
                
            }
            else{  // A new record is to be created
                if($action==1){  // record does not exist but time out is to be marked
                    // insert record but timeIn should be equal to time out
                    $sql=$insertOnlyTimeOutSql;
                }
                else{
                    $sql=$insertSql;
                }
            }

            if($statusArray[$i][$offlineTableRecordId]=='Success'){
            //    echo 'SQL'.$sql;
               
                $pic=base64_decode($pictureBase64);
                //
               
                if(LOCATION=='online')
                {
			      //$result_save= S3::putObject($pic, 'ubiattendanceimages', 'attendance_images/'.$new_name, S3::ACL_PUBLIC_READ);
				  file_put_contents("tempimage/" . $new_name, $pic);
				  $file = TEMPIMAGE.$new_name;
				  exec("aws s3 mv $file s3://ubiattendanceimages/attendance_images/");
                }
                else
                {
                file_put_contents('uploads/'. $new_name,$pic);
                }
                 $this->db->cache_delete_all();
                $this->db->cache_off();
                $query = $this->db->query($sql);
                if ($query > 0) {
                    //inserted successfully
                }
                else{
                    $statusArray[$i][$offlineTableRecordId]=='Error';
                    $query = $this->db->query("INSERT INTO `OfflineAttendanceNotSynced`(`FakeTimeStatus`,`FakeLocationStatus`,`EmployeeId`, `OrganizationId`, `SyncDate`, `OfflineMarkedDate`, `Time`, `Action`, `Latitude`, `Longitude`, `ReasonForFailure`) VALUES ($FakeTimeStatus,$FakeLocationStatus,'".$data[$i]["UserId"]."', '".$data[$i]["OrganizationId"]."', '".$currentDate."', '".$data[$i]["Date"]."', '".$data[$i]["Time"]."', '".$data[$i]["Action"]."', '".$data[$i]["Latitude"]."', '".$data[$i]["Longitude"]."', 'Database Insertion Error')");

                }
            }
            else{
              //  echo 'SQL'.$sql;
                $new_name="";
                $milliseconds = uniqid();
                $new_name   = $EmployeeId . '_' . date('dmY_His') ."_".$milliseconds. ".jpg";
                $pic=base64_decode($pictureBase64);
                $url= ""; 
                if(LOCATION=='online')
                {
			      //$result_save= S3::putObject($pic, 'ubiattendanceimages', 'attendance_images/'.$new_name, S3::ACL_PUBLIC_READ);
				  
				  file_put_contents("tempimage/" . $new_name, $pic);
				  $file = TEMPIMAGE.$new_name;
				  exec("aws s3 mv $file s3://ubiattendanceimages/attendance_images/");
			      $url= IMGPATH.'attendance_images/'.$new_name; 
                }
                else
                {
                  file_put_contents('uploads/'. $new_name,$pic);
                  $url=IMGURL.$new_name; 
                }
                $zone    = getTimeZone($data[$i]["OrganizationId"]);
                date_default_timezone_set($zone);
                $currentDate  = date("Y-m-d H:i:s");
                 $this->db->cache_delete_all();
                $this->db->cache_off();    
              $query = $this->db->query("INSERT INTO `OfflineAttendanceNotSynced`(`FakeTimeStatus`,`FakeLocationStatus`,`EmployeeId`, `OrganizationId`, `SyncDate`, `OfflineMarkedDate`, `Time`, `Action`, `Latitude`, `Longitude`, `ReasonForFailure`,`image`) VALUES ($FakeTimeStatus,$FakeLocationStatus,'".$data[$i]["UserId"]."', '".$data[$i]["OrganizationId"]."', '".$currentDate."', '".$data[$i]["Date"]."', '".$data[$i]["Time"]."', '".$data[$i]["Action"]."', '".$data[$i]["Latitude"]."', '".$data[$i]["Longitude"]."', '".$statusArray[$i][$offlineTableRecordId]."','".$url."')");
              
            }
        }

        echo json_encode($statusArray);
    }



	
    
   public function saveOfflineData()
    {
        $data=$_REQUEST['data'];
        $data=stripslashes($data);
        //echo $data;die;
        $decodedText = html_entity_decode($data);
        $decodedText=stripslashes($decodedText);
       // print_r (json_decode($decodedText));die;
        $data=json_decode($decodedText,true);
        $statusArray=Array();
        
        for($i=0;$i<count($data);$i++){  ///-----------Iterating every coming record--------------------
            $sql='';
            $offlineTableRecordId=isset($data[$i]["Id"])?$data[$i]["Id"]:0;
            $Time=isset($data[$i]["Time"])?$data[$i]["Time"]:'';
            
            $OrganizationId=$data[$i]["OrganizationId"];
            $action=$data[$i]["Action"];
            $FakeLocationStatus=isset($data[$i]["FakeLocationStatus"])?$data[$i]["FakeLocationStatus"]:0;
            $FakeLocationStatusTimeIn=0;
            $FakeLocationStatusTimeOut=0;
           // echo 'Action :'.$action.'  Time :'.$time;
            $pictureBase64=$data[$i]["PictureBase64"];
            $Latitude=$data[$i]["Latitude"];
            $Longitude=$data[$i]["Longitude"];
            $Address=isset($data[$i]["Address"])?$data[$i]["Address"]:$Latitude.','.$Longitude;
            $FakeTimeStatus=isset($data[$i]["FakeTimeStatus"])?$data[$i]["FakeTimeStatus"]:0;
            
            
            $EmployeeId=$data[$i]["UserId"];
            $AttendanceDate= $data[$i]["Date"]; 
            $AttendanceStatus=1;
            $CreatedById=$EmployeeId;
            $LastModifiedById=0;
            $TimeIn='';
            $TimeCol='';
            $device='mobile offline';
            $EmployeeRecord=getEmployeeForOffline($EmployeeId);
            $timeindate='0000-00-00';
            $timeoutdate='0000-00-00';
            $statusArray[$i][$offlineTableRecordId]='Success';
            


            $ShiftType=0;
            if($EmployeeRecord!=false){
                $ShiftId=$EmployeeRecord->Shift;
                $Dept_id=$EmployeeRecord->Department;
                $Desg_id=$EmployeeRecord->Designation;
                $areaId=$EmployeeRecord->area_assigned;
                $HourlyRateId=$EmployeeRecord->hourly_rate;
                $OwnerId=$EmployeeRecord->OwnerId;
               // echo 'Employee Record like desg shift etc found';
                
            }
            else{
                $statusArray[$i][$offlineTableRecordId]='Employee Id Not Found';
               // echo 'error while finding shift ';
            
            }


            
            $new_name="";
            $EntryImage='';
            $ExitImage='';
            $FakeTimeInTimeStatus=0;
            $FakeTimeOutTimeStatus=0;
            
            if($action==0){// Time In is synced
                $new_name="";
                //$milliseconds = substr(round(microtime(true)),4);
                $milliseconds = uniqid();
                $new_name   = $EmployeeId . '_' . date('dmY_His')."_".$milliseconds.".jpg";
                $TimeCol="TimeIn";
                $TimeIn=$Time;
                $TimeOut='00:00:00';
                
                if(LOCATION=='online')
                $EntryImage=IMGPATH.'attendance_images/'.$new_name;
                else
                 $EntryImage=IMGURL . $new_name;
                 
                $ExitImage='';
                $checkInLoc=$Address;
                $checkOutLoc='';
                $FakeLocationStatusTimeIn=$FakeLocationStatus;
                $latit_in=$Latitude;
                $longi_in=$Longitude;
                $latit_out='0.0';
                $longi_out='0.0';
                $timeindate=$AttendanceDate;
                $timeoutdate='0000-00-00';
                $FakeTimeInTimeStatus=$FakeTimeStatus;
                if($pictureBase64==""||$pictureBase64==NULL||empty($pictureBase64)){
                    $EntryImage="https://ubitech.ubihrm.com/public/avatars/male.png";
                    
                }    

            }
            else if($action==1){// Time Out is synced
                $new_name="";
                //$milliseconds = substr(round(microtime(true)),4);
                $milliseconds = uniqid();
                $new_name   = $EmployeeId . '_' . date('dmY_His')."_".$milliseconds. ".jpg";
                
                $TimeCol="TimeOut";
                $TimeOut=$Time;
                $TimeIn='00:00:00';
                
                if(LOCATION=='online')
                $ExitImage=IMGPATH.'attendance_images/'.$new_name;
                else
                $ExitImage=IMGURL.$new_name;
                
                $EntryImage='';
                $checkOutLoc=$Address;
                $checkInLoc='';
                
                $latit_out=$Latitude;
                $longi_out=$Longitude;
                $latit_in='0.0';
                $longi_in='0.0';
                $timeindate='0000-00-00';
                $timeoutdate=$AttendanceDate;
               
                $FakeLocationStatusTimeOut=$FakeLocationStatus;
                $FakeTimeOutTimeStatus=$FakeTimeStatus;
                if($pictureBase64==""||$pictureBase64==NULL||empty($pictureBase64)){
                    $ExitImage="https://ubitech.ubihrm.com/public/avatars/male.png";

                }
            }
            else{
                $statusArray[$i][$offlineTableRecordId]='Wrong Action Synced';//Wrong data synced
               // echo 'Wrong action';
            }

            

           //echo "EntryImage: $EntryImage  ExitImage: $ExitImage";


            /*----------------------------- Shift Calculation---------------------------------------*/
            
            $time   = $Time=="00:00:00"?"23:59:00":$Time;
            $shiftId=$ShiftId;
            $stype = 0;
            $sql1  = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;
			
            try {
                $result1 = $this->db->query($sql1);
                if ($row1 = $result1->row()) {
                    $stype = $row1->stype;
					$sti=$row1->TimeIn;
					$sto=$row1->TimeOut;
                    $shifttype=$row1->shifttype;
                    $ShiftType=$shifttype;
                    //echo $ShiftId;;
                }
            }
            catch (Exception $e) {
                $statusArray[$i][$offlineTableRecordId]='Error finding shift information';
            }
            if($shifttype==2 && $action==0){ // multi date shift case
                //echo "inside shift type check";
				if(strtotime($time)<strtotime($sto)){ // time in should mark in last day date
					try{
                  //      echo "changing time in date to previous";
						$ldate   = date('Y-m-d', strtotime('-1 day', strtotime($AttendanceDate)));
                        $AttendanceDate=$ldate;	
                    //    echo $AttendanceDate;
					}catch(Exception $e){
						$statusArray[$i][$offlineTableRecordId]='Error calculating previous day in multi date shift';
					}
				}
				//else  time in should mark in current day's date
            }
            else if($shifttype==2 && $action==1){
                if($time>$sti){ // time in should mark in last day date
					try{
                        //echo "changing time out date to previous";
						$ldate   = date('Y-m-d', strtotime('-1 day', strtotime($AttendanceDate)));
                        $AttendanceDate=$ldate;	
                       // echo $AttendanceDate;
					}catch(Exception $e){
						$statusArray[$i][$offlineTableRecordId]='Error calculating previous day in multi date shift';
					}
				}
            }


            

                

            
           


            
            $insertSql  = "INSERT INTO `AttendanceMaster`(`FakeTimeInTimeStatus`,`FakeTimeOutTimeStatus`,`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`, `EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`, `TimeOut`, `ShiftId`, `Dept_id`, `Desg_id`, `areaId`, `OrganizationId`,  `CreatedById`, `OwnerId`,  `device`, `EntryImage`, `ExitImage`, `checkInLoc`, `CheckOutLoc`, `timeindate`, `timeoutdate`, `latit_in`, `longi_in`, `latit_out`, `longi_out`, `HourlyRateId`) VALUES ($FakeTimeInTimeStatus,$FakeTimeOutTimeStatus,$FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,'$EmployeeId', '$AttendanceDate', '$AttendanceStatus', '$TimeIn', '$TimeOut', '$ShiftId', '$Dept_id', '$Desg_id', '$areaId', '$OrganizationId',  '$CreatedById', '$OwnerId',  '$device', '$EntryImage', '$ExitImage', '$checkInLoc', '$checkOutLoc', '$timeindate', '$timeoutdate', '$latit_in', '$longi_in', '$latit_out', '$longi_out', '$HourlyRateId') ON DUPLICATE KEY UPDATE EmployeeId=EmployeeId";
			
            $insertOnlyTimeOutSql  = "INSERT INTO `AttendanceMaster`(`FakeTimeInTimeStatus`,`FakeTimeOutTimeStatus`,`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`, `EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`, `TimeOut`, `ShiftId`, `Dept_id`, `Desg_id`, `areaId`, `OrganizationId`,  `CreatedById`, `OwnerId`,  `device`, `EntryImage`, `ExitImage`, `checkInLoc`, `CheckOutLoc`, `timeindate`, `timeoutdate`, `latit_in`, `longi_in`, `latit_out`, `longi_out`, `HourlyRateId`) VALUES ($FakeTimeInTimeStatus,$FakeTimeOutTimeStatus,$FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,'$EmployeeId', '$AttendanceDate', '$AttendanceStatus', '$TimeOut', '$TimeOut', '$ShiftId', '$Dept_id', '$Desg_id', '$areaId', '$OrganizationId',  '$CreatedById', '$OwnerId',  '$device', '$EntryImage', '$ExitImage', '$checkInLoc', '$checkOutLoc', '$timeindate', '$timeoutdate', '$latit_in', '$longi_in', '$latit_out', '$longi_out', '$HourlyRateId') ON DUPLICATE KEY UPDATE EmployeeId=EmployeeId";
            

            $attendanceMarked=checkIfAttendanceAlreadyMarked($OrganizationId,$EmployeeId,$AttendanceDate,$action);
            $updateTimeOutSql='';
            $updateTimeInAfterTimeOutSql='';
            

            if($attendanceMarked!=false){ //A record Exists in database 
                if ($stype < 0) //// if shift is end whthin same date
                {
                    $updateTimeOutSql = "UPDATE `AttendanceMaster` SET `timeoutdate` = '$timeoutdate',  `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, AttendanceStatus = 1,  `TimeOut`='$TimeOut',`device`='$device',`ExitImage`='$ExitImage',`CheckOutLoc`='$checkOutLoc',`latit_out`='$latit_out',`longi_out`='$longi_out',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;
					
                    $updateTimeInAfterTimeOutSql="UPDATE `AttendanceMaster` SET `timeindate` = '$timeindate',`FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut,`device`='$device', `TimeIn`='$TimeIn',`EntryImage`='$EntryImage',`checkInLoc`='$checkInLoc',AttendanceStatus = 1,`latit_in`='$latit_in',`longi_in`='$longi_in',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;
                }
                else{
                    $updateTimeOutSql= "UPDATE `AttendanceMaster` SET  `timeoutdate` = '$timeoutdate',  `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatus`=$FakeLocationStatus, `TimeOut`='$TimeOut', AttendanceStatus = 1, `device`='$device',`ExitImage`='$ExitImage',`CheckOutLoc`='$checkOutLoc',`latit_out`='$latit_out',`longi_out`='$longi_out',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;
					
                    $updateTimeInAfterTimeOutSql="UPDATE `AttendanceMaster` SET `timeindate` = '$timeindate', `FakeTimeInTimeStatus`=$FakeTimeInTimeStatus,`device`='$device',`FakeTimeOutTimeStatus`=$FakeTimeOutTimeStatus, `FakeLocationStatus`=$FakeLocationStatus,`TimeIn`='$TimeIn',`EntryImage`='$EntryImage',`checkInLoc`='$checkInLoc',AttendanceStatus = 1,`latit_in`='$latit_in',`longi_in`='$longi_in',overtime =(SELECT subtime(subtime('$time',timein),(select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ) WHERE Id=".$attendanceMarked->Id;

                } 
               // echo 'attendance found '.$attendanceMarked->Id;
                if($action==1) // Time Out is to be marked and time in is already imposed or auto Absentee Cron Marked both time in and out 
                {
                    // check if timeout is not smaller than time in

                    if(validateTimeMultiDateShift($attendanceMarked->AttendanceDate.' '.$attendanceMarked->TimeIn,$AttendanceDate.' '.$TimeOut))
                    {
                            //echo 'time out is to be marked';
                        if($attendanceMarked->device=='Auto Time out' || $attendanceMarked->device=='Auto Time Out' || $attendanceMarked->device=='mobile offline' || $attendanceMarked->device=='mobile' ){
                            // mark time out
                            $sql=$updateTimeOutSql;

                    //           echo 'Auto timeout found';
                        }
                        else if( $attendanceMarked->device=='Absentee Cron')
                        {
                            // mark time out
                    //     echo 'auto time out not found';
                           // $sql=$updateTimeOutSql;
                        	 $statusArray[$i][$offlineTableRecordId]="Time In not marked";
                        
                        }
                        else{
                            $statusArray[$i][$offlineTableRecordId]='Time Out already marked';
                        //   echo 'Attendance already marked';
                        }
                    }
                    else{
                        $statusArray[$i][$offlineTableRecordId]="Time Out is earlier than Time In";
                    }

                   
                }
                else if($action==0){ // We got a record in database( because time in time out was entered before) but we have to sync time in
                   // echo 'time in is to be marked'; 

                  // echo validateTimeMultiDateShift($AttendanceDate.' '.$TimeIn,$attendanceMarked->AttendanceDate.' '.$attendanceMarked->TimeOut);
                   if( validateTimeMultiDateShift($AttendanceDate.' '.$TimeIn,$attendanceMarked->AttendanceDate.' '.$attendanceMarked->TimeOut))
                   {
                    
                        if($attendanceMarked->TimeIn=='00:00:00' || $attendanceMarked->TimeIn==$attendanceMarked->TimeOut || $attendanceMarked->TimeIn==null || $attendanceMarked->TimeIn== '')
                        {
                            // update time in     
                        //  echo 'Time in updated';
                            $sql=$updateTimeInAfterTimeOutSql;
                        }
                        else{
                        //  echo 'time in already marked';
                            $statusArray[$i][$offlineTableRecordId]='Time In already marked';
                        }
                    }
                    else{
                        $statusArray[$i][$offlineTableRecordId]="Time In is later than Time Out";
                    }
                       
                }
                else{
                    $statusArray[$i][$offlineTableRecordId]="Wrong action synced";
                }
                
                
            }
            else{  // A new record is to be created
                if($action==1){  // record does not exist but time out is to be marked
                    // insert record but timeIn should be equal to time out
                    ////$sql=$insertOnlyTimeOutSql;
                    $statusArray[$i][$offlineTableRecordId]="Time In not marked";
                }
                else{
                    $sql=$insertSql;
                }
            }

            if($statusArray[$i][$offlineTableRecordId]=='Success'){
            //    echo 'SQL'.$sql;
               
                $pic=base64_decode($pictureBase64);
                //
               
                if(LOCATION=='online')
                {
			      //$result_save= S3::putObject($pic, 'ubiattendanceimages', 'attendance_images/'.$new_name, S3::ACL_PUBLIC_READ);
				  file_put_contents("tempimage/" . $new_name, $pic);
				  $file = TEMPIMAGE.$new_name;
				  exec("aws s3 mv $file s3://ubiattendanceimages/attendance_images/");
                }
                else
                {
                file_put_contents('uploads/'. $new_name,$pic);
                }
                 $this->db->cache_delete_all();
                $this->db->cache_off();
                $query = $this->db->query($sql);
                if ($query > 0) {
                    //inserted successfully
                }
                else{
                    $statusArray[$i][$offlineTableRecordId]=='Error';
                    $query = $this->db->query("INSERT INTO `OfflineAttendanceNotSynced`(`FakeTimeStatus`,`FakeLocationStatus`,`EmployeeId`, `OrganizationId`, `SyncDate`, `OfflineMarkedDate`, `Time`, `Action`, `Latitude`, `Longitude`, `ReasonForFailure`) VALUES ($FakeTimeStatus,$FakeLocationStatus,'".$data[$i]["UserId"]."', '".$data[$i]["OrganizationId"]."', '".$currentDate."', '".$data[$i]["Date"]."', '".$data[$i]["Time"]."', '".$data[$i]["Action"]."', '".$data[$i]["Latitude"]."', '".$data[$i]["Longitude"]."', 'Database Insertion Error')");

                }
            }
            else{
              //  echo 'SQL'.$sql;
                $new_name="";
                $milliseconds = uniqid();
                $new_name   = $EmployeeId . '_' . date('dmY_His') ."_".$milliseconds. ".jpg";
                $pic=base64_decode($pictureBase64);
                $url= ""; 
                if(LOCATION=='online')
                {
			      //$result_save= S3::putObject($pic, 'ubiattendanceimages', 'attendance_images/'.$new_name, S3::ACL_PUBLIC_READ);
				  
				  file_put_contents("tempimage/" . $new_name, $pic);
				  $file = TEMPIMAGE.$new_name;
				  exec("aws s3 mv $file s3://ubiattendanceimages/attendance_images/");
			      $url= IMGPATH.'attendance_images/'.$new_name; 
                }
                else
                {
                  file_put_contents('uploads/'. $new_name,$pic);
                  $url=IMGURL.$new_name; 
                }
                $zone    = getTimeZone($data[$i]["OrganizationId"]);
                date_default_timezone_set($zone);
                $currentDate  = date("Y-m-d H:i:s");
                 $this->db->cache_delete_all();
                $this->db->cache_off();    
              $query = $this->db->query("INSERT INTO `OfflineAttendanceNotSynced`(`FakeTimeStatus`,`FakeLocationStatus`,`EmployeeId`, `OrganizationId`, `SyncDate`, `OfflineMarkedDate`, `Time`, `Action`, `Latitude`, `Longitude`, `ReasonForFailure`,`image`) VALUES ($FakeTimeStatus,$FakeLocationStatus,'".$data[$i]["UserId"]."', '".$data[$i]["OrganizationId"]."', '".$currentDate."', '".$data[$i]["Date"]."', '".$data[$i]["Time"]."', '".$data[$i]["Action"]."', '".$data[$i]["Latitude"]."', '".$data[$i]["Longitude"]."', '".$statusArray[$i][$offlineTableRecordId]."','".$url."')");
              
            }
        }

        echo json_encode($statusArray);
    }




    public function getNotifications(){
        $OrganizationId=isset($_REQUEST['OrganizationId'])?$_REQUEST['OrganizationId']:0;
        $EmployeeId=isset($_REQUEST['EmployeeId'])?$_REQUEST['EmployeeId']:0;
        //echo $OrganizationId."   ".$EmployeeId;
        $query = $this->db->query("SELECT * FROM `OfflineAttendanceNotSynced` WHERE OrganizationId='".$OrganizationId."' and EmployeeId='".$EmployeeId."' order by Id desc");
        $result=$query->result();
        for($i=0;$i<count($result);$i++){
            $result[$i]->SyncDate=date("F jS, g:i a", strtotime($result[$i]->SyncDate));
            $result[$i]->OfflineMarkedDate=date("jS M", strtotime($result[$i]->OfflineMarkedDate));
            $result[$i]->Time=date("H:i", strtotime($result[$i]->Time));
            $result[$i]->Action=($result[$i]->Action==0)?"Time In":"Time Out";
            

        }

        echo json_encode($result);

            
    }
	
	public function getLeaveTypeId($orgid){
		
		$ci =& get_instance();
		$ci->load->database();
		$name="";$result = array();
		//$conname='';
		$ci->db->select("Id");
		$whereCondition= "(OrganizationId = $orgid AND DefaultSts = 1)";
		$ci->db->where($whereCondition);
		$ci->db->from("LeaveMaster");
		$query =$ci->db->get();
		$count = $query->num_rows();
		if($count>0){
			$status=true;
			$successMsg=$count." record found";
			foreach($query->result() as $row){
				$name=$row->Id;
			}
		}
		return  $name;
	}
	
    public function getHistory()
    {
        $userid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $refno  = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
       //$zone   = getTimeZone($refno);
        $zone    = getEmpTimeZone($userid,$refno); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $dateStart = date('Y-m-d');
		$dateEnd ="";
		$status = GetPlanStatus($refno);
		if($status==1)
	    	  $dateEnd   = date('Y-m-d', strtotime('-30 days'));
	  else
		    $dateEnd   = date('Y-m-d', strtotime('-7 days'));
      
        $query     = $this->db->query("SELECT `AttendanceDate` , `TimeIn`, `TimeOut`,timeindate,timeoutdate , TIMEDIFF(TIMEDIFF(TimeOut,TimeIn),(SELECT SEC_TO_TIME(sum(time_to_sec( TIMEDIFF(TimeTo,TimeFrom))) )as time from  Timeoff where Timeoff.EmployeeId = ? and TimeofDate=AttendanceDate))as activeHours,TIMEDIFF(TimeOut,TimeIn) as thours,(SELECT SEC_TO_TIME(sum(time_to_sec( TIMEDIFF(TimeTo,TimeFrom))) )as time from  Timeoff where Timeoff.EmployeeId = ? and TimeofDate=AttendanceDate and Timeoff.ApprovalSts=2) as bhour,EntryImage,CONCAT(LEFT(checkInLoc,30),'...') as checkInLoc,ExitImage,CONCAT(LEFT(CheckOutLoc,30),'...') as CheckOutLoc,latit_in,longi_in,latit_out,longi_out  FROM `AttendanceMaster` WHERE AttendanceMaster.EmployeeId=? and AttendanceMaster.AttendanceStatus in (1,3,5,4,8)  and date(attendanceDate) between date('" . $dateEnd . "') AND date('" . $dateStart . "') order by DATE(AttendanceDate) desc  ", array(  $userid,  $userid,  $userid)); 
		//and TimeOut!= '00:00:00' //limit 7  
        $data      = $query->result();
        //$query = $this->db->query("SELECT SEC_TO_TIME(sum(time_to_sec( TIMEDIFF(BreakOff,BreakOn))) )as time from  BreakMaster where EmployeeId = ? and date=?",array($userid,$row1->AttendanceDate));
        //    $data['timespent']=$query->result();
        echo json_encode($data);
    }
    
    public function getSlider()
    {
        $orgid = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : 0;
        if ($orgid == 0) {
            
            $query = $this->db->query("SELECT  `link`, `file`  FROM `slider_settings` WHERE  `archive`=1");
            echo json_encode($query->result());
            return;
        } else {
            $query = $this->db->query("SELECT status FROM `licence_ubiattendance` WHERE OrganizationId=?", array(
                $orgid
            ));
            if ($row = $query->row()) {
                $cond = ' WHERE 0=1';
                if ($row->status == 1) // paid users
                    $cond = ' WHERE archive in (1,2)';
                else if ($row->status == 0) // trial users
                    $cond = ' WHERE archive in (1,3)';
                $query = $this->db->query("SELECT  `link`, `file`  FROM `slider_settings`" . $cond);
                echo json_encode($query->result());
                return;
            }
        }
    }
    public function getUsersMobile()
    {
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
		
		//  $query = $this->db->query("SELECT Concat( `FirstName`,' ',`LastName`) as name , (select Name from DepartmentMaster where Id=Department) as `Department` ,(select Name from DesignationMaster where Id=Designation) as `Designation`,(select VisibleSts from UserMaster where EmployeeId = EmployeeMaster.Id) as `archive`, FROM `EmployeeMaster` WHERE  `OrganizationId`=" . $orgid . " and Is_Delete=0 order by FirstName");
		$adminstatus = getAdminStatus($empid);
		$cond = "";
		if($adminstatus == '2')
		{
	     	$dptid = getDepartmentIdByEmpID($empid);
			$cond = " AND Department = $dptid  ";
		}
        //$query = $this->db->query("SELECT EmployeeMaster.Id as Id, Concat( `FirstName`, ' ',`LastName` ) as Name,  Department as DepartmentId,(select Name from DepartmentMaster where Id=Department) as `Department` , Designation as DesignationId, (select Name from DesignationMaster where Id=Designation) as `Designation` , Shift as ShiftId,  VisibleSts as `archive`,Username as Email,username_mobile as mobile,Password,appSuperviserSts as admin,ImageName FROM `EmployeeMaster` , UserMaster WHERE VisibleSts=1 and EmployeeMaster.OrganizationId=" . $orgid . " and EmployeeMaster.Id= UserMaster.EmployeeId and Is_Delete=0 AND EmployeeMaster.OrganizationId = UserMaster.OrganizationId    $cond   order by FirstName");
		$query = $this->db->query("SELECT EmployeeMaster.Id as Id,`FirstName`,`LastName`,  Department as DepartmentId,(select Name from DepartmentMaster where Id=Department) as `Department` , Designation as DesignationId, (select Name from DesignationMaster where Id=Designation) as `Designation` , Shift as ShiftId,  VisibleSts as `archive`,Username as Email,username_mobile as mobile,Password,appSuperviserSts as admin,ImageName FROM `EmployeeMaster` , UserMaster WHERE VisibleSts=1 and EmployeeMaster.OrganizationId=" . $orgid . " and EmployeeMaster.Id= UserMaster.EmployeeId and Is_Delete=0 AND EmployeeMaster.OrganizationId = UserMaster.OrganizationId    $cond   order by FirstName");
	
		$res=array();
		foreach($query->result() as $row){
			$data=array();
			$data['Id']=$row->Id;
			$FirstName=trim($row->FirstName);
			$FirstName=preg_replace('/\s\s+/', ' ',$FirstName);
			$LastName=trim($row->LastName);
			$data['name'] = ucwords(strtolower($FirstName." ".$LastName));
			//$data['name'] = trim($row->Name);
			$data['Department']= $row->Department;
			$data['Designation']=$row->Designation;
			$data['Shift']=getShiftByEmpID($row->Id);
			$data['DepartmentId']= $row->DepartmentId;
			$data['DesignationId']=$row->DesignationId;
			$data['ShiftId'] = $row->ShiftId;
			$data['archive']=$row->archive;
			$data['Email']=decode5t($row->Email);
			$data['Mobile']=decode5t($row->mobile);
			$data['Admin']=$row->admin;
			$data['Password']= decode5t($row->Password);
			$data['Profile']=$row->ImageName==''?
			"http://ubiattendance.ubihrm.com/assets/img/avatar.png":
			'https://ubitech.ubihrm.com/public/uploads/'.$orgid . "/" . $row->ImageName;
			
			$res[]=$data;
			
		}
		echo json_encode($res);
    }
    
    //NKA Code 

   
   public function registeredFaceIDList()
    {
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
        $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : '';
		
		$adminstatus = getAdminStatus($empid);
		$cond = "";
		if($adminstatus == '2')
		{
	     	$dptid = getDepartmentIdByEmpID($empid);
			$cond = " AND Department = $dptid  ";
		}
		if($datafor=='registered'){
			$query = $this->db->query("Select (select FirstName from EmployeeMaster E where E.id=EmployeeId ) as FirstName,(select LastName from EmployeeMaster E where E.id=EmployeeId ) as LastName,profileimage,EmployeeId as Id from Persisted_Face where PersistedFaceId !='0' and OrganizationId='$orgid' order by FirstName");
	
		$res=array();
		$data1=array();
		foreach($query->result() as $row1){
			
			$data1['Id']=$row1->Id;
			$FirstName=trim($row1->FirstName);
			$FirstName=preg_replace('/\s\s+/', ' ',$FirstName);
			$LastName=trim($row1->LastName);
			$data1['name'] = ucwords(strtolower($FirstName." ".$LastName));	
			$data1['Profile']=$row1->profileimage;
			$data1['orgid']=$orgid;
			
			$res[]=$data1;
			
		}
		$data['registered'] =$res;

		}else if($datafor=='unregistered'){
        $query = $this->db->query("SELECT * FROM `EmployeeMaster` Where OrganizationId=" . $orgid . "  and Id Not In (Select EmployeeId from Persisted_Face where PersistedFaceId != '0')  and Is_Delete!=2 and archive=1 order by FirstName");
	
		$res1=array();
		foreach($query->result() as $row2){
			$data2=array();
			$data2['Id']=$row2->Id;
			$FirstName=trim($row2->FirstName);
			$FirstName=preg_replace('/\s\s+/', ' ',$FirstName);
			$LastName=trim($row2->LastName);
			$data2['name'] = ucwords(strtolower($FirstName." ".$LastName));	
			$data2['Profile'] = '';	
			$data2['orgid']=$orgid;
		
			
			$res1[]=$data2;
		}
		$data['unregistered'] =$res1;
	}
        
		
		echo json_encode($data);
    }
     public function disapprovefaceid()
	{
		$orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:"";
		$empid = isset($_REQUEST['empid'])?$_REQUEST['empid']:"";
		$orgTopic = isset($_REQUEST['OrgTopic'])?$_REQUEST['OrgTopic']:"";
		$personid = "";
		$name="User";
		$persistedfaceid = "";
		$zone    = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);		
        $date = date("Y-m-d H:i:s");
        $date1= date("Y-m-d");
		$FaceIdDisapprovedPerm=getNotificationPermission($orgid,'FaceIdDisapproved');

		// echo $perfaceid;
		// echo $pergroupid;
		// echo $perid;
		// exit();

		 $sql1 = "select * From Persisted_Face Where EmployeeId=$empid";
       $query1 = $this
           ->db
           ->query($sql1);
       if ($row1 = $query1->row())
       {
           $personid = $row1->PersonId;
           $persistedfaceid = $row1->PersistedFaceId;
           // print_r($personid);
           // print_r($persistedfaceid);
           // die();

       }
        $sql2 = "select * From EmployeeMaster Where Id=$empid";
       $query2 = $this
           ->db
           ->query($sql2);
       if ($row2 = $query2->row())
       {
           $name = $row2->FirstName;
           // print_r($personid);
           // print_r($persistedfaceid);
           // die();

       }

                	

                	$string1=$name;
                    $string1=ucwords($string1);
        
                    $string1 = str_replace('', '-', $string1); // Replaces all spaces with hyphens.
        
                    $string1 = preg_replace('/[^A-Za-z0-9\-]/', '', $string1);
                    
                    $EmployeeTopic=$string1.$empid;

	 
		face_delete($persistedfaceid,$personid,$orgid);
		persongrouptrain($orgid);

		$query=$this->db->query("UPDATE Persisted_Face SET profileimage = '0' ,PersistedFaceId = '0',ModifiedDate='$date' where EmployeeId=? ",array($empid)); 
         //var_dump($this->db->last_query()); 

              $res= $this->db->affected_rows();	
              if($res){
              $data['status'] = 'Face ID disapproved successfully';
                	 if($FaceIdDisapprovedPerm==9 || $FaceIdDisapprovedPerm==11|| $FaceIdDisapprovedPerm==13 || $FaceIdDisapprovedPerm==15){
             	sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name Face ID has been Disapproved", "");
             }
              if($FaceIdDisapprovedPerm==10|| $FaceIdDisapprovedPerm==11 || $FaceIdDisapprovedPerm==14 ||  $FaceIdDisapprovedPerm==15){
             	sendManualPushNotification("('$EmployeeTopic' in topics)", "Your Face ID has been Disapproved", "");
             }
              if($FaceIdDisapprovedPerm==5 || $FaceIdDisapprovedPerm==13 || $FaceIdDisapprovedPerm==7 ||  $FaceIdDisapprovedPerm==7){
                $query= $this->db->query("Select CurrentEmailId from EmployeeMaster where Id=$empid");
                foreach($query->result() as $row){
                 $email= decode5t($row->CurrentEmailId);
                 


             $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$name.' Face ID has been Disapproved
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "Face ID Disapproved";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                //    sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                //    sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
                
          }else{
          	$data['status'] = 'Unable to disapprove Face ID';
          }
			  $this->db->close();
			  echo json_encode($data, JSON_NUMERIC_CHECK);
	}
    //NKA code
	
	public function getEmployeesList()
    {
        $orgid = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : 0;
        $empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
		
		$adminstatus = getAdminStatus($empid);
		$cond = "";
		
		if($adminstatus == '2')
		{ 
	     	$dptid = getDepartmentIdByEmpID($empid);
			$cond = " AND Department = $dptid  ";
			
		}
		
        $query = $this->db->query("SELECT Id,Concat( `FirstName`,' ',`LastName`) as name , EmployeeCode as ecode FROM `EmployeeMaster` WHERE archive=1 and is_Delete=0 and `OrganizationId` = $orgid $cond and (DOL='0000-00-00' or DOL>curdate()) order by FirstName  ");
		echo json_encode($query->result());
    }
	
    public function getAttendanceMobile()
    {
        // getting counting of attending/onbreak/exits and not attending emps
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $att   = isset($_REQUEST['att']) ? $_REQUEST['att'] : 0;
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $data = array();
        if ($att == 'today') { //today attendance
            $query                = $this->db->query("SELECT count(`Id`) as total FROM `EmployeeMaster`  WHERE `OrganizationId`=" . $orgid . " and archive=1 and Is_Delete=0 ");
            //and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift)
            $data['total']        = $query->result();
            $query                = $this->db->query("SELECT count(`EmployeeId`) as exited FROM AttendanceMaster  WHERE `AttendanceDate`  ='" . $date . "' and `TimeOut` !='00:00:00' and `OrganizationId`=" . $orgid);
            $data['exited']       = $query->result();
            $query                = $this->db->query("SELECT count(`EmployeeId`) as timedin FROM AttendanceMaster  WHERE `AttendanceDate`  ='" . $date . "'  and `OrganizationId`=" . $orgid); //and `TimeOut` ='00:00:00'
            $data['timedin']      = $query->result();
            $query                = $this->db->query("SELECT count(TimeFrom) as onbreak FROM `Timeoff` where TimeofDate=? and (TimeTo ='00:00:00' or TimeTo IS NULL)  and OrganizationId=?", array(
                $date,
                $orgid
            ));
            $data['onbreak']      = $query->result();
            $query                = $this->db->query("select count(Id) as latecomers from AttendanceMaster where `AttendanceDate`  ='" . $date . "' and `OrganizationId`=" . $orgid . " and time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)");
            $data['latecomers']   = $query->result();
            $query                = $this->db->query("select count(Id) as earlyleavers from AttendanceMaster where `AttendanceDate`  ='" . $date . "' and `OrganizationId`=" . $orgid . " and TimeOut !='00:00:00' and time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId)");
            $data['earlyleavers'] = $query->result();
            $query                = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by `name`", array(
                $date,
                $orgid
            ));
            $data['elist']        = $query->result();
            //---managing off (weekly and holiday)
            $dt                   = $date;
            
            //    day of month : 1 sun 2 mon --
            $dayOfWeek   = 1 + date('w', strtotime($dt));
            $weekOfMonth = weekOfMonth($dt);
            $week        = '';
            $query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
                $orgid,
                $dayOfWeek
            ));
            if ($row = $query->result()) {
                $week = explode(",", $row[0]->WeekOff);
            }
            if ($week[$weekOfMonth - 1] == 1) {
                $data['absentees'] = '';
            } else {
                $query = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
                    $orgid,
                    $dt
                ));
                if ($query->num_rows() > 0) {
                    //-----managing off (weekly and holiday) - close            
                    $query             = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status from EmployeeMaster where `OrganizationId` =$orgid and EmployeeMaster.archive=1 and EmployeeMaster.Id not in(select AttendanceMaster.`EmployeeId` from AttendanceMaster where `AttendanceDate`='$date' and `OrganizationId` =$orgid) and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift) order by `name`", array(
                        $orgid,
                        $date,
                        $orgid
                    ));
                    $data['absentees'] = $query->result();
                } else {
                    $data['absentees'] = '';
                }
            }
        } else if ($att == 'today_late') {
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by `name`", array(
                $date,
                $orgid
            ));
            $data['elist'] = $query->result();
        } else if ($att == 'today_early') {
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,'Present' as status,EntryImage,ExitImage, SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by `name`", array(
                $date,
                $orgid
            ));
            $data['elist'] = $query->result();
        } else if ($att == 'today_abs') {
            
            
            $q2    = "select (select count(EmployeeMaster.Id)-count(AttendanceMaster.Id) from EmployeeMaster where OrganizationId =" . $orgid . ") as total, AttendanceDate from AttendanceMaster where AttendanceDate ='$date' and OrganizationId =" . $orgid . " group by AttendanceDate";
            $query = $this->db->query($q2);
            $d     = array();
            $res   = array();
            foreach ($query->result() as $row) {
                '<br/>total: ' . $row->total . '  date: ' . $row->AttendanceDate . '<br/>';
                $query1 = $this->db->query("SELECT Id as EmployeeId ,FirstName,Shift,Department,Designation, Id ,'" . $row->AttendanceDate . "' as absentdate
                            FROM `EmployeeMaster` 
                            WHERE EmployeeMaster.OrganizationId =" . $orgid . "
                             AND archive=1 and IF(EmployeeMaster.CreatedDate!='0000-00-00 00:00:00', CreatedDate < '" . $row->AttendanceDate . "', 1) and  Id 
                            NOT IN (
                            SELECT `EmployeeId` 
                            FROM `AttendanceMaster` 
                            WHERE `AttendanceDate` 
                            IN (
                             '" . $row->AttendanceDate . "'
                            )
                            AND AttendanceMaster.OrganizationId =" . $orgid . "
                            AND `AttendanceStatus` not in(3,5,6)
                            )
                            ORDER BY EmployeeMaster.Id ");
                $count  = $query1->num_rows();
                foreach ($query1->result() as $row) {
                    $data            = array();
                    //$data['name']=ucwords(getEmpName($row->Id));
                    $data['name']    = getEmpName($row->EmployeeId);
                    $data['status']  = 'Absent';
                    $data['TimeIn']  = '-';
                    $data['TimeOut'] = '-';
                    $res[]           = $data;
                }
            }
            $this->db->close();
            $data['elist'] = $res;
        } else if ($att == 'yesterday') { //yesterday attendance
            $date                 = date('Y-m-d', strtotime("-1 days"));
            $query                = $this->db->query("SELECT count(`Id`) as total FROM `EmployeeMaster`  WHERE archive=1 and `OrganizationId`=" . $orgid);
            $data['total']        = $query->result();
            $query                = $this->db->query("SELECT count(`Id`) as timedin FROM `AttendanceMaster`  WHERE AttendanceDate='" . $date . "' and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) and `OrganizationId`=" . $orgid);
            $data['timedin']      = $query->result();
            $query                = $this->db->query("select count(Id) as latecomers from AttendanceMaster where `AttendanceDate`  ='" . $date . "' and `OrganizationId`=" . $orgid . " and time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)");
            $data['latecomers']   = $query->result();
            $query                = $this->db->query("select count(Id) as earlyleavers from AttendanceMaster where `AttendanceDate`  ='" . $date . "' and `OrganizationId`=" . $orgid . " and TimeOut !='00:00:00' and time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId)");
            $data['earlyleavers'] = $query->result();
            //$query = $this->db->query("SELECT count(`EmployeeId`) as exited FROM AttendanceMaster  WHERE `AttendanceDate`  ='".$date."' and `TimeOut` !='00:00:00' and `OrganizationId`=".$orgid);
            //$data['exited']=$query->result();
            //    $query = $this->db->query("SELECT count(`EmployeeId`) as timedin FROM AttendanceMaster  WHERE `AttendanceDate`  ='".$date."' and `TimeOut` ='00:00:00' and `OrganizationId`=".$orgid);
            //    $data['timedin']=$query->result();
            //    $query = $this->db->query("SELECT count(BreakOn) as onbreak FROM `BreakMaster` where Date=? and BreakOff ='00:00:00' and OrganizationId=?",array($date,$orgid));
            //    $data['onbreak']=$query->result();
            
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,'Present' as status,EntryImage,ExitImage FROM `AttendanceMaster` WHERE (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) and `AttendanceDate`=? and  OrganizationId=? order by `name`", array(
                $date,
                $orgid
            ));
            $data['elist'] = $query->result();
            //---managing off (weekly and holiday)
            $dt            = $date;
            
            //    day of month : 1 sun 2 mon --
            $dayOfWeek   = 1 + date('w', strtotime($dt));
            $weekOfMonth = weekOfMonth($dt);
            $week        = '';
            $query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
                $orgid,
                $dayOfWeek
            ));
            if ($row = $query->result()) {
                $week = explode(",", $row[0]->WeekOff);
            }
            if ($week[$weekOfMonth - 1] == 1) {
                $data['absentees'] = '';
            } else {
                $query = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
                    $orgid,
                    $dt
                ));
                if ($query->num_rows() > 0) {
                    
                    //-----managing off (weekly and holiday) - close            
                    $query             = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status from EmployeeMaster where `OrganizationId` =$orgid and EmployeeMaster.archive=1 and EmployeeMaster.Id in(select AttendanceMaster.`EmployeeId` from AttendanceMaster where `AttendanceDate`='$date' and AttendanceStatus<>1 and `OrganizationId` =$orgid) and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift) order by `name`", array(
                        $orgid,
                        $date,
                        $orgid
                    ));
                    $data['absentees'] = $query->result();
                } else {
                    $data['absentees'] = '';
                }
            }
        } else if ($att == 'yes_late') {
            $date          = date('Y-m-d', strtotime("-1 days"));
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,'Present' as status,EntryImage,ExitImage FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by `name`", array(
                $date,
                $orgid
            ));
            $data['elist'] = $query->result();
        } else if ($att == 'yes_early') {
            $date          = date('Y-m-d', strtotime("-1 days"));
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,'Present' as status,EntryImage,ExitImage FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by `name`", array(
                $date,
                $orgid
            ));
            $data['elist'] = $query->result();
        } else if ($att == 'yes_abs') {
            $date  = date('Y-m-d', strtotime("-1 days"));
            $q2    = "select (select count(EmployeeMaster.Id)-count(AttendanceMaster.Id) from EmployeeMaster where OrganizationId =" . $orgid . ") as total, AttendanceDate from AttendanceMaster where AttendanceDate ='$date' and OrganizationId =" . $orgid . " group by AttendanceDate";
            $query = $this->db->query($q2);
            $d     = array();
            $res   = array();
            foreach ($query->result() as $row) {
                '<br/>total: ' . $row->total . '  date: ' . $row->AttendanceDate . '<br/>';
                $query1 = $this->db->query("SELECT Id as EmployeeId ,FirstName,Shift,Department,Designation, Id ,'" . $row->AttendanceDate . "' as absentdate
                            FROM `EmployeeMaster` 
                            WHERE EmployeeMaster.OrganizationId =" . $orgid . "
                             AND archive=1 and IF(EmployeeMaster.CreatedDate!='0000-00-00 00:00:00', CreatedDate < '" . $row->AttendanceDate . "', 1) and  Id 
                            NOT IN (
                            SELECT `EmployeeId` 
                            FROM `AttendanceMaster` 
                            WHERE `AttendanceDate` 
                            IN (
                             '" . $row->AttendanceDate . "'
                            )
                            AND AttendanceMaster.OrganizationId =" . $orgid . "
                            AND `AttendanceStatus` not in(3,5,6)
                            )
                            ORDER BY EmployeeMaster.Id ");
                $count  = $query1->num_rows();
                foreach ($query1->result() as $row) {
                    $data            = array();
                    //$data['name']=ucwords(getEmpName($row->Id));
                    $data['name']    = getEmpName($row->EmployeeId);
                    $data['status']  = 'Absent';
                    $data['TimeIn']  = '-';
                    $data['TimeOut'] = '-';
                    $res[]           = $data;
                }
            }
            $this->db->close();
            $data['elist'] = $res;
        } else if ($att == 'cdate') { //custom date  attendance
            $cdate = isset($_REQUEST['cdate']) ? date('Y-m-d', strtotime($_REQUEST['cdate'])) : 0;
            $cond  = '';
            if ($cdate == $date)
                $cond = "    and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift)";
            $query                = $this->db->query("SELECT count(`Id`) as total FROM `EmployeeMaster`  WHERE `OrganizationId`=" . $orgid . " and archive=1 " . $cond);
            $data['total']        = $query->result();
            $query                = $this->db->query("SELECT count(`EmployeeId`) as marked FROM AttendanceMaster  WHERE AttendanceDate  ='" . $cdate . "'  and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) and `OrganizationId`=" . $orgid);
            $data['marked']       = $query->result();
            $query                = $this->db->query("select count(Id) as latecomers from AttendanceMaster where `AttendanceDate`  ='" . $cdate . "' and `OrganizationId`=" . $orgid . " and time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)");
            $data['latecomers']   = $query->result();
            $query                = $this->db->query("select count(Id) as earlyleavers from AttendanceMaster where `AttendanceDate`  ='" . $cdate . "' and `OrganizationId`=" . $orgid . " and TimeOut !='00:00:00' and time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId)");
            $data['earlyleavers'] = $query->result();
            $query                = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut`,'Present' as status ,AttendanceDate FROM `AttendanceMaster` WHERE AttendanceDate =? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) and  OrganizationId=? order by `AttendanceDate` desc,name", array(
                $cdate,
                $orgid
            ));
            $data['elist']        = $query->result();
            //---managing off (weekly and holiday)// 
            $dt                   = $cdate;
            
            //    day of month : 1 sun 2 mon --
            $dayOfWeek   = 1 + date('w', strtotime($dt));
            $weekOfMonth = weekOfMonth($dt);
            $week        = '';
            $query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
                $orgid,
                $dayOfWeek
            ));
            if ($row = $query->result()) {
                $week = explode(",", $row[0]->WeekOff);
            }
            if ($week[$weekOfMonth - 1] == 1) {
                $data['absentees'] = '';
            } else {
                $query = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
                    $orgid,
                    $dt
                ));
                if ($query->num_rows() == 0) {
                    //-----managing off (weekly and holiday) - close
                    $query             = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status,'" . $cdate . "' as AttendanceDate from EmployeeMaster where `OrganizationId` =? and EmployeeMaster.archive=1 and EmployeeMaster.Id in(select AttendanceMaster.`EmployeeId` from AttendanceMaster where `AttendanceDate`=? and AttendanceStatus<>1 and `OrganizationId` =?) " . $cond . " order by `name`", array(
                        $orgid,
                        $cdate,
                        $orgid
                    ));
                    //$query = $this->db->query("select * from AttendanceMaster where AttendanceStatus<>1 and AttendanceDate='2018-01-01'",array());
                    $data['absentees'] = $query->result();
                } else {
                    $data['absentees'] = '';
                }
            }
        } else if ($att == 'cd_late') {
            $date          = isset($_REQUEST['cdate']) ? date('Y-m-d', strtotime($_REQUEST['cdate'])) : 0;
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,'Present' as status,EntryImage,ExitImage FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by `name`", array(
                $date,
                $orgid
            ));
            $data['elist'] = $query->result();
        } else if ($att == 'cd_early') {
            $date          = isset($_REQUEST['cdate']) ? date('Y-m-d', strtotime($_REQUEST['cdate'])) : 0;
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,'Present' as status,EntryImage,ExitImage FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8)order by `name`", array(
                $date,
                $orgid
            ));
            $data['elist'] = $query->result();
        } else if ($att == 'cd_abs') {
            $date  = isset($_REQUEST['cdate']) ? date('Y-m-d', strtotime($_REQUEST['cdate'])) : 0;
            $q2    = "select (select count(EmployeeMaster.Id)-count(AttendanceMaster.Id) from EmployeeMaster where OrganizationId =" . $orgid . ") as total, AttendanceDate from AttendanceMaster where AttendanceDate ='$date' and OrganizationId =" . $orgid . " group by AttendanceDate";
            $query = $this->db->query($q2);
            $d     = array();
            $res   = array();
            foreach ($query->result() as $row) {
                '<br/>total: ' . $row->total . '  date: ' . $row->AttendanceDate . '<br/>';
                $query1 = $this->db->query("SELECT Id as EmployeeId ,FirstName,Shift,Department,Designation, Id ,'" . $row->AttendanceDate . "' as absentdate
                            FROM `EmployeeMaster` 
                            WHERE EmployeeMaster.OrganizationId =" . $orgid . "
                             AND archive=1 and IF(EmployeeMaster.CreatedDate!='0000-00-00 00:00:00', CreatedDate < '" . $row->AttendanceDate . "', 1) and  Id 
                            NOT IN (
                            SELECT `EmployeeId` 
                            FROM `AttendanceMaster` 
                            WHERE `AttendanceDate` 
                            IN (
                             '" . $row->AttendanceDate . "'
                            )
                            AND AttendanceMaster.OrganizationId =" . $orgid . "
                            AND `AttendanceStatus` not in(3,5,6)
                            )
                            ORDER BY EmployeeMaster.Id ");
                $count  = $query1->num_rows();
                foreach ($query1->result() as $row) {
                    $data            = array();
                    //$data['name']=ucwords(getEmpName($row->Id));
                    $data['name']    = getEmpName($row->EmployeeId);
                    $data['status']  = 'Absent';
                    $data['TimeIn']  = '-';
                    $data['TimeOut'] = '-';
                    $res[]           = $data;
                }
            }
            $this->db->close();
            $data['elist'] = $res;
        } else if ($att == 'absentees') { //custom date  absentees
            $cdate               = isset($_REQUEST['cdate']) ? date('Y-m-d', strtotime($_REQUEST['cdate'])) : 0;
            ////////////////
            $query               = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status,'" . $cdate . "' as AttendanceDate  from EmployeeMaster where `OrganizationId` =" . $orgid . " and EmployeeMaster.Id not in(select AttendanceMaster.`EmployeeId` from AttendanceMaster WHERE `AttendanceDate`  ='" . $cdate . "' and `OrganizationId`=" . $orgid . " and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift) order by DATE(AttendanceDate),name)");
            $data['absentees'][] = $query->result();
            ////////////////
            $query               = $this->db->query("SELECT count(`Id`) as total FROM `EmployeeMaster`  WHERE `OrganizationId`=" . $orgid);
            $data['total']       = $query->result();
            $query               = $this->db->query("SELECT count(`EmployeeId`) as marked FROM AttendanceMaster  WHERE AttendanceDate  ='" . $cdate . "' and `OrganizationId`=" . $orgid);
            $data['marked']      = $query->result();
            $query               = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status,'" . $cdate . "' as AttendanceDate  from EmployeeMaster where `OrganizationId` =" . $orgid . " and EmployeeMaster.Id not in(select AttendanceMaster.`EmployeeId` from AttendanceMaster WHERE `AttendanceDate`  ='" . $cdate . "' and `OrganizationId`=" . $orgid . "  and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by DATE(AttendanceDate),name)");
            $data['elist']       = $query->result();
        } else if ($att == 'l7') { //last 7 days attendance
            $end_week   = date("Y-m-d", strtotime("-1 days"));
            $start_week = date("Y-m-d", strtotime('-6 day', strtotime($end_week)));
            $start_week = \DateTime::createFromFormat('Y-m-d', $start_week);
            $end_week   = \DateTime::createFromFormat('Y-m-d', $end_week);
            $datePeriod = new \DatePeriod($start_week, new \DateInterval('P1D'), $end_week->modify('+1day'));
            foreach ($datePeriod as $date) {
                $dt              = $date->format('Y-m-d');
                $query           = $this->db->query("SELECT count(`EmployeeId`) as total,AttendanceDate FROM AttendanceMaster WHERE `AttendanceDate`  ='" . $dt . "' and AttendanceStatus<>1 and `OrganizationId`=" . $orgid);
                $data['rec'][]   = $query->result();
                $query           = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,AttendanceDate ,'Present' as status FROM `AttendanceMaster` WHERE `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid . " and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by DATE(AttendanceDate) desc,name");
                $data['elist'][] = $query->result();
                ///////////////abs
                
                //---managing off (weekly and holiday)
                $query = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
                    $orgid,
                    $dt
                ));
                if ($query->num_rows() > 0)
                    continue;
                //    day of month : 1 sun 2 mon --
                $dayOfWeek   = 1 + date('w', strtotime($dt));
                $weekOfMonth = weekOfMonth($dt);
                $week        = '';
                $query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
                    $orgid,
                    $dayOfWeek
                ));
                if ($row = $query->result()) {
                    $week = explode(",", $row[0]->WeekOff);
                }
                if ($week[$weekOfMonth - 1] == 1)
                    continue;
                //-----managing off (weekly and holiday) - close
                $query               = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status,'" . $dt . "' as AttendanceDate  from EmployeeMaster where `OrganizationId` =" . $orgid . " and EmployeeMaster.archive=1 and EmployeeMaster.Id in(select AttendanceMaster.`EmployeeId` from AttendanceMaster WHERE `AttendanceDate`  ='" . $dt . "' and AttendanceStatus<>1 and `OrganizationId`=" . $orgid . ") and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift) order by DATE(AttendanceDate),name");
                $data['absentees'][] = $query->result();
                
                //////////abs
                
                
            }
        } else if ($att == 'l7_late') {
            $end_week   = date("Y-m-d", strtotime("-1 days"));
            $start_week = date("Y-m-d", strtotime('-6 day', strtotime($end_week)));
            $start_week = \DateTime::createFromFormat('Y-m-d', $start_week);
            $end_week   = \DateTime::createFromFormat('Y-m-d', $end_week);
            $datePeriod = new \DatePeriod($start_week, new \DateInterval('P1D'), $end_week->modify('+1day'));
            $res        = array();
            foreach ($datePeriod as $date) {
                $data1 = array();
                $dt    = $date->format('Y-m-d');
                $query = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,AttendanceDate ,'Present' as status,EntryImage,ExitImage FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid . " and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by DATE(AttendanceDate) desc,name");
                $res[] = $query->result();
            }
            $data['elist'] = $res;
            
        } else if ($att == 'l7_early') {
            $end_week   = date("Y-m-d", strtotime("-1 days"));
            $start_week = date("Y-m-d", strtotime('-6 day', strtotime($end_week)));
            $start_week = \DateTime::createFromFormat('Y-m-d', $start_week);
            $end_week   = \DateTime::createFromFormat('Y-m-d', $end_week);
            $datePeriod = new \DatePeriod($start_week, new \DateInterval('P1D'), $end_week->modify('+1day'));
            $res        = array();
            foreach ($datePeriod as $date) {
                $data1 = array();
                $dt    = $date->format('Y-m-d');
                $query = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,AttendanceDate ,'Present' as status,EntryImage,ExitImage FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid . " and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by DATE(AttendanceDate) desc,name");
                $res[] = $query->result();
            }
            $data['elist'] = $res;
        } else if ($att == 'l7_abs') {
            $end_week   = date("Y-m-d", strtotime("-1 days"));
            $start_week = date("Y-m-d", strtotime('-6 day', strtotime($end_week)));
            $start_week = \DateTime::createFromFormat('Y-m-d', $start_week);
            $end_week   = \DateTime::createFromFormat('Y-m-d', $end_week);
            $datePeriod = new \DatePeriod($start_week, new \DateInterval('P1D'), $end_week->modify('+1day'));
            
            $res = array();
            foreach ($datePeriod as $date) {
                $data1 = array();
                $dt    = $date->format('Y-m-d');
                $q2    = "select (select count(EmployeeMaster.Id)-count(AttendanceMaster.Id) from EmployeeMaster where OrganizationId =" . $orgid . ") as total, AttendanceDate from AttendanceMaster where AttendanceDate ='$dt' and OrganizationId =" . $orgid . " group by AttendanceDate";
                $query = $this->db->query($q2);
                $d     = array();
                //$res=array();
                foreach ($query->result() as $row) {
                    $date   = $row->AttendanceDate;
                    // '<br/>total: '.$row->total.'  date: '.$row->AttendanceDate .'<br/>';
                    $query1 = $this->db->query("SELECT Id as EmployeeId ,FirstName,Shift,Department,Designation, Id ,'" . $row->AttendanceDate . "' as absentdate
                            FROM `EmployeeMaster` 
                            WHERE EmployeeMaster.OrganizationId =" . $orgid . "
                             AND archive=1 and IF(EmployeeMaster.CreatedDate!='0000-00-00 00:00:00', CreatedDate < '" . $row->AttendanceDate . "', 1) and  Id 
                            NOT IN (
                            SELECT `EmployeeId` 
                            FROM `AttendanceMaster` 
                            WHERE `AttendanceDate` 
                            IN (
                             '" . $row->AttendanceDate . "'
                            )
                            AND AttendanceMaster.OrganizationId =" . $orgid . "
                            AND `AttendanceStatus` not in(3,5,6)
                            )
                            ORDER BY EmployeeMaster.Id ");
                    $count  = $query1->num_rows();
                    foreach ($query1->result() as $row) {
                        $data                   = array();
                        //$data['name']=ucwords(getEmpName($row->Id));
                        $data['name']           = getEmpName($row->EmployeeId);
                        $data['AttendanceDate'] = $date;
                        $data['status']         = 'Absent';
                        $data['TimeIn']         = '-';
                        $data['TimeOut']        = '-';
                        $res[]                  = $data;
                    }
                }
                //$res1[]=$query->result();
            }
            $this->db->close();
            $data['elist'] = $res;
        } else if ($att == 'thismonth') { //current month attendance
            $month                = date('m');
            $year                 = date('Y');
            $query                = $this->db->query("SELECT count(`Id`) as total FROM `EmployeeMaster`  WHERE `OrganizationId`=" . $orgid);
            $data['total']        = $query->result();
            $query                = $this->db->query("SELECT count(`EmployeeId`) as marked FROM AttendanceMaster  WHERE EXTRACT(MONTH from AttendanceDate)  ='" . $month . "' and EXTRACT(YEAR from AttendanceDate)  ='" . $year . "' and  `OrganizationId`=" . $orgid);
            $data['marked']       = $query->result();
            $query                = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut`,'Present' as status ,AttendanceDate FROM `AttendanceMaster` WHERE EXTRACT(MONTH from AttendanceDate) =?  and  EXTRACT(YEAR from AttendanceDate)  =? and OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by DATE(AttendanceDate),name", array(
                $month,
                $year,
                $orgid
            ));
            $data['elist']        = $query->result();
            $query                = $this->db->query("select count(Id) as latecomers from AttendanceMaster where EXTRACT(MONTH from AttendanceDate) ='" . $month . "' and `OrganizationId`=" . $orgid . " and time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)");
            $data['latecomers']   = $query->result();
            $query                = $this->db->query("select count(Id) as earlyleavers from AttendanceMaster where EXTRACT(MONTH from AttendanceDate) ='" . $month . "' and `OrganizationId`=" . $orgid . " and TimeOut !='00:00:00' and time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId)");
            $data['earlyleavers'] = $query->result();
            $start_week           = date('Y-m-01');
            $end_week             = date('Y-m-d');
            $start_week           = \DateTime::createFromFormat('Y-m-d', $start_week);
            $end_week             = \DateTime::createFromFormat('Y-m-d', $end_week);
            $datePeriod           = new \DatePeriod($start_week, new \DateInterval('P1D'), $end_week->modify('+1day'));
            foreach ($datePeriod as $date) {
                $dt    = $date->format('Y-m-d');
                //---managing off (weekly and holiday)
                $query = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
                    $orgid,
                    $dt
                ));
                if ($query->num_rows() > 0)
                    continue;
                //    day of month : 1 sun 2 mon --
                $dayOfWeek   = 1 + date('w', strtotime($dt));
                $weekOfMonth = weekOfMonth($dt);
                $week        = '';
                $query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
                    $orgid,
                    $dayOfWeek
                ));
                if ($row = $query->result()) {
                    $week = explode(",", $row[0]->WeekOff);
                }
                if ($week[$weekOfMonth - 1] == 1)
                    continue;
                //-----managing off (weekly and holiday) - close
                $query               = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status,'" . $dt . "' as AttendanceDate  from EmployeeMaster where `OrganizationId` =" . $orgid . " and EmployeeMaster.archive=1 and EmployeeMaster.Id in(select AttendanceMaster.`EmployeeId` from AttendanceMaster WHERE `AttendanceDate`  ='" . $dt . "'  and AttendanceStatus<>1 and `OrganizationId`=" . $orgid . ") and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift) order by DATE(AttendanceDate),name asc");
                $data['absentees'][] = $query->result();
            }
        } else if ($att == 'tm_late') {
            $start_week = date('Y-m-01');
            $end_week   = date('Y-m-d');
            $start_week = \DateTime::createFromFormat('Y-m-d', $start_week);
            $end_week   = \DateTime::createFromFormat('Y-m-d', $end_week);
            $datePeriod = new \DatePeriod($start_week, new \DateInterval('P1D'), $end_week->modify('+1day'));
            $res        = array();
            foreach ($datePeriod as $date) {
                $data1 = array();
                $dt    = $date->format('Y-m-d');
                $query = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,AttendanceDate ,'Present' as status,EntryImage,ExitImage FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid . " and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by DATE(AttendanceDate) desc,name");
                $res[] = $query->result();
            }
            $data['elist'] = $res;
            
        } else if ($att == 'tm_early') {
            $start_week = date('Y-m-01');
            $end_week   = date('Y-m-d');
            $start_week = \DateTime::createFromFormat('Y-m-d', $start_week);
            $end_week   = \DateTime::createFromFormat('Y-m-d', $end_week);
            $datePeriod = new \DatePeriod($start_week, new \DateInterval('P1D'), $end_week->modify('+1day'));
            $res        = array();
            foreach ($datePeriod as $date) {
                $data1 = array();
                $dt    = $date->format('Y-m-d');
                $query = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,AttendanceDate ,'Present' as status,EntryImage,ExitImage FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid . " and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by DATE(AttendanceDate) desc,name");
                $res[] = $query->result();
            }
            $data['elist'] = $res;
        } else if ($att == 'tm_abs') {
            $start_week = date('Y-m-01');
            $end_week   = date('Y-m-d');
            $start_week = \DateTime::createFromFormat('Y-m-d', $start_week);
            $end_week   = \DateTime::createFromFormat('Y-m-d', $end_week);
            $datePeriod = new \DatePeriod($start_week, new \DateInterval('P1D'), $end_week->modify('+1day'));
            $res        = array();
            foreach ($datePeriod as $date) {
                $data1 = array();
                $dt    = $date->format('Y-m-d');
                $q2    = "select (select count(EmployeeMaster.Id)-count(AttendanceMaster.Id) from EmployeeMaster where OrganizationId =" . $orgid . ") as total, AttendanceDate from AttendanceMaster where AttendanceDate ='$dt' and OrganizationId =" . $orgid . " group by AttendanceDate";
                $query = $this->db->query($q2);
                $d     = array();
                //$res=array();
                foreach ($query->result() as $row) {
                    $date   = $row->AttendanceDate;
                    // '<br/>total: '.$row->total.'  date: '.$row->AttendanceDate .'<br/>';
                    $query1 = $this->db->query("SELECT Id as EmployeeId ,FirstName,Shift,Department,Designation, Id ,'" . $row->AttendanceDate . "' as absentdate
                            FROM `EmployeeMaster` 
                            WHERE EmployeeMaster.OrganizationId =" . $orgid . "
                             AND archive=1 and IF(EmployeeMaster.CreatedDate!='0000-00-00 00:00:00', CreatedDate < '" . $row->AttendanceDate . "', 1) and  Id 
                            NOT IN (
                            SELECT `EmployeeId` 
                            FROM `AttendanceMaster` 
                            WHERE `AttendanceDate` 
                            IN (
                             '" . $row->AttendanceDate . "'
                            )
                            AND AttendanceMaster.OrganizationId =" . $orgid . "
                            AND `AttendanceStatus` not in(3,5,6)
                            )
                            ORDER BY EmployeeMaster.Id ");
                    $count  = $query1->num_rows();
                    foreach ($query1->result() as $row) {
                        $data                   = array();
                        //$data['name']=ucwords(getEmpName($row->Id));
                        $data['name']           = getEmpName($row->EmployeeId);
                        $data['AttendanceDate'] = $date;
                        $data['status']         = 'Absent';
                        $data['TimeIn']         = '-';
                        $data['TimeOut']        = '-';
                        $res[]                  = $data;
                    }
                }
                //$res1[]=$query->result();
            }
            $this->db->close();
            $data['elist'] = $res;
        } else if ($att == 'lastweek') { //lasweek attendance
            $previous_week = strtotime("-1 week +1 day");
            $start_week    = strtotime("last monday midnight", $previous_week);
            $end_week      = strtotime("next sunday", $start_week);
            $start_week    = date("Y-m-d", $start_week);
            $end_week      = date("Y-m-d", $end_week);
            $start_week    = \DateTime::createFromFormat('Y-m-d', $start_week);
            $end_week      = \DateTime::createFromFormat('Y-m-d', $end_week);
            $datePeriod    = new \DatePeriod($start_week, new \DateInterval('P1D'), $end_week->modify('+1day'));
            foreach ($datePeriod as $date) {
                $dt              = $date->format('Y-m-d');
                $query           = $this->db->query("SELECT count(`EmployeeId`) as total,AttendanceDate FROM AttendanceMaster  WHERE `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid);
                $data['rec'][]   = $query->result();
                $query           = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,AttendanceDate,'Present' as status FROM `AttendanceMaster` WHERE `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid . " and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by DATE(AttendanceDate),name ");
                $data['elist'][] = $query->result();
                //---managing off (weekly and holiday)
                $query           = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
                    $orgid,
                    $dt
                ));
                if ($query->num_rows() > 0)
                    continue;
                //    day of month : 1 sun 2 mon --
                $dayOfWeek   = 1 + date('w', strtotime($dt));
                $weekOfMonth = weekOfMonth($dt);
                $week        = '';
                $query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
                    $orgid,
                    $dayOfWeek
                ));
                if ($row = $query->result()) {
                    $week = explode(",", $row[0]->WeekOff);
                }
                if ($week[$weekOfMonth - 1] == 1)
                    continue;
                //-----managing off (weekly and holiday) - close
                $query               = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status,'" . $dt . "' as AttendanceDate  from EmployeeMaster where `OrganizationId` =" . $orgid . " and EmployeeMaster.Id not in(select AttendanceMaster.`EmployeeId` from AttendanceMaster WHERE `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid . ") order by DATE(AttendanceDate),name");
                $data['absentees'][] = $query->result();
            }
            //$data['elist']=$query->result();
        }
        echo json_encode($data, JSON_NUMERIC_CHECK);
        
    }
    public function getIndivisualReportData()
    {
        return true;
    }
    public function getLateComings()
    {
        $org_id = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $date   = isset($_REQUEST['cdate']) ? $_REQUEST['cdate'] : 0;
        $res    = array();
        $date   = date('Y-m-d', strtotime($date));
        $query  = $this->db->query("select Shift,Id  from EmployeeMaster where OrganizationId = $org_id and Id IN (select EmployeeId from AttendanceMaster where OrganizationId = $org_id and AttendanceDate='$date' and TimeIn != '00:00:00') AND is_Delete = 0 order by FirstName");
        foreach ($query->result() as $row) {
            $ShiftId = $row->Shift;
            $EId     = $row->Id;
            $query   = $this->db->query("select TimeIn,TimeOut from ShiftMaster where Id = $ShiftId");
            if ($data123 = $query->row()) {
                $shiftin  = $data123->TimeIn;
                $shift    = substr($data123->TimeIn, 0, 5) . ' - ' . substr($data123->TimeOut, 0, 5);
                $ct       = date('H:i:s');
                $query111 = $this->db->query("select * from EmployeeMaster where OrganizationId = $org_id  and Id =$EId");
                if ($row111 = $query111->row()) {
                    $query333 = $this->db->query("select * from AttendanceMaster where OrganizationId = $org_id  and EmployeeId =$EId and TimeIn > '$shiftin' and AttendanceDate='$date'");
                    if ($row333 = $query333->row()) {
                        $a              = new DateTime($row333->TimeIn);
                        $b              = new DateTime($data123->TimeIn);
                        $interval       = $a->diff($b);
                        $data['lateby'] = $interval->format("%H:%I");
                        $data['timein'] = substr($row333->TimeIn, 0, 5);
                        $data['name']   = $row111->FirstName . ' ' . $row111->LastName;
                        $data['shift']  = $shift;
                        $data['date']   = $date;
                        $res[]          = $data;
                    }
                }
            }
        }
        
        echo json_encode($res, JSON_NUMERIC_CHECK);
    }
    public function getEarlyLeavings()
    {
        $org_id = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $date   = isset($_REQUEST['cdate']) ? $_REQUEST['cdate'] : 0;
        $zone   = getTimeZone($org_id);
        date_default_timezone_set($zone);
        $res   = array();
        $date  = date('Y-m-d', strtotime($date));
        $cdate = date('Y-m-d');
        $time  = date('H:i:s');
        $cond  = '';
        $query = $this->db->query("select Shift,Id  from EmployeeMaster where OrganizationId = $org_id and Id IN (select EmployeeId from AttendanceMaster where OrganizationId = $org_id and AttendanceDate='$date' and TimeIn != '00:00:00') AND is_Delete=0 order by FirstName");
        foreach ($query->result() as $row) {
            $ShiftId = $row->Shift;
            $EId     = $row->Id;
            $query   = $this->db->query("select TimeIn,TimeOut,shifttype from ShiftMaster where Id = $ShiftId");
            if ($data123 = $query->row()) {
                $shiftout = $data123->TimeOut;
                $shiftout1 = $date. ' '.$data123->TimeOut;
				if($data123->shifttype==2)
				{
					$nextdate = date('Y-m-d',strtotime($date . "+1 days"));
					 $shiftout1 = $nextdate.' '.$data123->TimeOut;
				}
                $shift    = substr($data123->TimeIn, 0, 5) . ' - ' . substr($data123->TimeOut, 0, 5);
                $ct       = date('H:i:s');
                $query111 = $this->db->query("select FirstName , LastName from EmployeeMaster where OrganizationId = $org_id  and Id =$EId");
                if ($row111 = $query111->row()) {
                    if ($cdate == $date)
                        $cond = "    and TimeOut !='00:00:00'";
                    $query333 = $this->db->query("select TimeOut from AttendanceMaster where OrganizationId = $org_id  and EmployeeId =$EId and if(timeoutdate = '0000-00-00' , TimeOut  <  '$shiftout' , CONCAT(timeoutdate,' ' ,TimeOut)  <  '$shiftout1' ) and AttendanceDate='$date'" . $cond);
                    if ($row333 = $query333->row()) {
                        $a               = new DateTime($row333->TimeOut);
                        $b               = new DateTime($data123->TimeOut);
                        $interval        = $a->diff($b);
                        $data['earlyby'] = $interval->format("%H:%I");
                        $data['timeout'] = substr($row333->TimeOut, 0, 5);
                        ;
                        $data['name']  = $row111->FirstName . ' ' . $row111->LastName;
                        $data['shift'] = $shift;
                        $data['date']  = $date;
                        $res[]         = $data;
                    }
                }
            }
        }
        
        echo json_encode($res, JSON_NUMERIC_CHECK);
    }
    
    
    public function getBreakInfo()
    {
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $today       = date('Y-m-d');
        //SELECT `Id`, `EmployeeId`, `TimeofDate`, `TimeFrom`, `TimeTo`, `Reason`, `ApproverId`, `ApprovalSts`, `ApproverComment`, `CreatedDate`, `ModifiedDate`, `OrganizationId` FROM `Timeoff` WHERE 1
        $query       = $this->db->query("SELECT `Id`,TimeofDate as Date, TimeFrom as `BreakOn`, TimeTo as `BreakOff`, `OrganizationId` FROM `Timeoff` WHERE EmployeeId=? and OrganizationId=? and TimeofDate=? order by Id desc limit 1", array(
            $uid,
            $orgid,
            $today
        ));
        $data        = array();
        $data['id']  = '';
        $data['stb'] = '';
        $data['sts'] = 0;
        foreach ($query->result() as $row) {
            $data['id'] = $row->Id;
            if (($row->BreakOn != '00:00:00' or $row->BreakOn != '') and ( $row->BreakOff == '00:00:00' or $row->BreakOff == '')) {
                $data['sts'] = 1; // Timed in but not timed out
                $data['stb'] = $row->BreakOn;
            }
        } 
        echo json_encode($data);
    }
    
    public function timeBreak()
    {
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $id    = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $today = date('Y-m-d');
        $time  = date("H:i:s");
        ;
        $query = $this->db->query("SELECT `Id`,TimeFrom as BreakOn,TimeTo as BreakOff FROM `Timeoff` WHERE EmployeeId=? and OrganizationId=? and TimeofDate=? order by Id desc limit 1", array(
            $uid,
            $orgid,
            $today
        ));
        $data  = array();
        $sts   = 0;
        $res   = 0;
        if ($query->num_rows() > 0) {
            $row        = $query->result();
            $data['id'] = $row[0]->Id;
            //echo $row[0]->BreakOn."--".$row[0]->BreakOff.'--';
            if (($row[0]->BreakOn != '00:00:00' or $row[0]->BreakOn != '') and ($row[0]->BreakOff == '00:00:00' or $row[0]->BreakOff == '')) {
                $sts = 1; // Timed in but not timed out
            }
        }
        
        if ($sts == 0) { // time to marke start time off
            
            //    $query = $this->db->query("INSERT INTO `BreakMaster`(`EmployeeId`, `Date`, `BreakOn`, `OrganizationId`) VALUES (?,?,?,?)",array($uid,$today,$time,$orgid));
            $query = $this->db->query("INSERT INTO `Timeoff`(`EmployeeId`, `TimeofDate`, `TimeFrom`, `OrganizationId`,ApprovalSts,CreatedDate) VALUES (?,?,?,?,?,?)", array(
                $uid,
                $today,
                $time,
                $orgid,
                2,
                $today
            ));
            if ($this->db->affected_rows() > 0)
                $res = 1;
        } else { // time to mark stop time off
            //    $query = $this->db->query("UPDATE `BreakMaster` SET `BreakOff`=? WHERE id=? ",array($time,$id));
            $query = $this->db->query("UPDATE `Timeoff` SET `TimeTo`=? WHERE id=? ", array(
                $time,
                $id
            ));
            if ($this->db->affected_rows() > 0)
                $res = 2;
        }
        echo json_encode($res);
    }
    public function changePassword()
    {
        $uid         = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid       = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $pwd         = encode5t(isset($_REQUEST['pwd']) ? $_REQUEST['pwd'] : '');
        $npwd        = encode5t(isset($_REQUEST['npwd']) ? $_REQUEST['npwd'] : '');
        $data        = array();
        $res = 0;
        $zone        = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $today = date('Y-m-d');
        $query = $this->db->query("select * from UserMaster where EmployeeId=? and BINARY Password=? and OrganizationId=?", array(
            $uid,
            $pwd,
            $orgid
        ));
        if ($this->db->affected_rows() < 1) 
            $res = 2; // password not matched
        else { // old password matched
            if ($pwd == $npwd) 
                $res = 3; // new password and old password are same
        }
         if($res==0){
        $query = $this->db->query("UPDATE `UserMaster` SET `Password`=?  WHERE EmployeeId=? and OrganizationId=?", array( $npwd, $uid, $orgid ));
        if ($this->db->affected_rows() > 0) 
        
            $res = 1; // password updated
            
             $date = date("y-m-d H:i:s");
             $id = $uid;
            $module = "Attendance app";
            $actionperformed = " <b> Password </b>  has been updated for <b>".getEmpName($uid)."</b> from <b> Attendance App </b>";
             $activityby = 1;
           $query = $this->db->query("INSERT INTO ActivityHistoryMaster( LastModifiedDate,LastModifiedById,Module, ActionPerformed, OrganizationId,ActivityBy,adminid) VALUES (?,?,?,?,?,?,?)",array($date,$id,$module,$actionperformed,$orgid,$activityby,$id));
            
        }
        echo $res;
    }


  /////////////////////////////////////////////////sgCode///////////////////////////////////////////


    public function firstPassword()
    {
        $uid         = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid       = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $newpwd         = encode5t(isset($_REQUEST['newpwd']) ? $_REQUEST['newpwd'] : '');
        $password_sts        = isset($_REQUEST['password_sts']) ? $_REQUEST['password_sts'] : '';
        $data        = array();
        $res = 0;
        $zone        = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $today = date('Y-m-d');
        
       
        $query = $this->db->query("UPDATE `UserMaster` SET `Password_sts`= '1' , `Password`=? WHERE EmployeeId=? and OrganizationId=?", array(     $newpwd, $uid, $orgid ));
         if ($this->db->affected_rows() > 0) 
        
      		$res = 1; // password updated
            
        //      $date = date("y-m-d H:i:s");
        //      $id = $uid;
        //     $module = "Attendance app";
        //     $actionperformed = " <b> Password </b>  has been updated for <b>".getEmpName($uid)."</b> from <b> Attendance App </b>";
        //      $activityby = 1;
        //    $query = $this->db->query("INSERT INTO ActivityHistoryMaster( LastModifiedDate,LastModifiedById,Module, ActionPerformed, OrganizationId,ActivityBy,adminid) VALUES (?,?,?,?,?,?,?)",array($date,$id,$module,$actionperformed,$orgid,$activityby,$id));
            
        
        echo $res;
    }



    public function getProfile()
    {
		$desg = "";
		$dept = "";
        $uid                    = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $query                  = $this->db->query("SELECT `Id`,`FirstName`,ImageName,`LastName`,`MaritalStatus`,`HomeAddress`,`PersonalNo`,Department,Designation  ,Shift,CurrentCountry,OrganizationId FROM `EmployeeMaster` WHERE `Id`=?", array(
            $uid
        ));
        $data                   = array();
        $data['info']           = $query->result();
        $query                  = $this->db->query("SELECT `DisplayName`, `ActualValue` FROM `OtherMaster` WHERE `OtherType`='MaritalStatus'");
        $data['marital']        = $query->result();
        $msts                   = $data['info'][0]->MaritalStatus;
		
		$dept = $data['info'][0]->Department!='0'?getDepartment($data['info'][0]->Department):'';
		
		if (strlen($dept) > 16)
				$data['dept'] = substr($dept, 0, 16) . '..';
			else
				$data['dept'] = $dept;
		

		$desg = $data['info'][0]->Designation!=0?getDesignation($data['info'][0]->Designation):'';
		
		if (strlen($desg) > 16)
				$data['desg'] = substr($desg, 0, 16) . '..';
			else
				$data['desg'] = $desg;
        
		
        $data['shift']          = $data['info'][0]->Shift!=0?getShift($data['info'][0]->Shift):'';
        $data['PersonalNo']     = decode5t($data['info'][0]->PersonalNo);
        $data['HomeAddress']    = decode5t($data['info'][0]->HomeAddress);
		$data['shifttiming']	= $data['info'][0]->Shift!=0?getShiftTimes($data['info'][0]->Shift):'';
        $data['CurrentCountry'] = $data['info'][0]->CurrentCountry;
        $query                  = $this->db->query("SELECT `Id`, `Name`,countryCode FROM `CountryMaster` order by Name");
        $data['country']        = $query->result();
		if($data['CurrentCountry'] != '0')
		  $data['countryCode']     = getCountryCodeById1($data['CurrentCountry']);
	     else
		 {
			          $countryid =    getCountryIdByOrg($data['info'][0]->OrganizationId);
					  $data['countryCode']     =  getCountryCodeById1($countryid);
		 }
        echo json_encode($data);
    }
	public function getAttendancees()
    {
        $orgid                    = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : 0;
        $zone        = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $today = date('Y-m-d');
		$query                  = $this->db->query("
		select Id,
		concat(FirstName,' ',LastName) as Name,
		Shift,
		Designation,
		Department 
		from EmployeeMaster where OrganizationId=$orgid and 
		archive=1 and 
		Id not in( select EmployeeId from AttendanceMaster where AttendanceDate='$today' and OrganizationId='$orgid')");
        echo json_encode($query->result());
    }
	
    public function updateProfile()
    {
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $no    = isset($_REQUEST['no']) ? encode5t($_REQUEST['no']) : 0;
        //$mar   = isset($_REQUEST['mar']) ? $_REQUEST['mar'] : 0; // marrital status -- eliminated
        $con   = isset($_REQUEST['con']) ? $_REQUEST['con'] : 0;
        //$ccon  = isset($_REQUEST['ccon']) ? $_REQUEST['ccon'] : '0';
       // $add   = isset($_REQUEST['add']) ? encode5t($_REQUEST['add']) : 0;
        $res   = 0;
		$count = 0;
		 if ($no != '') {
            $sql = "SELECT * FROM UserMaster where username_mobile = '" . $no . "' ";
            $this->db->query($sql);
            $count = $this->db->affected_rows();
        }
		if($count == 0){
		
        //, CurrentCountry=?,countrycode=?
        $query = $this->db->query("update EmployeeMaster set `PersonalNo`= ? WHERE `Id`=? and OrganizationId=? ", array(            
            $no,
            $uid,
            $orgid
        ));//$con,$ccon,
        $res   = $this->db->affected_rows();
        if ($res)
            $query = $this->db->query("update UserMaster set username_mobile=? WHERE `EmployeeId`=? and OrganizationId=? ", array(
                $no,
                $uid,
                $orgid
            ));
        }
		
        $data        = array();
        $data['res'] = $res;
        echo json_encode($data);
    }
	
	public function updateProfilePhoto()
    {
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $new_name   = $uid.".jpg";
		$res = 0;
		$status = false;
		
		if (!file_exists(IMGURL2."uploads/$orgid/")) {
			mkdir(IMGURL2."uploads/$orgid/" , 0777,true);
		}
		
		if(file_exists(IMGURL2."uploads/$orgid/".$new_name)){
			unlink(IMGURL2."uploads/$orgid/".$new_name);
		}
		if(!isSet($_FILES["file"]))
			$status = true;
		else
		{
        if (move_uploaded_file($_FILES["file"]["tmp_name"], IMGURL2."uploads/$orgid/" . $new_name)){
		
        //, CurrentCountry=?,countrycode=?
		
        $query = $this->db->query("update EmployeeMaster set `ImageName`= ? WHERE Id =? and OrganizationId=? ", array(       
            $new_name,
            $uid,
            $orgid
        ));//$con,$ccon,
		
			$res = $this->db->affected_rows();
			Trace($new_name." ".$uid." ".$orgid);
			$status = true;
        }
		}
		
        $data     = array();
		$data['status'] = $status;
        echo json_encode($data);
    } 
    
   public function resetPasswordLink(){   // generate and set reset password link
//sendEmail_new("bitsvijay@gmail.com","pwd testing","testing mail");
		$una=isset($_REQUEST['una'])?$_REQUEST['una']:'';
		//$orgid=isset($_REQUEST['refno'])?$_REQUEST['refno']:0;
		//$orgid=sqrt($`-99);
		$una=encode5t($una);
		$query = $this->db->query("SELECT Id,OrganizationId,`FirstName`,`LastName`,(SELECT  `resetPassCounter` FROM `UserMaster` WHERE `Username`=? or username_mobile=?)as ctr, (SELECT  `Username` FROM `UserMaster` WHERE `Username`=? or username_mobile=?)as email FROM `EmployeeMaster` WHERE `Id`=(SELECT  `EmployeeId` FROM `UserMaster` WHERE `Username`=? or username_mobile=?)",array($una,$una,$una,$una,$una,$una));
		if($query->num_rows()>0){
			if($row=$query->result()){	
//	 $url='https://ubiattendance.ubihrm.com/index.php/services/HastaLaVistaUbi?hasta='.encrypt($row[0]->Id).'&vista='.encrypt($orgid);
			$orgid = $row[0]->OrganizationId;
			$email=($row[0]->email=='')?'':decode5t($row[0]->email);
			 $url='https://ubiattendance.ubihrm.com/index.php/services/HastaLaVistaUbi?hasta='.encrypt($row[0]->Id).'&vista='.encrypt($orgid).'&ctrpvt='.encrypt($row[0]->ctr);
			$msg=" Dear ".$row[0]->FirstName." ".$row[0]->LastName." <br/>
				You have requested for reset your ubiAttendance login password, please click on the following link to reset your password ".$url." <br/><br/>
				Thanks<br/>
				UBITECH TEAM" ;
				//sendEmail_new($email,"UbiAttendance reset Password",$msg);
				$headers = 'From: <noreply@ubiattendance.com>' . "\r\n";
				sendEmail_new($email,"UbiAttendance reset Password",$msg,$headers);
				Trace("reset password link mail");
				Trace($msg);
				echo 1; // valid id and ref
			}else
				echo 0;  
		}else
			echo 2;
	}
	
    public function setPassword()
    {
        $uid   = isset($_REQUEST['hasta']) ? decrypt($_REQUEST['hasta']) : 0;
        $orgid = isset($_REQUEST['vista']) ? decrypt($_REQUEST['vista']) : 0;
        $np    = isset($_REQUEST['np']) ? encode5t($_REQUEST['np']) : '';
        //$np=isset($_REQUEST['np'])?($_REQUEST['np']):'';
        $res   = 0;
        //    echo "UPDATE UserMaster SET Password='".$np."' WHERE EmployeeId=".$uid." and OrganizationId=".$orgid;
        //    return false;
        $query = $this->db->query("UPDATE `UserMaster` SET`Password`=?,resetPassCounter=resetPassCounter+1 WHERE EmployeeId=? and OrganizationId=?", array(
            $np,
            $uid,
            $orgid
        ));
        $res   = $this->db->affected_rows();
        echo $res;
    }
    public function checkLinkValidity($uid, $orgid, $counter)
    {
        //    echo "SELECT id  FROM `UserMaster` WHERE  EmployeeId='$uid' and OrganizationId=$orgid and resetPassCounter=$counter";
        //        die();
        $query = $this->db->query("SELECT id  FROM `UserMaster` WHERE  EmployeeId=? and OrganizationId=? and resetPassCounter=?", array(
            $uid,
            $orgid,
            $counter
        ));
        
        return $query->num_rows();
        
    }
    public function getSuperviserSts()
    {
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $query = $this->db->query("SELECT `appSuperviserSts`  FROM `UserMaster` WHERE  EmployeeId=? and OrganizationId=?", array(
            $uid,
            $orgid
        ));
        echo json_encode($query->result());
    }
    public function test()
    {
        $userid  = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $addr    = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $aid     = isset($_REQUEST['aid']) ? $_REQUEST['aid'] : 0;
        $act     = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'TimeIn';
        $shiftId = isset($_REQUEST['shiftid']) ? $_REQUEST['shiftid'] : 0;
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';

		$dept=getDepartmentIdByEmpID($userid);
		$desg=getDesignationIdByEmpID($userid);
		$hourltRate=getHourlyRateIdByEmpID($userid);
		
		
		if($shiftId==0)
			$shiftId=getShiftIdByEmpID($userid);
        ////////---------------checking and marking "timeOff" stop (if exist)
        if($userid!=0)
        	$zone  = getEmpTimeZone($userid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
       // $zone    = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $today   = date("Y-m-d");
        $time   = date("H:i")=="00:00"?"23:59":date("H:i");       
		//echo $time;
		
        $today  = date('Y-m-d');
       
        ////////---------------checking and marking "timeOff" stop (if exist)--/end
        $count      = 0;
        $errorMsg   = "";
        $successMsg = "";
        $status     = 0;
        $resCode    = 0;
        $serversts  = 1;
		$sto='00:00:00';
		$sti='00:00:00';
		$shifttype='';
		$data=array();
		$data['msg']='Mark visit under process';
		$data['res']=0;
		$attImage=0;
		$new_name   = "https://ubitech.ubihrm.com/public/avatars/male.png";
		$attImage=getAttImageStatus($orgid);
		if($attImage){ // true, image must be uploaded. false, optional image
			 $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name)){
			Trace('image not uploaded--');
			$result['status']=3;
			$result['errorMsg']='Error in moving the image, try later.';
			$result['successMsg'] = '';
			echo json_encode($result);
			return;
			}	
			$new_name =IMGURL.$new_name;
		} // Go ahead if image is optional or image uploaded successfully
		
		
     //   $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
    /*    if (move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name))
        
        //if(true)
            {*/
            $sql = '';
			//////----------------getting shift info
            $stype = 0;
            $sql1  = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;
			
            try {
                $result1 = $this->db->query($sql1);
                if ($row1 = $result1->row()) {
                    $stype = $row1->stype;
					$sti=$row1->TimeIn;
					$sto=$row1->TimeOut;
					$shifttype=$row1->shifttype;
                }
            }
            catch (Exception $e) {
                Trace('Error_3: ' . $e->getMessage());
            }
			if($shifttype==2 && $act=='TimeIn'){ // multi date shift case
				if($time<$sto){ // time in should mark in last day date
					try{
						$ldate   = date("Y-m-d",strtotime("-1 days"));
						$sql="select Id as ttl from AttendanceMaster where AttendanceDate='$ldate' and EmployeeId=$userid";
						$res=$this->db->query($sql);
						if($res->num_rows() > 0){// if attn already marked in previous date
							$date   = date("Y-m-d");
						}
						else
							$date   = date("Y-m-d",strtotime("-1 days"));
							
					}catch(Exception $e){
						
					}
				}
				//else  time in should mark in current day's date
			}
			
		//	echo $date;
		//	return false;
			
            //////----------------/gettign shift info
            
            if ($aid != 0) //////////////updating path of employee profile picture in database/////////////
                {
                if ($stype < 0) //// if shift is end whthin same date
                    $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp',overtime =(SELECT subtime(subtime('$time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$date'
                WHERE id=$aid and `EmployeeId`=$userid  and date(AttendanceDate) = '$date' "; //and SUBTIME(  `TimeOut` ,  `TimeIn` ) >'00:05:00'";
                else{
					//////getting timein information
					$sql="select Timein as timein_time, Attendancedate as timein_date from AttendanceMaster where Id=".$aid;
					$timein_date='';
					$timein_time='';
					$res=$this->db->query($sql);
					if($r= $res->result()){
							$timein_date=$r[0]->timein_date;
							$timein_time=$r[0]->timein_time;
					}
					//////getting timein information/
				/*	echo $timein_date.' '.$timein_time;
					echo '---';
					echo $date.' '.$time;
					echo '***';
					*/
					// shift hours
					$shiftHours='';
					$sql="select subtime('24:00:00',subtime(timein,timeout)) as shiftHours from ShiftMaster where id=$shiftId";
					//$sql="select subtime('30:00:00','21:00:00') as shiftHours from ShiftMaster where id=$shiftId";
					$res=$this->db->query($sql);
					if($r= $res->result())
						$shiftHours=$r[0]->shiftHours;
					
					// time spent
			//		echo $timein_date.' '.$timein_time.'-------';
			//		echo $date.' '.$time.'-------';
					$start = date_create($timein_date.' '.$timein_time);
					$end = date_create($date.' '.$time);
					$diff=date_diff($end,$start);
					$hrs=0;
					if($diff->d==1)// if shift is running more than 24 hrs
						$hrs=24;
					$timeSpent=str_pad($hrs+ $diff->h, 2, "0", STR_PAD_LEFT).':'.str_pad($diff->i, 2, "0", STR_PAD_LEFT).':00';
					
					//echo 'TimeSpent:'.$timeSpent;
					//echo 'shiftHours:'.$shiftHours;
                    $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp' ,overtime = subtime('$timeSpent','$shiftHours'),timeoutdate='$date'
                WHERE id=$aid and `EmployeeId`=$userid  ORDER BY `AttendanceDate` DESC LIMIT 1";
                //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
				}
                 /*   $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp' ,overtime =(SELECT subtime(subtime('$time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$date'
                WHERE id=$aid and `EmployeeId`=$userid  ORDER BY `AttendanceDate` DESC LIMIT 1";*/
                //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
                //----------push check code
                try {
                    $push = "push/";
                    if (!file_exists($push))
                        mkdir($push, 0777, true);
                    $filename = $push . $orgid . ".log";
                    $fp       = fopen($filename, "a+");
                    fclose($fp);
                }
                catch (Exception $e) {
                    echo $e->getMessage();
                }
                //----------push check code
            } //LastModifiedDate
            else if ($aid == 0) {
                ///-------- code for prevent duplicacy in a same day   code-001
                $sql = "select * from  AttendanceMaster where EmployeeId=$userid and AttendanceDate= '$today'";
                
                try {
                    $result1 = $this->db->query($sql);
                    if ($this->db->affected_rows() < 1) { ///////code-001 (ends)
                        $area = getAreaId($userid);
                        $sql  = "INSERT INTO `AttendanceMaster`(`EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`,`ShiftId`,Dept_id,Desg_id,areaId,HourlyRateId, `OrganizationId`,
      `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `Overtime`, `EntryImage`, `checkInLoc`,`device`,latit_in,longi_in,timeindate)
      VALUES ($userid,'$date',1,'$time',$shiftId,$dept,$desg,$area,$hourltRate,$orgid,'$date',$userid,'$stamp',$userid,$userid,'00:00:00','" . $new_name . "','$addr','mobile','$latit','$longi','$today')";
      
                    } else
                        $sql = '';
                }
                catch (Exception $e) {
                    Trace('Error_2: ' . $e->getMessage());
                    $errorMsg = 'Message: ' . $e->getMessage();
                    $status   = 0;
                }
                
                
            }
            
            try {
                $query = $this->db->query($sql);
                if ($this->db->affected_rows() > 0 && $act == 'TimeIn') {
                    //----------push check code
                    try {
                        $push = "push/";
                        if (!file_exists($push))
                            mkdir($push, 0777, true);
                        $filename = $push . $orgid . ".log";
                        $fp       = fopen($filename, "a+");
                        fclose($fp);
                    }
                    catch (Exception $e) {
                        echo $e->getMessage();
                    }
                    //----------push check code
                    
                    
                    $resCode    = 0;
                    $status     = 1; // update successfully
                    $successMsg = "Image uploaded successfully.";
                    //////////////////----------------mail send if attndnce is marked very first time in org ever
                    $sql        = "SELECT  `Email`  FROM `Organization` WHERE `Id`=" . $orgid;
                    $to         = '';
                    $query1     = $this->db->query($sql);
                    if ($row = $query1->result()) {
                        $to = $row[0]->Email;
                    }
                    
                    //////////////////----------------/mail send if attndnce is marked very first time in org ever
                } else {
                    $status = 2; // no changes found
                    $errorMsg .= "Failed to upload Image/No Check In found today.";
                }
            }
            catch (Exception $e) {
                Trace('Error_1: ' . $e->getMessage());
                $errorMsg = 'Message: ' . $e->getMessage();
                $status   = 0;
            }
      /*  } else {
            Trace('image not uploaded--');
            $status   = 3; // error in uploading image
            $errorMsg = 'Message: error in uploading image';
        }*/
        $result['status']     = $status;
        $result['successMsg'] = $successMsg;
        $result['errorMsg']   = $errorMsg;
        //$result['location']=$addr;
        
        echo json_encode($result);
    }
    //////-----------------------------------shift
    public function addShift()
    {
        $sna       = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
        $ti        = date("H:i:s", strtotime(isset($_REQUEST['ti']) ? $_REQUEST['ti'] : '00:00:00'));
        $to        = date("H:i:s", strtotime(isset($_REQUEST['to']) ? $_REQUEST['to'] : '00:00:00'));
        $tib       = date("H:i:s", strtotime(isset($_REQUEST['tib']) ? $_REQUEST['tib'] : '00:00:00'));
        $tob       = date("H:i:s", strtotime(isset($_REQUEST['tob']) ? $_REQUEST['tob'] : '00:00:00'));
        $tig       = date("H:i:s", strtotime(isset($_REQUEST['tig']) ? $_REQUEST['tig'] : '00:00:00'));
        $tog       = date("H:i:s", strtotime(isset($_REQUEST['tog']) ? $_REQUEST['tog'] : '00:00:00'));
        $bog       = date("H:i:s", strtotime(isset($_REQUEST['bog']) ? $_REQUEST['bog'] : '00:00:00'));
        $big       = date("H:i:s", strtotime(isset($_REQUEST['big']) ? $_REQUEST['big'] : '00:00:00'));
		 $ti	=	 $ti=='00:00:00'	?	'00:01:00'	:	 $ti;
		 $to	=	 $to=='00:00:00'	?	'23:59:00'	:	 $to;
        $shifttype = isset($_REQUEST['shifttype']) ? $_REQUEST['shifttype'] : 0;
        $orgid     = isset($_REQUEST['org_id']) ? $_REQUEST['org_id'] : '0';
        $sts       = isset($_REQUEST['sts']) ? $_REQUEST['sts'] : 1;
        $date      = date('Y-m-d');
        $res       = 0;
        $query     = $this->db->query("select Id from `ShiftMaster` where Name=? and OrganizationId=?  ", array(
            $sna,
            $orgid
        ));
        if ($query->num_rows() > 0)
            $res = -1; // Shift Name already exist already exist
        else {
            $query = $this->db->query("INSERT INTO `ShiftMaster`(`Name`, `TimeIn`, `TimeOut`, `TimeInGrace`, `TimeOutGrace`, `TimeInBreak`, `TimeOutBreak`, `OrganizationId`, `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `BreakInGrace`, `BreakOutGrace`, `archive`,shifttype) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", array(
                $sna,
                $ti,
                $to,
                $tig,
                $tog,
                $tib,
                $tob,
                $orgid,
                $date,
                0,
                $date,
                0,
                0,
                $big,
                $bog,
                $sts,
                $shifttype
            ));
			$res   = $this->db->affected_rows();
			$shift_id = $this->db->insert_id();
			 for ($i = 1; $i < 8; $i++)// create default weekly off
						$query = $this->db->query("INSERT INTO `ShiftMasterChild`(`ShiftId`,`Day`,`WeekOff`, `OrganizationId`, `ModifiedBy`, `ModifiedDate`) VALUES (?,?,'0,0,0,0,0',?,0,?)",array($shift_id,$i,$orgid,$date));
           }
        $this->db->close();
        echo $res;
        
    }
    //////-----------------------------------/shift
    //////-----------------------------------/department
    public function addDept()
    {
        
        $id    = isset($_REQUEST['uid']) ? $_REQUEST['uid'] :0;
        $orgid = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : 0;
        $dna   = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
        $sts   = isset($_REQUEST['sts']) ? $_REQUEST['sts'] : 0;
        $date  = date('Y-m-d');
		$query=$this->db->query("Select Id from DepartmentMaster where Name=? and OrganizationId=?",array($dna,$orgid));
		$res = $this->db->affected_rows();
		if($res>0)
		{
			echo -1; // if dept already exist
			return false;
		}
        $query = $this->db->query("INSERT INTO `DepartmentMaster`(`Name`, `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `OrganizationId`,`archive`) VALUES (?,?,?,?,?,?,?,?)", array(
            $dna,
            $date,
            $id,
            $date,
            $id,
            $id,
            $orgid,
            $sts
        ));
        $res = $this->db->affected_rows();
        echo $res;
    }
	public function updateDept()
    {
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] :0;
        $dna   = isset($_REQUEST['dept']) ? $_REQUEST['dept'] : '-';
        $sts   = isset($_REQUEST['sts']) ? $_REQUEST['sts'] : 0;
        $did   = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
		$orgid = getName("EmployeeMaster","OrganizationId","Id",$uid);
        $date  = date('Y-m-d');
		$query=$this->db->query("Select Id from DepartmentMaster where Name=? and OrganizationId=? and Id!=?",array($dna,$orgid,$did));
		$res = $this->db->affected_rows();
		if($res>0)
		{
			echo -1; // if dept already exist
			return false;
		}
		
        $query = $this->db->query("update `DepartmentMaster` set 
		`Name`=?,
		`LastModifiedDate`=?, 
		`LastModifiedById`=?,
		`archive`=? 
			where id=?",
		array(
            $dna,
            $date,
            $uid,
            $sts,
			$did
        ));
        $res = $this->db->affected_rows();
		$this->db->close();
        echo $res;
    }
    //////-----------------------------------/department
	public function updateShift()
    {
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] :0;
        $dna   = isset($_REQUEST['shift']) ? $_REQUEST['shift'] : '-';
        $sts   = isset($_REQUEST['sts']) ? $_REQUEST['sts'] : 0;
        $did   = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
		$orgid = getName("EmployeeMaster","OrganizationId","Id",$uid);
        $date  = date('Y-m-d');
		$query=$this->db->query("Select Id from ShiftMaster where Name=? and OrganizationId=? and Id!=?",array($dna,$orgid,$did));
		$res = $this->db->affected_rows();
		if($res>0)
		{
			echo -1; // if dept already exist
			return false;
		}
		
        $query = $this->db->query("update `ShiftMaster` set 
		`Name`=?,
		`LastModifiedDate`=?, 
		`LastModifiedById`=?,
		`archive`=? 
			where id=?",
		array(
            $dna,
            $date,
            $uid,
            $sts,
			$did
        ));
        $res = $this->db->affected_rows();
		$this->db->close();
        echo $res;
    }
	//////-----------------------------------/update designation
	public function updateDesg()
    {
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] :0;
        $dna   = isset($_REQUEST['desg']) ? $_REQUEST['desg'] : '-';
        $sts   = isset($_REQUEST['sts']) ? $_REQUEST['sts'] : 0;
        $did   = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
		$orgid = getName("EmployeeMaster","OrganizationId","Id",$uid);
        $date  = date('Y-m-d');
		$query=$this->db->query("Select Id from DesignationMaster where Name=? and OrganizationId=? and Id!=?",array($dna,$orgid,$did));
		$res = $this->db->affected_rows();
		if($res>0)
		{
			echo -1; // if dept already exist
			return false;
		}
		
        $query = $this->db->query("update `DesignationMaster` set 
		`Name`=?,
		`LastModifiedDate`=?, 
		`LastModifiedById`=?,
		`archive`=? 
			where id=?",
		array(
            $dna,
            $date,
            $uid,
            $sts,
			$did
        ));
        $res = $this->db->affected_rows();
		$this->db->close();
        echo $res;
    }
    //////-----------------------------------/department
    //////-----------------------------------/update designation
   public function updateClient()
   {
       $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] :0;
       $cna   = isset($_REQUEST['company']) ? $_REQUEST['company'] : '-';
       $pna   = isset($_REQUEST['client']) ? $_REQUEST['client'] : '-';
       $sts   = isset($_REQUEST['sts']) ? $_REQUEST['sts'] : 0;
       $cid   = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
       $orgid = getName("EmployeeMaster","OrganizationId","Id",$uid);
       $date  = date('Y-m-d');
       $query=$this->db->query("Select Id from ClientMaster where Company=? and Name=? and OrganizationId=? and Id!=?", array($cna,$pna,$orgid,$cid));
       $res = $this->db->affected_rows();
       if($res>0){
           echo -1; // if dept already exist
           return false;
       }
       
       $query = $this->db->query("update `ClientMaster` set
       `Company`=?,
       `Name`=?,
       `ModifiedDate`=?,
       `ModifiedById`=?,
       `status`=?
        where Id=?",
       array(
           $cna,
           $pna,
           $date,
           $uid,
           $sts,
           $cid
       ));
       $res = $this->db->affected_rows();
       $this->db->close();
       echo $res;
   }
	
    //////-----------------------------------/desgi
    public function addDesg()
    {
        $id    = isset($_REQUEST['uid']) ? $_REQUEST['uid'] :0;
        $orgid = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : 0;
        $dna   = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
        $sts   = isset($_REQUEST['sts']) ? $_REQUEST['sts'] : 0;
        $desc  = isset($_REQUEST['desc']) ? $_REQUEST['desc'] : '';
        $date  = date('Y-m-d');
		$query=$this->db->query("Select Id from DesignationMaster where Name=? and OrganizationId=?",array($dna,$orgid));
		$res = $this->db->affected_rows();
		if($res>0)
		{
			echo -1; // if dept already exist
			return false;
		}
        $query = $this->db->query("INSERT INTO `DesignationMaster`(`Name`, `OrganizationId`, `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`,`Description`, `archive`) VALUES (?,?,?,?,?,?,?,?,?)", array(
            $dna,
            $orgid,
            $date,
            $id,
            $date,
            $id,
            $id,
            $desc,
            $sts
        ));
        $res   = $this->db->affected_rows();
		$this->db->close();
        echo $res;
    }
    public function getTimeoffList()
    {
        $org_id     = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : '0';
        $start_week = isset($_REQUEST['fd']) ? $_REQUEST['fd'] : '0';
        $end_week   = isset($_REQUEST['to']) ? $_REQUEST['to'] : '0';
        $userid   = isset($_REQUEST['userid']) ? $_REQUEST['userid'] : '0';
        $start_week = date("Y-m-d", strtotime($start_week));
        $end_week   = date("Y-m-d", strtotime($end_week));
		$q = "";
		 if($userid  != '0')
		 {
			 $q = "EmployeeId =  $userid And ";
		 }
		
        $query      = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName) from EmployeeMaster where id= `EmployeeId`) as name,(SELECT DisplayName FROM `OtherMaster` WHERE `OtherType` = 'LeaveStatus' and ActualValue = ApprovalSts) as ApprovalSts, TIME_FORMAT(TimeFrom, '%H:%i') as TimeFrom ,TIME_FORMAT(TimeTo, '%H:%i') as TimeTo,IF(TimeTo != '00:00:00' , TIME_FORMAT(TIMEDIFF( TimeTo, TimeFrom), '%H:%i'), '00:00') as diff,DATE_FORMAT(TimeofDate,'%d/%m/%Y') as tod, Reason as reason,CONCAT(LEFT(LocationIn,30),'...') as startloc,LatIn as latin,longIn as longin,CONCAT(LEFT(LocationOut,30),'...') as endloc,LatOut as latout,LongOut as longout FROM `Timeoff` where  $q      OrganizationId = " . $org_id . " and TimeofDate  BETWEEN '" . $start_week . "' AND '" . $end_week . "' order by DATE(TimeofDate), name");
		$this->db->close();
        echo json_encode($query->result(), JSON_NUMERIC_CHECK);
        
        //echo "SELECT (select CONCAT(FirstName,' ',LastName) from EmployeeMaster where id= `EmployeeId`) as name, TimeFrom,TimeTo,TIMEDIFF( TimeTo, TimeFrom) as diff FROM `Timeoff`,TimeofDate where OrganizationId = 10 and TimeofDate  BETWEEN '".$start_week."' AND '".$end_week."'";
        
    }
    //////-----------------------------------/desig
    
    public function getAppVersion()
    {
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : 'Android1';
        if ($platform == 'Android1')
		{
            $query = $this->db->query("SELECT android_version as version FROM  `PlayStore` WHERE orgid=0 LIMIT 1");
		}
        else
		{
            $query = $this->db->query("SELECT ios_version as version FROM  `PlayStore` WHERE orgid=0 LIMIT 1");
		}
		 $this->db->close();
        echo json_encode($query->result());
    }
	public function UpdateStatus()
	{
		 $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : 'Android1';
        if ($platform == 'Android1')
		{
            $query = $this->db->query("SELECT alert_popup_android as status FROM  `PlayStore` WHERE orgid=0   LIMIT 1");
		}
        else
		{
            $query = $this->db->query("SELECT  alert_popup_ios as status FROM  `PlayStore` WHERE orgid=0   LIMIT 1");
		}
		 $this->db->close();
        echo json_encode($query->result());
	}
	public function checkMandUpdate()
    {
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : 'Android';
		
        if ($platform == 'Android')
            $query = $this->db->query("SELECT is_mandatory_android as is_update FROM  `PlayStore` WHERE orgid=0 LIMIT 1");
        else // for ios
            $query = $this->db->query("SELECT is_mandatory_ios as is_update FROM  `PlayStore` WHERE orgid=0 LIMIT 1");
			 $this->db->close();
        echo json_encode($query->result());
    }
    
    public function addCheckin()
    {
        $orgid    = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : 0;
        $cname    = isset($_REQUEST['cname']) ? $_REQUEST['cname'] : '';
        $comment  = isset($_REQUEST['comment']) ? $_REQUEST['comment'] : '';
        $loc      = isset($_REQUEST['loc']) ? $_REQUEST['loc'] : '';
        $latit    = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi    = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $skip     = isset($_REQUEST['skip']) ? $_REQUEST['skip'] : 0;
        $uid      = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $punch_id = isset($_REQUEST['punch_id']) ? $_REQUEST['punch_id'] : 0;
        if($uid!=0)
        	$zone  = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
       // $zone     = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date = date('Y-m-d');
        $time = date('H:i:00');
        if ($punch_id == 0)
            echo $query = $this->db->query("INSERT INTO `checkin_master`(`EmployeeId`, `location`, `latit`, `longi`, `time`, `date`, `client_name`, `description`, `OrganizationId`) VALUES (?,?,?,?,?,?,?,?,?)", array(
                $uid,
                $loc,
                $latit,
                $longi,
                $time,
                $date,
                $cname,
                $comment,
                $orgid
            ));
        else if ($punch_id != 0 && $skip == 1) // skip case
            echo $query = $this->db->query("UPDATE `checkin_master`set `location_out`=location, `latit_out`=latit, `longi_out`=longi, `time_out`=time where id=?", array(
                $punch_id
            ));
        else
            echo $query = $this->db->query("UPDATE `checkin_master`set `location_out`=?, `latit_out`=?, `longi_out`=?, `time_out`=? where id=?", array(
                $loc,
                $latit,
                $longi,
                $time,
                $punch_id
            ));
    }
    
    ////////////////////importing methods from HRM- start
    public function getModules()
    {
        $orgid = isset($_REQUEST['orgid']) ? decode5t($_REQUEST['orgid']) : '0';
        $data  = array();
        try {
            $query              = $this->db->query("SELECT ModuleId AS module,ViewPermission as permission FROM OrgPermission WHERE  OrgId= ? and ModuleId in (5,31,66,171,186,187,2)", array(
                $orgid
            ));
            $data['permission'] = $query->result();
        }
        catch (Exception $a) {
            $data['permission'] = '0';
        }
        return $data;
        
    }
    public function punchLocation()
    {
        $orgid   = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : 0;
        $cname   = isset($_REQUEST['cname']) ? $_REQUEST['cname'] : '';
        $comment = isset($_REQUEST['comment']) ? $_REQUEST['comment'] : '';
        $loc     = isset($_REQUEST['loc']) ? $_REQUEST['loc'] : '';
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $uid     = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $lid     = isset($_REQUEST['lid']) ? $_REQUEST['lid'] : 0;
        $act     = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'PunchIn';
        if($uid!=0)
        	$zone  = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date           = date('Y-m-d');
        $time           = date('H:i:00');
        $data           = array();
        $data['lid']    = 0;
        $data['status'] = 'failure';
        if ($lid == 0) {
            $query = $this->db->query("INSERT INTO checkin_master(`EmployeeId`, `location`, `latit`, `longi`, `time`, `date`, `client_name`, `description`, `OrganizationId`) VALUES (?,?,?,?,?,?,?,?,?)",
			array(
                $uid,
                $loc,
                $latit,
                $longi,
                $time,
                $date,
                $cname,
                $comment,
                $orgid
            ));
            
            $data['lid']    = $this->db->insert_id();
            $data['act']    = 'PunchOut';
            $data['status'] = 'success';
        } else {
            $query = $this->db->query("update `checkin_master` set location_out=?, `latit_out`=?, `longi_out`=?, `time_out`=?  where Id =?",
			array(
                $loc,
                $latit,
                $longi,
                $time,
                $lid
            ));
            $data['status'] = 'success';
            $data['act']    = 'PunchIn';
        }
        echo json_encode($data);
        
    }
    public function skipPunch()
    {
        $lid            = isset($_REQUEST['lid']) ? $_REQUEST['lid'] : 0;
        $data           = array();
        $data['status'] = 'failure';
        $query          = $this->db->query("update `checkin_master` set location_out=location,`latit_out`=latit, `longi_out`=longi, `time_out`=time  where Id =?",
		array(
            $lid
        ));
        if ($query)
            $data['status'] = 'success';
        echo json_encode($data);
        
    }
    public function fetchTimeOffList()
    {
		
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
		
		$orgid=getOrgIdByEmpId($uid);
			//	$zone  = getTimeZone($orgid);
		$zone    = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $query = $this->db->query("SELECT Id, `TimeofDate`, `TimeFrom`, `TimeTo`,TIMEDIFF(TimeTo, TimeFrom) as hrs, `Reason`, `ApprovalSts`, `ApproverComment`, `LocationIn`, `LatIn`, `longIn`, `LocationOut`, `LatOut`, `LongOut` FROM `Timeoff` WHERE EmployeeId=? order by Id desc limit 30",
		array(
            $uid
        ));
        $res          = array();
      
        foreach ($query->result() as $row) {
            $data         = array();
			$currenttime = date('H:i');
			$currentdate = date('Y-m-d');
			$data['timeoffid'] = $row->Id;
            $data['date'] = date("dS M", strtotime($row->TimeofDate));
			
			$fromtime=date('H:i', strtotime($row->TimeFrom));
			$timeoffdate=date('Y-m-d', strtotime($row->TimeofDate));
			if($currentdate==$timeoffdate && strtotime($currenttime)>strtotime($fromtime)){
				$data['withdrawlsts'] = false;
			}else if((strtotime($currentdate)<=strtotime($timeoffdate) ) && ($this->gettimeoffpendingatstatus($row->ApprovalSts, $row->Id)=='Pending')){
				/* if(strtotime($currenttime)>strtotime($fromtime)){
				$data['withdrawlsts'] = false;
				} */
				$data['withdrawlsts'] = true;
			}else
				$data['withdrawlsts'] = false;
                                           
            $data['from'] = date('H:i', strtotime($row->TimeFrom));
            $data['to']   = date('H:i', strtotime($row->TimeTo));
            $data['hrs']  = date('H:i', strtotime($row->hrs));
            
            $data['status']  = $this->gettimeoffpendingatstatus($row->ApprovalSts, $row->Id);
            $data['reason']  = $row->Reason != '' || $row->Reason != null ? $row->Reason : '-';
            $data['startloc']  = $row->LocationIn != '' || $row->LocationIn != null ? $row->LocationIn : '-';
            $data['latin']   = $row->LatIn;
            $data['longout']  = $row->longIn;
            $data['endloc']  = $row->LocationOut != '' || $row->LocationOut != null ? $row->LocationOut : '-';
            $data['latout']   = $row->LatOut;
            $data['longout']  = $row->LongOut;
            $data['comment'] = $row->ApproverComment != '' || $row->ApproverComment != null ? $row->ApproverComment : '-';
            //$data['comment']=$row->ApproverComment;
            $res[]           = $data;
        }
        echo json_encode($res);
    }
	
	/////////////////   service to fetch leave summary  //////////
	
	public function getLeaveList()
    {
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $query = $this->db->query("SELECT Id,`ApplyDate`, `LeaveFrom`, `LeaveTo`, LeaveValidDays, `LeaveReason`, `LeaveStatus`, `ApproverComment` FROM `EmployeeLeave` WHERE EmployeeId=? order by Id desc limit 30",
		array(
            $uid
        ));
        $res          = array();
		
        foreach ($query->result() as $row) {
            $data         = array();
			$data['leaveid'] = $row->Id;
			$todaydate=date("Y-m-d");
			$data['withdrawlsts'] = true;
			if(strtotime($todaydate)>strtotime($row->LeaveFrom))
				$data['withdrawlsts'] = false;
            $data['date'] = date("dS M'y", strtotime($row->ApplyDate));
            $data['from'] = date("dS M'y", strtotime($row->LeaveFrom));
            $data['to']   = date("dS M'y", strtotime($row->LeaveTo));
            $data['days']  = ' (' . $row->LeaveValidDays . ')';
            $data['status']  = $this->getpendingatstatus($row->LeaveStatus, $row->Id);// TODO dynamic with the status pending on which employee.
            $data['reason']  = $row->LeaveReason != '' || $row->LeaveReason != null ? $row->LeaveReason : '-';
            $data['comment'] = $row->ApproverComment != '' || $row->ApproverComment != null ? $row->ApproverComment : '-';
            //$data['comment']=$row->ApproverComment;
            $res[]           = $data;
        }
        echo json_encode($res);
    }
	
	public function getpendingatstatus($sts,$leaveid){
	if($sts==3){
						$pendingapprover=$this->getApproverPendingSts($leaveid,3);
						$pendingapp=$this->getEmployeeName($pendingapprover);			
						if($pendingapp=="")
							return "Pending";
						else
							return "Pending at $pendingapp";
					}else{
						return $this->getleavetype($sts);
					}
	}
	
	public function getApproverPendingSts($id,$sts)
	{
		$name ="0";
		if($sts==2) //approved
			$sql = "SELECT * FROM LeaveApproval where LeaveId=? and ApproverSts=? order by Id desc limit 1";
		else //pending	
			$sql = "SELECT * FROM LeaveApproval where LeaveId=? and ApproverSts=? order by Id asc limit 1";
		$query = $this->db->query($sql,
		array(
			$id,$sts
		));
		try{
			foreach ($query->result() as $row) {
		
				$name = $row->ApproverId;
			}
		}catch(Exception $e) {}

		return $name;
	}
	
	//////////// get time off pending at status ///////////////
	
		public function gettimeoffpendingatstatus($sts,$leaveid){
	if($sts==3){
						$pendingapprover=$this->gettimeoffApproverPendingSts($leaveid,3);
						$pendingapp=$this->getEmployeeName($pendingapprover);			
						if($pendingapp=="")
							return "Pending";
						else
							return "Pending";
						/* return "Pending at $pendingapp";// removing pending at condition as per discussion with badi ma'am */
					}else{
						return $this->getleavetype($sts);
					}
	}
	
	public function gettimeoffApproverPendingSts($id,$sts)
	{
		$name ="0";
		if($sts==2) //approved
			$sql = "SELECT * FROM TimeoffApproval where TimeofId=? and ApproverSts=? order by Id desc limit 1";
		else //pending	
			$sql = "SELECT * FROM TimeoffApproval where TimeofId=? and ApproverSts=? order by Id asc limit 1";
		$query = $this->db->query($sql,
		array(
			$id,$sts
		));
		try{
			foreach ($query->result() as $row) {
		
				$name = $row->ApproverId;
			}
		}catch(Exception $e) {}

		return $name;
	}
	
	public function getEmployeeName($id)
	{
		$name ="";
		
		$sql = "SELECT FirstName, MiddleName, LastName FROM EmployeeMaster WHERE Id = ?";
        $query =$this->db->query($sql,
		array(
			$id
		));
		try{
			foreach ($query->result() as $row) {
				 $name = ucwords(strtolower($row->FirstName. " " .$row->MiddleName. " " .$row->LastName));
			}
		}catch(Exception $e) {
			
		}
		return $name;
	}
	
	public function getleavetype($val){
		$status = "info"; $label="Pending";                 
		if($val==1){ $status = "danger"; $label="Rejected";  }
		elseif($val==2){ $status = "success"; $label="Approved";  }
		elseif($val==4){ $status = "warning"; $label="Cancel";  }	
		elseif($val==5){ $status = "info"; $label="Withdrawn";  }			
		elseif($val==6){ $status = "success"; $label="Issued";  }			
		elseif($val==7){ $status = "warning"; $label="Pending at admin";  }			
		return $label;
    }
	

    ////////////////////importing methods from HRM- end
    public function mailtest()
    {
        echo getAreaId(4147);
        return false;
        echo decrypt('q+fX19fNg7zez84=');
        echo "</br/>" . decrypt('q+fX19fNg7zez86U');
        return false;
        $tmrw      = date('Y-m-d', strtotime(' +1 day'));
        $startdate = date('Y-m-d', strtotime(' -2 day'));
        $enddate   = date('Y-m-d', strtotime(' +2 day'));
        $data      = array();
        $query     = $this->db->query("SELECT
            LIC.end_date as expiry ,
            AD.name as contectperson ,
            ORG.Id as orgid,
            ORG.Name as orgname ,
            ORG.PhoneNumber as orgno,
            ORG.Email as orgemail,
            ORG.Country as orgcountry,
            (select count(id) from EmployeeMaster as EMP where EMP.OrganizationId=ORG.Id) as orgemp ,
            AD.username as uname,
            AD.password as pass
            FROM  admin_login AD , Organization ORG , licence_ubiattendance LIC WHERE LIC.OrganizationId=ORG.Id AND AD.OrganizationId=ORG.Id AND  LIC.end_date BETWEEN '$startdate' AND '$enddate'  ORDER BY LIC.end_date ");
        foreach ($query->result() as $row) {
            $data1                  = array();
            $data1['expiry']        = $row->expiry;
            $data1['contectperson'] = $row->contectperson;
            $data1['orgid']         = $row->orgid;
            $data1['orgname']       = $row->orgname;
            $data1['orgno']         = $row->orgno;
            $data1['orgemail']      = $row->orgemail;
            $data1['orgcountry']    = $row->orgcountry;
            $data1['orgemp']        = $row->orgemp;
            $data1['uname']         = $row->uname;
            $data1['pass']          = decrypt($row->pass);
            $data[]                 = $data1;
        }
        //print_r($data); return;
        $list = '';
        for ($i = 0; $i < count($data); $i++) {
            $list .= '<tr><td>' . ($i + 1) . '.</td><td>' . date('d-m-Y', strtotime($data[$i]['expiry'])) . '</td><td>' . (($data[$i]['orgid']) * ($data[$i]['orgid']) + 99) . '</td><td>' . $data[$i]['orgname'] . '</td><td>' . $data[$i]['contectperson'] . '</td><td>' . $data[$i]['orgno'] . '</td><td>' . $data[$i]['orgemail'] . '</td><td>' . $data[$i]['orgemp'] . '</td><td>' . getCountryById1($data[$i]['orgcountry']) . '</td><tr/>';
            
            if ($data[$i]['expiry'] == $tmrw) {
                $to      = $data[$i]['orgemail'];
                $subject = $data[$i]['contectperson'] . ", your Premium Plan expires tomorrow!";
                $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

                <title>ubiAttendance</title>
                <style type="text/css">
                body {
                  margin-left: 0px;
                  margin-top: 0px;
                  margin-right: 0px;
                  margin-bottom: 0px;
                -webkit-text-size-adjust:none; -ms-text-size-adjust:none;
                background: white;
                } 

                table{border-collapse: collapse;}  
                .icon-row{
                  border-bottom: 2px solid #00aad4;

                }

                .icons{
                  padding: 50px;
                }
                .icons img{
                  width:100px;
                  height: auto;
                }

                </style></head>

                <body>
                <table  width="650" align="center" style="background-color: white;">
                  <tr>
                    <td><img src="https://ubiattendance.ubihrm.com/mailers/banner.png"></td>
                </tr>
                <tr>
                  <td align="left">
                    <p style="font-family: Arial;font-size: 18px;padding: 10px;">
                      Hello ' . $data[$i]['contectperson'] . ',<br><br>

                We hope you are enjoying the free trial of ubiAttendance!<br><br> 

                The Trial period will be over in just less than 24 hours. By now, you are more than likely feeling one of these two ways:<br><br> 

                 

                HAPPY! -    Subscribe to the ubiAttendance Software - <a target="_blank" href="https://ubiattendance.ubihrm.com/">Login to My Plan</a><br><br>

                NEED MORE TIME?  -   <a  style="color: black;" href="mailto:support@ubitechsolutions.com?subject=Extend%20My%20Free%20Trial">Extend your trial further by writing back to us</a><br><br>

                 

                Looking forward to make <b>ubiAttendance</b> work for you!<br><br>

                Regards,<br>

                Team ubiAttendance 
                      
                    </p>
                    
                  </td>
                </tr>
                <tr>
                  <td align="center">
                    <p style="text-align: center;font-size: 16px;font-family: Arial">You can <a style="color: black;" href="mailto:unsubscribe@ubitechsolutions.com?subject=Unsubscribe&body=Hello%0A%0APlease%20unsubscribe%20me%20from%20the%20mailing%20list%0A%0AThanks">unsubscribe</a> from this email or change your email 
                <br>notifications</p>
                  </td>
                </tr>
                  </table>
                  <table  width="650" align="center"> 
                    <tr>
                      <td>
                        <p style="text-align: center; font-size: 12px; font-family:Arial">
                          This email was sent by <a style="" href="mailto:ubiattendance@ubitechsolutions.com">ubiattendance@ubitechsolutions.com</a> to ' . $data[$i]['orgemail'] . '
                Not interested? <a style="color: black;" href="mailto:unsubscribe@ubitechsolutions.com?subject=Unsubscribe&body=Hello%0A%0APlease%20unsubscribe%20me%20from%20the%20mailing%20list%0A%0AThanks">Unsubscribe</a><br>
                <p style="color: grey;text-align: center;font-size: 12px;">Ubitech Solutions Private Limited | S-553, Greater Kailash Part II, New Delhi, 110048</p>

                        </p>
                      </td>
                    </tr>
                  </table>

                </body>
                </html>';
                
                // Always set content-type when sending HTML email
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                // More headers
                $headers .= 'From: <support@ubitechsolutions.com>' . "\r\n";
                //$headers .= 'Cc: vijay@ubitechsolutions.com' . "\r\n";
                //        sendEmail_new($to,$subject,$message,$headers);
              //--  sendEmail_new('parth@ubitechsolutions.com', "Mail Via SMTP", $message, $headers);
                echo 'Mail done';
                //    sendEmail_new('parth@ubitechsolutions.com',$subject,$message,$headers);
                break;
            } //if ends here
        } // loop ends here
        
        
        return false;
        /*
        $to = "parth@ubitechsolutions.com";
        $subject = "Expiry mailer";
        $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        
        <title>ubiAttendance</title>
        <style type="text/css">
        body {
        margin-left: 0px;
        margin-top: 0px;
        margin-right: 0px;
        margin-bottom: 0px;
        -webkit-text-size-adjust:none; -ms-text-size-adjust:none;
        background: white;
        } 
        
        table{border-collapse: collapse;}  
        .icon-row{
        border-bottom: 2px solid #00aad4;
        
        }
        
        .icons{
        padding: 50px;
        }
        .icons img{
        width:100px;
        height: auto;
        }
        
        </style></head>
        
        <body>
        <table  width="650" align="center" style="border: 3px solid #e3e6e7;">
        <tr>
        <td><img src="https://ubiattendance.ubihrm.com/mailers/banner.png"></td>
        </tr>
        <tr>
        <td align="left">
        <p style="font-family: Arial;font-size: 20px;padding: 10px;">
        Hello [Customer Name]*,<br><br>
        
        We hope you are enjoying the free trial of ubiAttendance!<br><br> 
        
        The Trial period will be over in just less than 24 hours. By now, you are more than likely feeling one of these two ways:<br><br> 
        
        
        
        HAPPY!     Subscribe to the ubiAttendance Software - <a target="_blank" href="https://ubiattendance.ubihrm.com/">Login to My Plan</a><br><br>
        
        ID - [registered email id]*<br><br>
        
        Password - [password]*<br><br>
        
        
        
        NEED MORE TIME?     <a  style="color: black;" href="mailto:support@ubitechsolutions.com?subject=Extend%20My%20Free%20Trial">Extend your trial further by writing back to me</a><br><br>
        
        
        
        Looking forward to make <b>ubiAttendance</b> work for you!<br><br>
        
        Regards,<br><br>
        
        Team ubiAttendance 
        
        </p>
        
        </td>
        </tr>
        </table>
        
        </body>
        </html>';
        
        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // More headers
        $headers .= 'From: <support@ubitechsolutions.com.com>' . "\r\n";
        //$headers .= 'Cc: vijaympct13@gmail.com' . "\r\n";
        sendEmail_new($to,$subject,$message,$headers);*/
    }
    
	public function getAllDesgPermission()
    {
		$result = array();
		$orgid = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : 0;
		$roleid = isset($_REQUEST['roleid']) ? $_REQUEST['roleid'] : 0;
		$count=0; $errorMsg=""; $successMsg=""; $status=false;
		$data = array();
        $query = $this->db->query("SELECT `Id`, `Name` FROM `DesignationMaster`  WHERE OrganizationId=? and archive = 1 AND Id != $roleid order by name", array(
            $orgid
        ));
		$count=$query->num_rows();
		if($count>=1)
		{
			$status=true;
			$successMsg=$count." record found";
			//$res1 = array();
			//$res1['rolename'] = $id;
			//$data[] = $res1;
			foreach ($query->result() as $row)
			{
				$res = array();
				$res['id'] = $row->Id;
				$res['rolename'] = $row->Name;
				$res['permissions'] = $this->getPermissionDetail($row->Id, $orgid);				
				$data[] = $res;
			}
        }
		if ($count >= 1) {
           $status =true;
		   $successMsg = "Permission successed";
        } else {
           $status =false;
		   $errorMsg="Error while fetching permission";
        }
		/* $result["data"] =$data;
		$result['status']=$status;
		$result['successMsg']=$successMsg;
		$result['errorMsg']=$errorMsg; */
        return $data;
    }
	public function getPermissionDetail($id, $orgid){
		$result = array();
		$count=0; $errorMsg=""; $successMsg=""; $status=false;
		$data = array();
		//$id=decode5t($id);
		$this->db->select('*');		 
		$whereCondition="(AttendanceAppSts=1)";
		$this->db->where($whereCondition);
		$this->db->from('ModuleMaster');
		
		$query1 =$this->db->get();
		$count=$query1->num_rows();
		if($count>=1){
			$status=true;
			$successMsg=$count." record found";			
			foreach ($query1->result() as $row)
			{
				$res = array();				
				$res['rolename'] = $id;
				$res['roles'] = getName('DesignationMaster','Name','Id',$id);
				$res['modulename'] = $row->Id;
				$res['name'] = getName('ModuleMaster','ModuleName','Id',$row->Id);
				$res['label'] = getName('ModuleMaster','ModuleLabel','Id',$row->Id);
				$res['vsts'] = (int)$this->getModulePermission($id, $row->Id, "ViewPermission", $orgid);
				$res['ests'] = (int)$this->getModulePermission($id, $row->Id, "EditPermission", $orgid);
				$res['dsts'] = (int)$this->getModulePermission($id, $row->Id, "DeletePermission", $orgid);
				$res['asts'] = (int)$this->getModulePermission($id, $row->Id, "AddPermission", $orgid);
				$data[] = $res;
			}
        }
		
		if ($count >= 1) {
           $status =true;
		   $successMsg = "Permission successed";
        } else {
           $status =false;
		   $errorMsg="Error while fetching permission";
        }
		
		$result = $data;		
		return $result;
    }
	
	function getModulePermission($roleid, $moduleid, $sts, $orgid){
		
		$ci =& get_instance();
		$ci->load->database();
		$per="0";$result = array();
		//$conname='';
		$ci->db->select($sts);
		$whereCondition= "(RoleId = $roleid AND OrganizationId = $orgid AND ModuleId = $moduleid)";
		$ci->db->where($whereCondition);
		$ci->db->from("UserPermission");
		$query =$ci->db->get();
		$count = $query->num_rows();
		if($count>0){
			$status=true;
			$successMsg=$count." record found";
			foreach($query->result() as $row){
				$per=$row->$sts;
			}
		}
		return  $per;
	}
	
	
/* 	public function getPermissionDetail($id, $orgid){
		$result = array();
		$count=0; $errorMsg=""; $successMsg=""; $status=false;
		$data = array();
		//$id=decode5t($id);
		$this->db->select('*');		 
		$whereCondition="(RoleId = $id AND OrganizationId = $orgid and ModuleId in (select Id from ModuleMaster where AttendanceAppSts=1))";
		$this->db->where($whereCondition);
		$this->db->from('UserPermission');
		
		$query1 =$this->db->get();
		$count=$query1->num_rows();
		if($count>=1){
			$status=true;
			$successMsg=$count." record found";			
			foreach ($query1->result() as $row)
			{
				$res = array();
				$res['id'] = $row->Id;
				$res['rolename'] = $row->RoleId;
				$res['roles'] = getName('DesignationMaster','Name','Id',$row->RoleId);
				$res['modulename'] = $row->ModuleId;
				$res['name'] = getName('ModuleMaster','ModuleName','Id',$row->ModuleId);
				$res['label'] = getName('ModuleMaster','ModuleLabel','Id',$row->ModuleId);
				$res['vsts'] = (int)$row->ViewPermission;
				$res['ests'] = (int)$row->EditPermission;
				$res['dsts'] = (int)$row->DeletePermission;
				$res['asts'] = (int)$row->AddPermission;
				$data[] = $res;
			}
        }
		
		if ($count >= 1) {
           $status =true;
		   $successMsg = "Permission successed";
        } else {
           $status =false;
		   $errorMsg="Error while fetching permission";
        }
		
		$result = $data;		
		return $result;
    } */
	
	public function getAttendances_new()
    {
        // getting counting of attending/onbreak/exits and not attending emps
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
        $att   = isset($_REQUEST['att']) ? $_REQUEST['att'] : 0;
        $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : 0;
        //$zone  = getTimeZone($orgid);
        $zone    = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $date =date('Y-m-d');
        $time = date('H:i:s');
        $data = array();
       
	    $adminstatus = getAdminStatus($empid);
	/*	$cond = "";
		$cond1 = "";
		if($adminstatus == '2')
		{ 
	     	$dptid = getDepartmentIdByEmpID($empid);
			$cond = " AND Dept_id = $dptid  ";
			$cond1 = " AND Department = $dptid  ";
		}
	   */
	   if($datafor=='present'){
         //today attendance
		$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE `AttendanceDate`=?  and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `name`", array($date,$orgid));
            $data['present']    = $query->result();
	   }else if($datafor=='absent'){/*
            //---managing off (weekly and holiday)
            $dt                   = $date;          
            //    day of month : 1 sun 2 mon --
            $dayOfWeek   = 1 + date('w', strtotime($dt));
            $weekOfMonth = weekOfMonth($dt);
            $week        = '';
            $query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
                $orgid,
                $dayOfWeek
            ));
            if ($row = $query->result()) {
                $week = explode(",", $row[0]->WeekOff);
            }
            if ($week[$weekOfMonth - 1] == 1) {
                $data['absentees'] = '';
            } else {
                $query = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
                    $orgid,
                    $dt
                ));
                if ($query->num_rows() > 0) {
                    //-----managing off (weekly and holiday) - close            
                    $query             = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status from EmployeeMaster where `OrganizationId` =$orgid and EmployeeMaster.archive=1 and EmployeeMaster.Id not in(select AttendanceMaster.`EmployeeId` from AttendanceMaster where `AttendanceDate`='$date' and `OrganizationId` =$orgid) and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift) order by `name`", array(
                        $orgid,
                        $date,
                        $orgid
                    ));
                    $data['absentees'] = $query->result();
                } 
            }*/
			 //////////today_abs
           /* $q2    = "select (select count(EmployeeMaster.Id)-count(AttendanceMaster.Id) from EmployeeMaster where OrganizationId =" . $orgid . ") as total, AttendanceDate from AttendanceMaster where AttendanceDate ='$date' and OrganizationId =" . $orgid . " group by AttendanceDate";
            $query = $this->db->query($q2);
            $d     = array();
            $res   = array();
            foreach ($query->result() as $row) {
                $query1 = $this->db->query("SELECT Id as EmployeeId ,FirstName,Shift,Department,Designation, Id ,'" . $row->AttendanceDate . "' as absentdate
                            FROM `EmployeeMaster` 
                            WHERE EmployeeMaster.OrganizationId =" . $orgid . "
                             AND archive=1 and IF(EmployeeMaster.CreatedDate!='0000-00-00 00:00:00', CreatedDate < '" . $row->AttendanceDate . "', 1) and  Id 
                            NOT IN (
                            SELECT `EmployeeId` 
                            FROM `AttendanceMaster` 
                            WHERE `AttendanceDate` 
                            IN (
                             '" . $row->AttendanceDate . "'
                            )
                            AND AttendanceMaster.OrganizationId =" . $orgid . "
                            AND `AttendanceStatus` not in(3,5,6)
                            )
                            ORDER BY EmployeeMaster.Id ");
                $count  = $query1->num_rows();
                foreach ($query1->result() as $row) {
                    $data1            = array();
                    //$data['name']=ucwords(getEmpName($row->Id));
                    $data1['name']    = getEmpName($row->EmployeeId);
                    $data1['status']  = 'Absent';
                    $data1['TimeIn']  = '-';
                    $data1['TimeOut'] = '-';
                    $res[]           = $data1;
                }
            }
            $this->db->close();
            $data['absent'] = $res;
        */
		
         $temp=array();
			$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`  ) as name , '-' as `TimeOut` ,'-' as TimeIn FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and AttendanceStatus in (2,6,7) order by `name`",array($date,$orgid));
            $temp =  $query->result();
			$query  = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name, '-' as TimeIn,'-' as TimeOut
						FROM  `EmployeeMaster` 
						WHERE  `OrganizationId` =? 
						AND ARCHIVE =1
						AND Is_Delete=0
						AND (DOL='0000-00-00' OR DOL>CURDATE())
						AND Id NOT 
						IN (
						SELECT EmployeeId
						FROM AttendanceMaster
						WHERE AttendanceDate =  ?
						AND  `OrganizationId` =?
						)
						AND (
						SELECT  `TimeIn` 
						FROM  `ShiftMaster` 
						WHERE  `Id` = Shift
						AND TimeIn <  ?
						)",array($orgid,$date,$orgid,$time));
						$data['absent']= array_merge($temp,$query->result());
						
			  $this->db->close();
	}else if($datafor=='latecomings'){		
			
        //////// today_late
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=?   and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by `name`", array(
                $date,
                $orgid
            ));
            $data['lateComings'] = $query->result();
	}else if($datafor=='earlyleavings'){	
			
        ////////today_early
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=?   and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) order by `name`", array(
                $date,  $orgid ));
            $data['earlyLeavings'] = $query->result();
	}
			
			
			
       
        
        echo json_encode($data, JSON_NUMERIC_CHECK);
    }
	
	public function getAttendances_yes()
    {
        // getting counting of attending/onbreak/exits and not attending emps
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
        $att   = isset($_REQUEST['att']) ? $_REQUEST['att'] : 0;
        $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : 0;
       //$zone  = getTimeZone($orgid);
        if($empid!=0)
        	$zone  = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
       $date =date('Y-m-d', strtotime(' -1 day'));
        $time = date('H:i:s');
        $data = array();
       
	   
	   
	   if($datafor=='present'){
         //today attendance
		$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) order by `name`", array(
				$date,	$orgid  ));
            $data['present']        = $query->result();
	   }else if($datafor=='absent'){
			$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , '-' as `TimeOut` ,'-' as TimeIn FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=2 or AttendanceStatus=6 or AttendanceStatus=7 ) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) order by `name`", array($date,$orgid));
            $this->db->close();
            $data['absent'] =  $query->result();
        
	}else if($datafor=='latecomings'){		
			
			
        //////// today_late
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) order by `name`", array(
                $date,
                $orgid
            ));
            $data['lateComings'] = $query->result();
	}else if($datafor=='earlyleavings'){	
			
			
			
        ////////today_early
          /* $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8)  AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) order by `name`", array( $date,$orgid  ));
            $data['earlyLeavings'] = $query->result();*/
		$query = $this->db->query("select Shift,Id , FirstName , LastName  from EmployeeMaster where OrganizationId = $orgid and Id IN (select EmployeeId from AttendanceMaster where OrganizationId = $orgid and AttendanceDate='$date' and TimeIn != '00:00:00'   ) AND is_Delete=0 order by FirstName");
		 $res   = array();
		 $cond  = '';
        foreach ($query->result() as $row) {
            $ShiftId = $row->Shift;
            $EId     = $row->Id;
            $query   = $this->db->query("select TimeIn,TimeOut,shifttype from ShiftMaster where Id = $ShiftId");
            if ($data123 = $query->row()) {
                $shiftout = $data123->TimeOut;
                $shiftout1 = $date. ' '.$data123->TimeOut;
				if($data123->shifttype==2)
				{
					$nextdate = date('Y-m-d',strtotime($date . "+1 days"));
					 $shiftout1 = $nextdate.' '.$data123->TimeOut;
				}
                $shift    = substr($data123->TimeIn, 0, 5) . ' - ' . substr($data123->TimeOut, 0, 5);
                $ct       = date('H:i:s');
               
               
                    $query333 = $this->db->query("select SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out from AttendanceMaster where  EmployeeId =$EId and if(timeoutdate = '0000-00-00' , TimeOut  <  '$shiftout' , CONCAT(timeoutdate,' ' ,TimeOut)  <  '$shiftout1' ) and AttendanceDate='$date' and TimeOut !='00:00:00' ");
					
					
                    if ($row333 = $query333->row()) {
                        $a               = new DateTime($row333->TimeOut);
                        $b               = new DateTime($data123->TimeOut);
                        $interval        = $a->diff($b);
                        $data1['earlyby'] = $interval->format("%H:%I");
                        $data1['timeout'] = substr($row333->TimeOut, 0, 5);
                        $data1['name']  = $row->FirstName . ' ' . $row->LastName;
                        $data1['shift'] = $shift;
                        $data1['status'] = $row333->status;
                        $data1['TimeIn']  =     $row333->TimeIn;
                        $data1['TimeOut']  =  $row333->TimeOut;
                        $data1['CheckOutLoc']  =  $row333->CheckOutLoc;
                        $data1['checkInLoc']  =  $row333->checkInLoc;
                        $data1['EntryImage']  =  $row333->EntryImage;
                        $data1['ExitImage']  =  $row333->ExitImage;
                        $data1['latit_in']  =  $row333->latit_in;
                        $data1['longi_in']  =  $row333->longi_in;
                        $data1['latit_out']  =  $row333->latit_out;
                        $data1['longi_out']  =  $row333->longi_out;
                        $data1['date']  = $date;
                        $res[]         = $data1;
                    }
                
            }
        }
            $data['earlyLeavings'] =$res;
	}
     echo json_encode($data, JSON_NUMERIC_CHECK);
        
    }
    public function getCDateAttendances_new()
    {
       // getting counting of attending/onbreak/exits and not attending emps
       $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
       $att   = isset($_REQUEST['att']) ? $_REQUEST['att'] : 0;
       $date   = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
       $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : '';
       $empid   = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : ''; 
       $trialstatus = isset($_REQUEST['trialstatus']) ? $_REQUEST['trialstatus'] : ''; 
       $zone  = getTimeZone($orgid);
       date_default_timezone_set($zone);
       $date =date('Y-m-d',strtotime($date));
       $time = date('H:i:s');
       $data = array();
      $cdate = date('Y-m-d');
       $adminstatus = getAdminStatus($empid);
       $cond = "";
       $cond1 = "";
       if($adminstatus == '2')
       { 
            $dptid = getDepartmentIdByEmpID($empid);
           $cond = " AND Department = $dptid  ";
           $cond1 = " AND Dept_id = $dptid  ";
           
       }

       if($trialstatus=='2')
           $limit='limit 5';
       else
           $limit='';
      
      
        //today attendance
        if($datafor=='present'){
       $query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE `AttendanceDate`=? $cond1 and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = ? AND Is_Delete = 0 ) order by `name` $limit", array($date,$orgid,$orgid));
           $data['present']        = $query->result();
           // var_dump($this->db->last_query());
           // die();
           
            }else if($datafor=='absent'){/*
           //---managing off (weekly and holiday)
           $dt                   = $date;          
           //    day of month : 1 sun 2 mon --
           $dayOfWeek   = 1 + date('w', strtotime($dt));
           $weekOfMonth = weekOfMonth($dt);
           $week        = '';
           $query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
               $orgid,
               $dayOfWeek
           ));
           if ($row = $query->result()) {
               $week = explode(",", $row[0]->WeekOff);
           }
           if ($week[$weekOfMonth - 1] == 1) {
               $data['absentees'] = '';
           } else {
               $query = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
                   $orgid,
                   $dt
               ));
               if ($query->num_rows() > 0) {
                   //-----managing off (weekly and holiday) - close            
                   $query             = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status from EmployeeMaster where `OrganizationId` =$orgid and EmployeeMaster.archive=1 and EmployeeMaster.Id not in(select AttendanceMaster.`EmployeeId` from AttendanceMaster where `AttendanceDate`='$date' and `OrganizationId` =$orgid) and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift) order by `name`", array(
                       $orgid,
                       $date,
                       $orgid
                   ));
                   $data['absentees'] = $query->result();
               } 
           }*/
           //////////today_abs
           
           if($date != date('Y-m-d')){// for other deay's absentees
               $query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , '-' as `TimeOut` ,'-' as TimeIn FROM `AttendanceMaster` WHERE `AttendanceDate`=? $cond1 and  OrganizationId=? and (AttendanceStatus=2 or AttendanceStatus=6 or AttendanceStatus=7 ) AND EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId =  $orgid AND Is_Delete = 0)  order by `name` $limit", array($date,$orgid));
               $this->db->close();
               $data['absent'] =  $query->result();
        
           }else{ // for today's absentees
           
               $query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , '-' as `TimeOut` ,'-' as TimeIn FROM `AttendanceMaster` WHERE `AttendanceDate`=? $cond1 and  OrganizationId=? 
and AttendanceStatus in (2,6,7) AND EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId =  $orgid AND Is_Delete = 0 ) order by `name` $limit",array($date,$orgid));
           $temp=array();
           $temp =  $query->result();
           
           $query  = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name, '-' as `TimeOut` ,'-' as TimeIn FROM  `EmployeeMaster`  WHERE  `OrganizationId` =? AND  Is_Delete=0  $cond AND ARCHIVE =1 AND is_Delete = 0 AND (DOL='0000-00-00' OR DOL>CURDATE()) AND Id NOT  IN ( SELECT EmployeeId FROM AttendanceMaster WHERE AttendanceDate =  ? AND  `OrganizationId` =? ) AND ( SELECT  `TimeIn`  FROM  `ShiftMaster`  WHERE  `Id` = Shift AND TimeIn <  ? ) $limit",array($orgid,$date,$orgid,$time));
            
               $data['absent']=  array_merge($temp,$query->result());	
           }
           
   }else if($datafor=='latecomings'){
       //////// today_late
           $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? $cond1 and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId =  $orgid AND Is_Delete = 0 ) order by `name` $limit", array(
               $date,
               $orgid
           ));
           $data['lateComings'] = $query->result();
   }else if($datafor=='earlyleavings'){		
       ////////today_early
           /*$query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? $cond1 and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )  AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)  order by `name`", array( $date,$orgid ));
           $data['earlyLeavings'] = $query->result();*/
           
        $query = $this->db->query("select Shift,Id , FirstName , LastName  from EmployeeMaster where OrganizationId = $orgid and Id IN (select EmployeeId from AttendanceMaster where OrganizationId = $orgid and AttendanceDate='$date' and TimeIn != '00:00:00'   ) AND is_Delete=0 order by FirstName $limit");
        $res   = array();
        $cond  = '';
       foreach ($query->result() as $row) {
           $ShiftId = $row->Shift;
           $EId     = $row->Id;
           $query   = $this->db->query("select TimeIn,TimeOut,shifttype from ShiftMaster where Id = $ShiftId");
           if ($data123 = $query->row()) {
               $shiftout = $data123->TimeOut;
               $shiftout1 = $date. ' '.$data123->TimeOut;
               if($data123->shifttype==2)
               {
                   $nextdate = date('Y-m-d',strtotime($date . "+1 days"));
                    $shiftout1 = $nextdate.' '.$data123->TimeOut;
               }
               $shift    = substr($data123->TimeIn, 0, 5) . ' - ' . substr($data123->TimeOut, 0, 5);
               $ct       = date('H:i:s');
              
                   if ($cdate == $date)
                       $cond = "    and TimeOut !='00:00:00'";
                   $query333 = $this->db->query("select SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out from AttendanceMaster where  EmployeeId =$EId and if(timeoutdate = '0000-00-00' , TimeOut  <  '$shiftout' , CONCAT(timeoutdate,' ' ,TimeOut)  <  '$shiftout1' ) and AttendanceDate='$date' and TimeOut !='00:00:00' ");
                   
                   
                   if ($row333 = $query333->row()) {
                       $a               = new DateTime($row333->TimeOut);
                       $b               = new DateTime($data123->TimeOut);
                       $interval        = $a->diff($b);
                       $data1['earlyby'] = $interval->format("%H:%I");
                       $data1['timeout'] = substr($row333->TimeOut, 0, 5);
                       $data1['name']  = $row->FirstName . ' ' . $row->LastName;
                       $data1['shift'] = $shift;
                       $data1['status'] = $row333->status;
                       $data1['TimeIn']  =     $row333->TimeIn;
                       $data1['TimeOut']  =  $row333->TimeOut;
                       $data1['CheckOutLoc']  =  $row333->CheckOutLoc;
                       $data1['checkInLoc']  =  $row333->checkInLoc;
                       $data1['EntryImage']  =  $row333->EntryImage;
                       $data1['ExitImage']  =  $row333->ExitImage;
                       $data1['latit_in']  =  $row333->latit_in;
                       $data1['longi_in']  =  $row333->longi_in;
                       $data1['latit_out']  =  $row333->latit_out;
                       $data1['longi_out']  =  $row333->longi_out;
                       $data1['date']  = $date;
                       $res[]         = $data1;
                   }
               
           }
       }
         $data['earlyLeavings'] =	 $res;
           
   }		
   
       echo json_encode($data, JSON_NUMERIC_CHECK);
       
   }

    public function getSuspiciousAttn()
    {
       // getting counting of attending/onbreak/exits and not attending emps
       $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
       $att   = isset($_REQUEST['att']) ? $_REQUEST['att'] : 0;
       $date   = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
       $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : '';
       $empid   = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : ''; 
       $zone  = getTimeZone($orgid);
       date_default_timezone_set($zone);
       $date =date('Y-m-d',strtotime($date));
       $time = date('H:i:s');
       $data = array();
      $cdate = date('Y-m-d');
       $adminstatus = getAdminStatus($empid);
       $cond = "";
       $cond1 = "";
       if($adminstatus == '2')
       { 
            $dptid = getDepartmentIdByEmpID($empid);
           $cond = " AND Department = $dptid  ";
           $cond1 = " AND Dept_id = $dptid  ";
           
       }
      
      
        //today attendance
        if($datafor=='present'){
        $query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name ,Id, SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out,SuspiciousTimeInStatus,SuspiciousTimeOutStatus,TimeInConfidence,TimeOutConfidence,PersistedFaceTimeIn,PersistedFaceTimeOut FROM `AttendanceMaster` WHERE `AttendanceDate`=? $cond1 and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) and (SuspiciousTimeInStatus=1 or SuspiciousTimeOutStatus=1) AND EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = ? AND Is_Delete = 0 ) order by `name`", array($date,$orgid,$orgid));
           // $data['present']        = $query->result();
           // echo json_encode($data);
           // exit();
        $i=0;
               $data=array();
               $res=array();
           foreach ($query->result() as $row){
               
               // $data1['Name']='';
            //    $data1['profileimage']='';
            //    $data1['EntryExitImage']='';
            //    $data1['TimeInOut']='';
            //    $data1['TimeInOutConfidence']='';


               if( $row->SuspiciousTimeInStatus == 1){
                   $data1=array();
                   $data1['Id']= $row->Id;
                   $data1['Name']= $row->name;
                   $data1['profileimage']= $row->PersistedFaceTimeIn;
                   $data1['EntryExitImage']= $row->EntryImage;
                   $data1['TimeInOut']= ("Time In:".' '.$row->TimeIn );
                   //$data1['TimeInOut']= $row->TimeIn;
                   $data1['TimeInOutConfidence']= $row->TimeInConfidence;
                   $res[]=$data1;
                   $i++;
               }

               if( $row->SuspiciousTimeOutStatus == 1){
                   $data1=array();
                   $data1['Id']= $row->Id;
                   $data1['Name']= $row->name;
                   $data1['profileimage']= $row->PersistedFaceTimeOut;
                   $data1['EntryExitImage']= $row->ExitImage;
                   $data1['TimeInOut']= ("Time Out:".' '.$row->TimeOut );
                   //$data1['TimeInOut']= $row->TimeOut;
                   $data1['TimeInOutConfidence']= $row->TimeOutConfidence;
                   $res[]=$data1;
                      $i++;

               }
               

           }
           $data['present']=$res;
//print_r($res);
           
            }
   
       echo json_encode($data, JSON_NUMERIC_CHECK);
       
   }
   public function saveImageGrpAttFace()
   {

       $userid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
       $addr = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
       //$aid     = isset($_REQUEST['aid']) ? $_REQUEST['aid'] : 0;
       //$act     = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'TimeIn';
       $shiftId = '';
       $act = '';
       $aid = '';
       $orgid = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
       $latit = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
       $longi = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
       $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
       $FakeLocationStatus = isset($_REQUEST['FakeLocationStatus']) ? $_REQUEST['FakeLocationStatus'] : 0;
       $FakeLocationStatusTimeIn = 0;
       $FakeLocationStatusTimeOut = 0;
       $faceid = "";
       $personid = "";
       $confidence= "0";
       $personobj="0";
       $FirstName="";
       $statusatt="";
       $file="";

       $attImage = 0;
       $new_name = "https://ubitech.ubihrm.com/public/avatars/male.png";
       $attImage = getAttImageStatus($orgid);
		
       if($attImage){ // true, image must be uploaded. false, optional image
			 $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name)){
			Trace('image not uploaded--'.$userid);
			$result['status']=3;
			$result['errorMsg']='Error in moving the image. Try later.';
			$result['successMsg'] = '';
			echo json_encode($result);
			return;
			}	
			$new_name =IMGURL.$new_name;
		}
       
       // Go ahead if image is optional or image uploaded successfully
       $sql7 = "select PersonGroupId from licence_ubiattendance where OrganizationId = $orgid and Addon_FaceRecognition='1'";
       $query7 = $this
           ->db
           ->query($sql7);
       if ($row7 = $query7->row())
       {
           $persongroup_id = $row7->PersonGroupId;
           $flag = '1';

       }

       $faceid = getfaceid($new_name);
       if ($faceid != '0')
       {
           $personobj = face_identify($faceid, $persongroup_id);
           

        //    print_r($personobj);
        //    exit();
           if ($personobj == '0')
           {
               //print_r($personobj);die;
               $result['status'] = '6';
               $result['groupface'] ="FACE_ID_NOT_CREATED";
               $this
                   ->db
                   ->close();
               echo json_encode($result);
               return;
           }else{
                $personid=$personobj[0]->candidates[0]->personId;
                $confidence=$personobj[0]->candidates[0]->confidence;
           }

       }
       else
       {

           $result['status'] = '5';
           $result['groupface'] ="NO_FACE_DETECTED";
           $this
               ->db
               ->close();
           echo json_encode($result);
           return;
       }
       //echo $personid;
       //print_r($personid);
       //die();
       $sql6 = "select Id,shift,FirstName from EmployeeMaster where OrganizationId = $orgid and PersonId = '$personid' and archive=1";
       $query6 = $this
           ->db
           ->query($sql6);
       if ($row6 = $query6->row())
       {
           $employeeid = $row6->Id;
           $shiftId = $row6->shift;
           $FirstName = $row6->FirstName;
       }
       else{
            $result['status'] = '5';
            $result['groupface'] ="NO_FACE_DETECTED";
            $this
                ->db
                ->close();
            echo json_encode($result);
            return;
       }
        //print_r($employeeid);
        //print_r($shiftId);
        //print_r($FirstName);
        //die();

       $dept = getDepartmentIdByEmpID($employeeid);
       $desg = getDesignationIdByEmpID($employeeid);
       $hourltRate = getHourlyRateIdByEmpID($employeeid);

       $reportNotificationSent = 0;

       ////////---------------checking and marking "timeOff" stop (if exist)
       // $zone    = getTimeZone($orgid);
       $zone = getEmpTimeZone($employeeid, $orgid); // to set the timezone by employee country.
       date_default_timezone_set($zone);
       $stamp = date("Y-m-d H:i:s");
       $date = date("Y-m-d");
       $today = date("Y-m-d");
       $time = date("H:i") == "00:00" ? "23:59" : date("H:i");
       //echo $time;
       //AutoTimeOffEnd($userid, $orgid, $time, $date, $stamp, $addr, $latit, $longi); // auto timeOff end
       //AutoVisitOutEnd($userid, $orgid, $time, $addr, $latit, $longi);
       /////////// This query is from auto visit out/////////////
       //$query=$this->db->query("update `checkin_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00'",array('Auto Visit Out Punched',$userid));
       /////////// This query is from auto visit out/////////////
       $today = date('Y-m-d');

       ////////---------------checking and marking "timeOff" stop (if exist)--/end
       $count = 0;
       $orgname = "";
       $orgnameForNoti = "";
       $errorMsg = "";
       $successMsg = "";
       $status = 0;
       $resCode = 0;
       $serversts = 1;
       $sto = '00:00:00';
       $sti = '00:00:00';
       $shifttype = '';
       $data = array();
       $data['msg'] = 'Mark visit under process';
       $data['res'] = 0;

       //Group facial recognition code starts
       

       $query = $this
           ->db
           ->query("SELECT Addon_AutoTimeOut  FROM `licence_ubiattendance` WHERE OrganizationId=?", array(
           $orgid
       ));
       //Organization
       if ($row = $query->result())
       {

           $Addon_AutoTimeOut = $row[0]->Addon_AutoTimeOut;

       }
    //    print_r("hello nitin");
    //    die();
       $act = getaction($shiftId, $Addon_AutoTimeOut, $employeeid,$orgid);
       $aid = getaid($shiftId, $Addon_AutoTimeOut, $employeeid);
    //    print_r($act);
    //    print_r($aid);
    //    die();

       if ($act == 'TimeIn')
       {
           $FakeLocationStatusTimeIn = $FakeLocationStatus;
       }
       else
       {
           $FakeLocationStatusTimeOut = $FakeLocationStatus;
       }

       if ($act == 'Imposed')
       {
           $result['facerecog'] = '5';
           $result['groupface'] =$FirstName;
           $result['status'] = '4'; //for already marked attendance
           $result['successMsg'] = $successMsg;
           $result['errorMsg'] = $errorMsg;
           $this
               ->db
               ->close();
           echo json_encode($result);
           return;
       }

        if ($act == 'RecentlyMarked')
        {
            $result['facerecog'] = '5';
            $result['groupface'] =$FirstName;
            $result['status'] = '7'; //for recently marked attendance
            $result['successMsg'] = $successMsg;
            $result['errorMsg'] = $errorMsg;
            $this
                ->db
                ->close();
            echo json_encode($result);
            return;
        }

       //   $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
       /*    if (move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name))
       
       //if(true)
           {*/
       $sql = '';
       //////----------------getting shift info
       $stype = 0;
       $sql1 = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;

       try
       {
           $result1 = $this
               ->db
               ->query($sql1);
           if ($row1 = $result1->row())
           {
               $stype = $row1->stype;
               $sti = $row1->TimeIn;
               $sto = $row1->TimeOut;
               $shifttype = $row1->shifttype;
           }
       }
       catch(Exception $e)
       {
           Trace('Error_3: ' . $e->getMessage());
       }
       if ($shifttype == 2 && $act == 'TimeIn')
       { // multi date shift case
           if ($time < $sto)
           { // time in should mark in last day date
               try
               {
                   $ldate = date("Y-m-d", strtotime("-1 days"));
                   $sql = "select Id as ttl from AttendanceMaster where AttendanceDate='$ldate' and EmployeeId=$employeeid";
                   $res = $this
                       ->db
                       ->query($sql);
                   if ($res->num_rows() > 0)
                   { // if attn already marked in previous date
                       $date = date("Y-m-d");
                   }
                   else $date = date("Y-m-d", strtotime("-1 days"));

               }
               catch(Exception $e)
               {

               }
           }
           //else  time in should mark in current day's date
           
       }
       else if ($shifttype == 2 && $act == 'TimeOut')
       {
           if ($time > $sti)
           { // time in should mark in last day date
               try
               {

                   $date = date("Y-m-d", strtotime("-1 days"));
               }
               catch(Exception $e)
               {

               }
           }
       }

       //	echo $date;
       //	return false;
       //////----------------/gettign shift info
       Trace($act . ' AID' . $aid . 'UserId' . $employeeid);
       if ($aid == 0 && $act == 'TimeOut')
       {
           $sqlId = "select Id from  AttendanceMaster where EmployeeId=$employeeid and TimeOut='00:00:00' Order by AttendanceDate desc Limit 1";
           $resId = $this
               ->db
               ->query($sqlId);
           if ($rowId = $resId->row())
           {
               $aid = $rowId->Id;
           }
           Trace('After Fetch: ' . $act . ' AID' . $aid . 'UserId' . $employeeid);
       }
       /*********
       EmployeeMaster
       ***********/
       if ($aid != 0 && $act != 'TimeIn') //////////////updating path of employee profile picture in database/////////////
       
       {

           if ($stype < 0)
           { //// if shift is end whthin same date
               $statusatt="2";
               

               $sql = "UPDATE `AttendanceMaster` SET  `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time',TimeOutFaceId='$faceid',TimeOutConfidence=$confidence, LastModifiedDate='$stamp',overtime =(SELECT subtime(TIMEDIFF ( CONCAT('$date', ' ','$time'),CONCAT(AttendanceDate , '  ', timein)),
               (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$date' WHERE id=$aid and `EmployeeId`=$employeeid   and TimeOut='00:00:00'"; //and SUBTIME(  `TimeOut` ,  `TimeIn` ) >'00:05:00'";
               
           }
           else
           {
               //////getting timein information
               $statusatt="2";
               $sql = "select Timein as timein_time, Attendancedate as timein_date from AttendanceMaster where Id=" . $aid;
               $timein_date = '';
               $timein_time = '';
               $res = $this
                   ->db
                   ->query($sql);
               if ($r = $res->result())
               {
                   $timein_date = $r[0]->timein_date;
                   $timein_time = $r[0]->timein_time;
               }
               //////getting timein information/
               /*	echo $timein_date.' '.$timein_time;
               echo '---';
               echo $date.' '.$time;
               echo '***';
               */
               // shift hours
               $shiftHours = '';
               $sql = "select subtime('24:00:00',subtime(timein,timeout)) as shiftHours from ShiftMaster where Id=$shiftId";
               //$sql="select subtime('30:00:00','21:00:00') as shiftHours from ShiftMaster where id=$shiftId";
               $res = $this
                   ->db
                   ->query($sql);
               if ($r = $res->result()) $shiftHours = $r[0]->shiftHours;

               // time spent
               //		echo $timein_date.' '.$timein_time.'-------';
               //		echo $date.' '.$time.'-------';
               $start = date_create($timein_date . ' ' . $timein_time);
               $end = date_create($date . ' ' . $time);
               $diff = date_diff($end, $start);
               $hrs = 0;
               if ($diff->d == 1) // if shift is running more than 24 hrs
               $hrs = 24;
               $timeSpent = str_pad($hrs + $diff->h, 2, "0", STR_PAD_LEFT) . ':' . str_pad($diff->i, 2, "0", STR_PAD_LEFT) . ':00';

               //echo 'TimeSpent:'.$timeSpent;
               //echo 'shiftHours:'.$shiftHours;
               

               $sql = "UPDATE `AttendanceMaster` SET `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time',TimeOutFaceId='$faceid',TimeOutConfidence=$confidence, LastModifiedDate='$stamp' ,overtime = subtime('$timeSpent','$shiftHours'),timeoutdate='$date'
               WHERE id=$aid and `EmployeeId`=$employeeid and TimeOut='00:00:00' ORDER BY `AttendanceDate` DESC LIMIT 1";
               //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
               
           }
           /*   $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp' ,overtime =(SELECT subtime(subtime('$time',timein),
               (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$date'
               WHERE id=$aid and `EmployeeId`=$userid  ORDER BY `AttendanceDate` DESC LIMIT 1";*/
           //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
           //----------push check code
           try
           {
               $push = "push/";
               if (!file_exists($push)) mkdir($push, 0777, true);
               $filename = $push . $orgid . ".log";
               $fp = fopen($filename, "a+");
               fclose($fp);
           }
           catch(Exception $e)
           {
               echo $e->getMessage();
           }
           //----------push check code
           
       } //LastModifiedDate
       else
       {
           ///-------- code for prevent duplicacy in a same day   code-001
           $sql = "select * from  AttendanceMaster where EmployeeId=$employeeid and AttendanceDate= '$today'";

           try
           {
               $result1 = $this
                   ->db
                   ->query($sql);
               if ($this
                   ->db
                   ->affected_rows() < 1)
               { ///////code-001 (ends)
                   $area = getAreaId($employeeid);
                   if ($orgid == '10932')
                   { // only for welspun
                       $area = getNearLocationOfEmp($latit, $longi, $employeeid);
                   }
                   $statusatt="1";

                   $sql = "INSERT INTO `AttendanceMaster`(`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`,`EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`,`ShiftId`,Dept_id,Desg_id,areaId,HourlyRateId, `OrganizationId`,
     `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `Overtime`, `EntryImage`, `checkInLoc`,`device`,latit_in,longi_in,timeindate,Platform,TimeInFaceId,TimeInConfidence)
     VALUES ($FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,$employeeid,'$date',1,'$time',$shiftId,$dept,$desg,$area,$hourltRate,$orgid,'$date',$userid,'$stamp',$userid,$userid,'00:00:00','" . $new_name . "','$addr','mobile','$latit','$longi','$today',' $platform','$faceid',$confidence)";
                   Trace('User Attendance: ' . $employeeid . ' ' . $sql);

               }
               else $sql = '';
           }
           catch(Exception $e)
           {
               Trace('Error_2: ' . $e->getMessage());
               $errorMsg = 'Message: ' . $e->getMessage();
               $status = 0;
           }
       }
       try
       {
           $query = $this
               ->db
               ->query($sql);
           if ($this
               ->db
               ->affected_rows() > 0 && $act == 'TimeIn')
           {
               //----------push check code
               try
               {
                   $push = "push/";
                   if (!file_exists($push)) mkdir($push, 0777, true);
                   $filename = $push . $orgid . ".log";
                   $fp = fopen($filename, "a+");
                   fclose($fp);
               }
               catch(Exception $e)
               {
                   echo $e->getMessage();
               }
               //----------push check code
               $resCode = 0;
               $status = 1; // update successfully
               $successMsg = "Image uploaded successfully.";
               //////////////////----------------mail send if attndnce is marked very first time in org ever
               $sql = "SELECT  `Email`,ReportNotificationSent,Name  FROM `Organization` WHERE `Id`=" . $orgid;
               $to = '';
               $query1 = $this
                   ->db
                   ->query($sql);
               if ($row = $query1->result())
               {
                   $to = $row[0]->Email;
                   $reportNotificationSent = $row[0]->ReportNotificationSent;
                   $orgname = $row[0]->Name;

               }

               //////////////////----------------/mail send if attndnce is marked very first time in org ever
               
           }
           else
           {
               $status = 2; // no changes found
               $errorMsg .= "Failed to upload Image/No Check In found today.";
           }
       }
       catch(Exception $e)
       {
           Trace('Error_1: ' . $e->getMessage());
           $errorMsg = 'Message: ' . $e->getMessage();
           $status = 0;
       }
       /*  } else {
           Trace('image not uploaded--');
           $status   = 3; // error in uploading image
           $errorMsg = 'Message: error in uploading image';
       }*/

       //emp
       $result['status'] = $status;
       $result['successMsg'] = $successMsg;
       $result['errorMsg'] = $errorMsg;
       $result['groupface'] =$FirstName;
       $result['statusatt'] =$statusatt;
       //$result['location']=$addr;
       /***    Logic for sending first time in  push notification of employee to admin  ****/
       $EmployeeName = '';
       if ($reportNotificationSent == 0)
       {
           $query1 = $this
               ->db
               ->query("SELECT count(*) as count FROM `AttendanceMaster` as A inner join UserMaster as U where A.OrganizationId=$orgid and A.EmployeeId=U.EmployeeId and U.appSuperviserSts=0 ");
           if ($row = $query1->result())
           {
               $count = $row[0]->count;
               if ($count == 1)
               {
                   $sqlId = "select FirstName from  EmployeeMaster where Id=$employeeid";
                   $resId = $this
                       ->db
                       ->query($sqlId);
                   if ($rowId = $resId->row())
                   {
                       $EmployeeName = $rowId->FirstName;
                   }
                   $orgnameForNoti = ucwords($orgname);
                   $orgnameForNoti = preg_replace("/[^a-zA-Z]+/", "", $orgnameForNoti);
                   $orgnameForNoti = str_replace(".", "", $orgnameForNoti . $orgid);
                   sendManualPushNotification("('$orgnameForNoti' in topics) && ('admin' in topics) ", "Bingo! $EmployeeName has punched Time in.", "You can check his Attendance");
                   $this
                       ->db
                       ->query("update Organization set ReportNotificationSent=1 where Id=$orgid");
               }

           }
       }
       /***    Logic for sending first time in push notification of employee to admin   ****/
       $this
           ->db
           ->close();
       echo json_encode($result);

   }

     public function disapprovesuspiciousattn()
	{
		//var_dump($date);
		$id = isset($_REQUEST['id'])?$_REQUEST['id']:"";
		$newtopic = isset($_REQUEST['newtopic'])?$_REQUEST['newtopic']:"";
		$device='Suspicious Selfie';
         //var_dump($absent);

		 $sql2 = "select * From AttendanceMaster Where Id=$id";
       $query2 = $this
           ->db
           ->query($sql2);
       if ($row2 = $query2->row())
       {
           $empid = $row2->EmployeeId;
           // print_r($personid);
           // print_r($persistedfaceid);
           // die();

       }

		 $sql3 = "select * From EmployeeMaster Where Id=$empid";
       $query3 = $this
           ->db
           ->query($sql3);
       if ($row3 = $query3->row())
       {
           $name = $row3->FirstName;
           // print_r($personid);
           // print_r($persistedfaceid);
           // die();

       }

         $query=$this->db->query("UPDATE AttendanceMaster SET AttendanceStatus = 2,device=? where Id=? ",array($device,$id)); 
         //var_dump($this->db->last_query()); 

              $res= $this->db->affected_rows();	
              if($res){
              $data['status'] = 'Attendance disapproved successfully';
              sendManualPushNotification("('$newtopic' in topics)", "$name Attendance has been disapproved. He will marked absent.", "");
          }else{
          	$data['status'] = 'Unable to disapprove attendance';
          }

			  $this->db->close();
			  echo json_encode($data, JSON_NUMERIC_CHECK);
	}
	
	public function getCDateAttnDeptWise_new()
    {
        // getting counting of attending/onbreak/exits and not attending emps
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $att   = isset($_REQUEST['att']) ? $_REQUEST['att'] : 0;
        $date   = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        $dept   = isset($_REQUEST['dept']) ? $_REQUEST['dept'] : '';
        $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : '';
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d',strtotime($date));
        $time = date('H:i:s');
        $data = array();
       $cdate = date('Y-m-d');
	   
	   $dept_cond='';
	   $dept_cond1 ='';
	   if($dept!=0)
	   {
		   $dept_cond = ' and Dept_id='.$dept;
		   $dept_cond1 = ' and Department='.$dept;
	   }
	   
         //today attendance
		 if($datafor=='present'){
		$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE `AttendanceDate`=? ".$dept_cond." and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `name`",
		array($date,$orgid));
            $data['present']        = $query->result();
			
			 }else if($datafor=='absent'){
				 /*
            //---managing off (weekly and holiday)
            $dt                   = $date;          
            //    day of month : 1 sun 2 mon --
            $dayOfWeek   = 1 + date('w', strtotime($dt));
            $weekOfMonth = weekOfMonth($dt);
            $week        = '';
            $query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
                $orgid,
                $dayOfWeek
            ));
            if ($row = $query->result()) {
                $week = explode(",", $row[0]->WeekOff);
            }
            if ($week[$weekOfMonth - 1] == 1) {
                $data['absentees'] = '';
            } else {
                $query = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
                    $orgid,
                    $dt
                ));
                if ($query->num_rows() > 0) {
                    //-----managing off (weekly and holiday) - close            
                    $query             = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status from EmployeeMaster where `OrganizationId` =$orgid and EmployeeMaster.archive=1 and EmployeeMaster.Id not in(select AttendanceMaster.`EmployeeId` from AttendanceMaster where `AttendanceDate`='$date' and `OrganizationId` =$orgid) and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift) order by `name`", array(
                        $orgid,
                        $date,
                        $orgid
                    ));
                    $data['absentees'] = $query->result();
                } 
            }*/
			//////////abs
	
			
			
			if($date!=date('Y-m-d')){// for other deay's absentees
				$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , '-' as `TimeOut` ,'-' as TimeIn FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=2 or AttendanceStatus=6 or AttendanceStatus=7 ) ". $dept_cond  ." order by `name`", array($date,$orgid));
				$this->db->close();
				$data['absent'] =  $query->result();
			}else{ // for today's absentees
				$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , '-' as `TimeOut` ,'-' as TimeIn FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? 
and AttendanceStatus in (2,6,7)  ". $dept_cond ."   order by `name`",array($date,$orgid));
			$temp=array();
            $temp =  $query->result();
			
			$query  = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name, '-' as `TimeOut` ,'-' as TimeIn
						FROM  `EmployeeMaster` 
						WHERE  `OrganizationId` =?
						AND is_Delete = 0 and  ARCHIVE =1 ". $dept_cond1." 
						AND Id NOT 
						IN (
						SELECT EmployeeId
						FROM AttendanceMaster
						WHERE AttendanceDate =  ?
						AND  `OrganizationId` =?
						)
						AND (
						SELECT  `TimeIn` 
						FROM  `ShiftMaster` 
						WHERE  `Id` = Shift
						AND TimeIn <  ?
						)",array($orgid,$date,$orgid,$time));
						$data['absent']= array_merge($temp,$query->result());
			}
	}else if($datafor=='latecomings'){
	//	echo "SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`='$date' ".$dept_cond." and  OrganizationId='$orgid' and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `name`";
        //////// today_late
            $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? ".$dept_cond." and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `name`", array(
                $date,
                $orgid
            ));
            $data['lateComings'] = $query->result();
	}else if($datafor=='earlyleavings'){		
        ////////today_early
		
        /*  $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? ".$dept_cond." and  OrganizationId=? and  AttendanceStatus in (1,3,4,5,8) order by `name`", array( $date, $orgid ));
            $data['earlyLeavings'] = $query->result();  */
			
		 $query = $this->db->query("select Shift,Id , FirstName , LastName  from EmployeeMaster where OrganizationId = $orgid and Id IN (select EmployeeId from AttendanceMaster where OrganizationId = $orgid and AttendanceDate='$date' and TimeIn != '00:00:00'  $dept_cond ) AND is_Delete=0 order by FirstName");
		 $res   = array();
		 $cond  = '';
        foreach ($query->result() as $row) {
            $ShiftId = $row->Shift;
            $EId     = $row->Id;
            $query   = $this->db->query("select TimeIn,TimeOut,shifttype from ShiftMaster where Id = $ShiftId");
            if ($data123 = $query->row()) {
                $shiftout = $data123->TimeOut;
                $shiftout1 = $date. ' '.$data123->TimeOut;
				if($data123->shifttype==2)
				{
					$nextdate = date('Y-m-d',strtotime($date . "+1 days"));
					 $shiftout1 = $nextdate.' '.$data123->TimeOut;
				}
                $shift    = substr($data123->TimeIn, 0, 5) . ' - ' . substr($data123->TimeOut, 0, 5);
                $ct       = date('H:i:s');
               
                    if ($cdate == $date)
                        $cond = "    and TimeOut !='00:00:00'";
                    $query333 = $this->db->query("select SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out from AttendanceMaster where  EmployeeId =$EId and if(timeoutdate = '0000-00-00' , TimeOut  <  '$shiftout' , CONCAT(timeoutdate,' ' ,TimeOut)  <  '$shiftout1' ) and AttendanceDate='$date'" . $cond);
					
					
                    if ($row333 = $query333->row()) {
                        $a               = new DateTime($row333->TimeOut);
                        $b               = new DateTime($data123->TimeOut);
                        $interval        = $a->diff($b);
                        $data['earlyby'] = $interval->format("%H:%I");
                        $data['timeout'] = substr($row333->TimeOut, 0, 5);
                        $data['name']  = $row->FirstName . ' ' . $row->LastName;
                        $data['shift'] = $shift;
                        $data['TimeIn']  =     $row333->TimeIn;
                        $data['TimeOut']  =  $row333->TimeOut;
                        $data['CheckOutLoc']  =  $row333->CheckOutLoc;
                        $data['checkInLoc']  =  $row333->checkInLoc;
                        $data['latit_in']  =  $row333->latit_in;
                        $data['longi_in']  =  $row333->longi_in;
                        $data['latit_out']  =  $row333->latit_out;
                        $data['longi_out']  =  $row333->longi_out;
                        $data['status']  =  $row333->status;
                        $data['date']  = $date;
                        $res[]         = $data;
                    }
                
            }
        }
		  $data['earlyLeavings'] =	 $res; 
     	}
        echo json_encode($data, JSON_NUMERIC_CHECK);
    }
	
	public function getEmpdataDepartmentWise()
    {
		$orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $date   = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d',strtotime($date));
		$todaydate = date('Y-m-d');
		
        $time = date('H:i:s');
        $data = array();
		
		if(strtotime($todaydate) == strtotime($date) )
			$query = $this->db->query("SELECT id , name , (select count(EmployeeMaster.id) from EmployeeMaster where Department = DepartmentMaster.Id and `OrganizationId` = $orgid and Is_Delete=0 and archive = 1 )  as total, (select count(AttendanceMaster.id) from AttendanceMaster where Dept_id = DepartmentMaster.Id and AttendanceStatus in (1,4,8) AND AttendanceDate = '$date' )  as 'present', (Select count(ID) from EmployeeMaster where  Department = DepartmentMaster.Id AND OrganizationId = $orgid AND Is_Delete=0 and archive = 1    AND ( ID NOT IN (SELECT EmployeeId from AttendanceMaster where Dept_id = DepartmentMaster.Id  AND AttendanceStatus in (1,4,8) AND AttendanceDate = '$date')  AND  Shift NOT IN (Select id from ShiftMaster where OrganizationId = '$orgid' AND TimeIn > '$time') )) as 'absent' FROM  DepartmentMaster WHERE `OrganizationId` = $orgid" );
	   else
			$query = $this->db->query("SELECT id , name , (select count(EmployeeMaster.id) from EmployeeMaster where Department = DepartmentMaster.Id and Is_Delete=0 and archive = 1 )  as total, (select count(AttendanceMaster.id) from AttendanceMaster where Dept_id = DepartmentMaster.Id and AttendanceStatus in (1,4,8) AND AttendanceDate = '$date' )  as 'present', (Select count(ID) from EmployeeMaster where  Department = DepartmentMaster.Id AND OrganizationId = $orgid AND Is_Delete=0 and archive = 1    AND ( ID NOT IN (SELECT EmployeeId from AttendanceMaster where Dept_id = DepartmentMaster.Id  AND AttendanceStatus in (1,4,8) AND AttendanceDate = '$date'))) as 'absent' FROM  DepartmentMaster WHERE `OrganizationId` = $orgid" );
	
		$data['departments'] = $query->result();
        echo json_encode($data, JSON_NUMERIC_CHECK);
	}
	
	public function getEmpdataDepartmentWiseCount()
    {
		$orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $date   = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d',strtotime($date));
		$todaydate = date('Y-m-d');
		
        $time = date('H:i:s');
        $data = array();
		$data['departments'] =0;
		$data['present'] =0;
		$data['absent'] =0;
		$data['total'] =0;
		if(strtotime($todaydate) == strtotime($date) ){
			$query = $this->db->query("SELECT count(id) as departments, (select count(id) from EmployeeMaster where `OrganizationId` = $orgid and Is_Delete=0 and archive = 1 )  as total, (select count(id) from AttendanceMaster where AttendanceStatus in (1,4,8) AND AttendanceDate = '$date' and OrganizationId=$orgid)  as 'present', (Select count(ID) from EmployeeMaster where OrganizationId = $orgid AND Is_Delete=0 and archive = 1 AND ( ID NOT IN (SELECT EmployeeId from AttendanceMaster  where OrganizationId = $orgid AND AttendanceStatus in (1,4,8) AND AttendanceDate = '$date')  AND  Shift NOT IN (Select id from ShiftMaster where OrganizationId = '$orgid' AND TimeIn > '$time') )) as 'absent' FROM  DepartmentMaster WHERE `OrganizationId` = $orgid" );
			
			if($row =$query->result()){
					$data['departments'] = $row[0]->departments;
					$data['total'] =$row[0]->total;
					$data['present'] =$row[0]->present;
					$data['absent'] =$row[0]->absent;
			}
		}
	   else{
		   $query = $this->db->query("SELECT count(id) as departments,(select count(id) from EmployeeMaster where OrganizationId = $orgid and Is_Delete=0 and archive = 1 )  as total, (select count(id) from AttendanceMaster where AttendanceStatus in (1,4,8) AND AttendanceDate = '$date' and OrganizationId=$orgid )  as 'present', (Select count(ID) from EmployeeMaster where OrganizationId = $orgid AND Is_Delete=0 and archive = 1 AND ( ID NOT IN (SELECT EmployeeId from AttendanceMaster where OrganizationId = $orgid AND AttendanceStatus in (1,4,8) AND AttendanceDate = '$date'))) as 'absent' FROM  DepartmentMaster WHERE `OrganizationId` = $orgid" );
	
			if($row =$query->result()){
				$data['departments'] = $row[0]->departments;
				$data['total'] =$row[0]->total;
				$data['present'] =$row[0]->present;
				$data['absent'] =$row[0]->absent;
			}
	   }
        echo json_encode($data, JSON_NUMERIC_CHECK);
	}
	
	
	

///////////// single emp history of last 30 days
	public function getEmpHistoryOf30()
    {
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $emp   = isset($_REQUEST['emp']) ? $_REQUEST['emp'] : 0;
        $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : '';
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d');
        $edate =date('Y-m-d',strtotime('-30 days'));
        $time = date('H:i:s');
        $data = array();
		if($datafor=='present'){
		//echo "SELECT AttendanceDate,SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,EntryImage,ExitImage,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (`AttendanceDate` BETWEEN '".$edate."' AND '".$date."') and  EmployeeId=$emp AND  OrganizationId = '$orgid' and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `AttendanceDate` Desc";
		$query  = $this->db->query("SELECT AttendanceDate,SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,EntryImage,ExitImage,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (`AttendanceDate` BETWEEN '".$edate."' AND '".$date."') and  EmployeeId=$emp AND  OrganizationId = '$orgid' and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `AttendanceDate` Desc");
            $data['present']        = $query->result();
		} else if($datafor=='absent'){
			//////////abs
			$query  = $this->db->query("SELECT AttendanceDate,'-' as TimeIn,'-' as TimeOut FROM `AttendanceMaster` WHERE (`AttendanceDate` BETWEEN ? AND ?) and  EmployeeId=? and (AttendanceStatus=2 or AttendanceStatus=7 or AttendanceStatus=6 ) order by `AttendanceDate` Desc",
		array($edate,$date,$emp));
            $data['absent'] =  $query->result();
			
	}else if($datafor=='latecomings'){
        //////// late
		
         /*   $query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? ".$dept_cond." and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `name`", array(
                $date,
                $orgid
            ));*/
			$query  = $this->db->query("SELECT AttendanceDate,SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,EntryImage,ExitImage,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (`AttendanceDate` BETWEEN ? AND ?) and (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and  EmployeeId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `AttendanceDate` Desc",
		array($edate,$date,$emp));
            $data['lateComings'] = $query->result();
	}else if($datafor=='earlyleavings'){		
        ////////early
           /*$query  = $this->db->query("SELECT AttendanceDate,SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,EntryImage,ExitImage,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (`AttendanceDate` BETWEEN ? AND ?) and (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00') and  EmployeeId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `AttendanceDate` Desc",
		array($edate,$date,$emp));*/
			  $query = $this->db->query("select ShiftId,EmployeeId , AttendanceDate from AttendanceMaster where  `AttendanceDate` BETWEEN ? AND ? and TimeIn != '00:00:00' AND TimeOut!='00:00:00' AND AttendanceStatus in (1,3,4,5,8) and  EmployeeId = ?  order by `AttendanceDate` Desc " , array($edate,$date,$emp));
		 $res   = array();
		 $name  = getEmpName($emp);
        foreach ($query->result() as $row) {
            $ShiftId = $row->ShiftId;
           
			$attendanceDate = $row->AttendanceDate;
            $query   = $this->db->query("select TimeIn,TimeOut,shifttype from ShiftMaster where Id = '".$ShiftId."' ");
            if ($data123 = $query->row()) {
                $shiftout = $data123->TimeOut;
                $shiftout1 = $attendanceDate. ' '.$data123->TimeOut;
				if($data123->shifttype==2)
				{
					$nextdate = date('Y-m-d',strtotime($attendanceDate . "+1 days"));
					 $shiftout1 = $nextdate.' '.$data123->TimeOut;
				}
                $shift    = substr($data123->TimeIn, 0, 5) . ' - ' . substr($data123->TimeOut, 0, 5);
                $ct      = date('H:i:s');
               
                
                    $query333 = $this->db->query("select SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out from AttendanceMaster where  EmployeeId = $emp and if(timeoutdate = '0000-00-00' , TimeOut  <  '$shiftout' , CONCAT(timeoutdate,' ' ,TimeOut)  <  '$shiftout1' ) and AttendanceDate='$attendanceDate' " );
					
					
                    if ($row333 = $query333->row()) {
                        $a               = new DateTime($row333->TimeOut);
                        $b               = new DateTime($data123->TimeOut);
                        $interval        = $a->diff($b);
                        $data1['earlyby'] = $interval->format("%H:%I");
                        $data1['timeout'] = substr($row333->TimeOut, 0, 5);
                        $data1['name']  = $name ;
                        $data1['shift'] = $shift;
                        $data1['TimeIn']  =     $row333->TimeIn;
                        $data1['TimeOut']  =  $row333->TimeOut;
                        $data1['CheckOutLoc']  =  $row333->CheckOutLoc;
                        $data1['checkInLoc']  =  $row333->checkInLoc;
                        $data1['latit_in']  =  $row333->latit_in;
                        $data1['longi_in']  =  $row333->longi_in;
                        $data1['latit_out']  =  $row333->latit_out;
                        $data1['EntryImage']  =  $row333->EntryImage;
                        $data1['ExitImage']  =  $row333->ExitImage;
                        $data1['longi_out']  =  $row333->longi_out;
                        $data1['AttendanceDate']  = $attendanceDate;
                        $res[]         = $data1;
                    }
            }
        }
		  $data['earlyLeavings'] =	 $res;
	}	 
        echo json_encode($data, JSON_NUMERIC_CHECK);
    }
	
	public function getEmpHistoryOf30Count()
    {
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $emp   = isset($_REQUEST['emp']) ? $_REQUEST['emp'] : 0;
        $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : '';
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d');
        $edate =date('Y-m-d',strtotime('-30 days'));
        $time = date('H:i:s');
        $data = array();
		$data['present'] =0;
		$data['absent'] =0;
		$data['latecomings'] =0;
		$data['earlyleavings'] =0;
		
		//////////pre
		 $sql1 = "SELECT count(id) as present FROM `AttendanceMaster` WHERE (`AttendanceDate` BETWEEN '".$edate."' AND '".$date."') and  EmployeeId=$emp AND  OrganizationId = '$orgid' and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )";
		$query  = $this->db->query($sql1);
		if($row =$query->result())
					$data['present'] = $row[0]->present;
		
		//////////abs
		 $sql2 = "SELECT count(id) as absent FROM `AttendanceMaster` WHERE (`AttendanceDate` BETWEEN '".$edate."' AND '".$date."') and  EmployeeId=$emp and (AttendanceStatus=2 or AttendanceStatus=7 or AttendanceStatus=6 )";
		$query  = $this->db->query($sql2);
            if($row =$query->result())
					$data['absent'] = $row[0]->absent;
			
        //////// late
		 $sql3 = "SELECT count(id) as latecomings FROM `AttendanceMaster` WHERE (`AttendanceDate` BETWEEN '".$edate."' AND '".$date."') and (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and  EmployeeId=$emp and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )";
		$query  = $this->db->query($sql3);
             if($row =$query->result())
					$data['latecomings'] = $row[0]->latecomings;
	
        ////////early
		 $sql4 = "select count(Id) as earlyleavings from AttendanceMaster where (AttendanceDate BETWEEN  '".$edate."' and '".$date."') and `OrganizationId`=$orgid and TimeOut !='00:00:00' and time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and AttendanceStatus in (1,3,4,5,8) AND EmployeeId = $emp and EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) ";
        $query = $this->db->query($sql4);
			if($row =$query->result())
					$data['earlyleavings'] = $row[0]->earlyleavings;
	
        echo json_encode($data);
    }
	
	public function getCDateAttnDesgWise_new()
    {
        // getting counting of attending/onbreak/exits and not attending emps
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $att   = isset($_REQUEST['att']) ? $_REQUEST['att'] : 0;
        $date   = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        $desg   = isset($_REQUEST['desg']) ? $_REQUEST['desg'] : '';
        $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : '';
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d',strtotime($date));
        $time = date('H:i:s');
        $data = array();
		$cdate = date('Y-m-d');
 
		  $desg_cond='';
		  $desg_cond1='';
		 
		 
		  if($desg!=0)
		  {
		  $desg_cond=' and Desg_id='.$desg;
		  $desg_cond1 =' and Designation='.$desg;
		  }
		 
				 //today attendance
		if($datafor=='present'){
			$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE `AttendanceDate`=? ".$desg_cond." and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0 ) order by `name`",
			array($date,$orgid));
				$data['present']        = $query->result();

		}else if($datafor=='absent'){/*
					//---managing off (weekly and holiday)
					$dt                   = $date;          
					//    day of month : 1 sun 2 mon --
					$dayOfWeek   = 1 + date('w', strtotime($dt));
					$weekOfMonth = weekOfMonth($dt);
					$week        = '';
					$query       = $this->db->query("SELECT `WeekOff` FROM  `WeekOffMaster` WHERE  `OrganizationId` =? AND  `Day` =  ?", array(
						$orgid,
						$dayOfWeek
					));
					if ($row = $query->result()) {
						$week = explode(",", $row[0]->WeekOff);
					}
					if ($week[$weekOfMonth - 1] == 1) {
						$data['absentees'] = '';
					} else {
						$query = $this->db->query("SELECT `DateFrom`, `DateTo` FROM `HolidayMaster` WHERE OrganizationId=? and (? between `DateFrom` and `DateTo`) ", array(
							$orgid,
							$dt
						));
						if ($query->num_rows() > 0) {
							//-----managing off (weekly and holiday) - close            
							$query             = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name,'-' as TimeIn,'-' as TimeOut ,'Absent' as status from EmployeeMaster where `OrganizationId` =$orgid and EmployeeMaster.archive=1 and EmployeeMaster.Id not in(select AttendanceMaster.`EmployeeId` from AttendanceMaster where `AttendanceDate`='$date' and `OrganizationId` =$orgid) and CAST('$time' as time) > (select TimeIn from ShiftMaster where ShiftMaster.Id=shift) order by `name`", array(
								$orgid,
								$date,
								$orgid
							));
							$data['absentees'] = $query->result();
						}
					}*/
		//////////abs



			if($date!=date('Y-m-d')){// for other deay's absentees
				$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , '-' as `TimeOut` ,'-' as TimeIn FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=2 or AttendanceStatus=6 or AttendanceStatus=7 )  ".$desg_cond."  order by `name`", array($date,$orgid));
				$this->db->close();
				$data['absent'] =  $query->result();
			}else{ // for today's absentees
				$query  = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , '-' as `TimeOut` ,'-' as TimeIn FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and AttendanceStatus in (2,6,7) ".$desg_cond."  order by `name`",array($date,$orgid));
				$temp=array();
					$temp =  $query->result();

				$query  = $this->db->query("SELECT CONCAT(FirstName,' ',LastName) as name, '-' as `TimeOut` ,'-' as TimeIn
				FROM  `EmployeeMaster`
				WHERE  `OrganizationId` =?
				AND ARCHIVE =1 ".$desg_cond1."
				AND is_Delete = 0 AND  Id NOT
				IN (
				SELECT EmployeeId
				FROM AttendanceMaster
				WHERE AttendanceDate =  ?
				AND  `OrganizationId` =?
				)
				AND (
				SELECT  `TimeIn`
				FROM  `ShiftMaster`
				WHERE  `Id` = Shift
				AND TimeIn <  ?
				)",array($orgid,$date,$orgid,$time));
				$data['absent']= array_merge($temp,$query->result());
			}

		}else if($datafor=='latecomings'){
		// echo "SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`='$date' ".$desg_cond." and  OrganizationId='$orgid' and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `name`";
				//////// today_late
			$query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? ".$desg_cond." and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `name`", array(
						$date,
						$orgid
					));
			$data['lateComings'] = $query->result();
		}else if($datafor=='earlyleavings'){
			////////today_early
			/*$query         = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? ".$desg_cond." and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) order by `name`", array( $date,   $orgid  ));
			$data['earlyLeavings'] = $query->result();*/

			$query = $this->db->query("select Shift,Id , FirstName , LastName  from EmployeeMaster where OrganizationId = $orgid and Id IN (select EmployeeId from AttendanceMaster where OrganizationId = $orgid and AttendanceDate='$date' and TimeIn != '00:00:00'  $desg_cond ) AND is_Delete=0 order by FirstName");
			$res   = array() ;
			$cond  = '';
				foreach ($query->result() as $row) {
					$ShiftId = $row->Shift;
					$EId     = $row->Id;
					$query   = $this->db->query("select TimeIn,TimeOut,shifttype from ShiftMaster where Id = $ShiftId");
					if ($data123 = $query->row()) {
						$shiftout = $data123->TimeOut;
						$shiftout1 = $date. ' '.$data123->TimeOut;
						if($data123->shifttype==2)
						{
						$nextdate = date('Y-m-d',strtotime($date . "+1 days"));
						$shiftout1 = $nextdate.' '.$data123->TimeOut;
						}
						$shift    = substr($data123->TimeIn, 0, 5) . ' - ' . substr($data123->TimeOut, 0, 5);
						$ct       = date('H:i:s');
					   
							if ($cdate == $date)
								$cond = "    and TimeOut !='00:00:00'";
							$query333 = $this->db->query("select SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out from AttendanceMaster where  EmployeeId =$EId and if(timeoutdate = '0000-00-00' , TimeOut  <  '$shiftout' , CONCAT(timeoutdate,' ' ,TimeOut)  <  '$shiftout1' ) and AttendanceDate='$date'" . $cond);


							if ($row333 = $query333->row()) {
								$a               = new DateTime($row333->TimeOut);
								$b               = new DateTime($data123->TimeOut);
								$interval        = $a->diff($b);
								$data['earlyby'] = $interval->format("%H:%I");
								$data['timeout'] = substr($row333->TimeOut, 0, 5);
								$data['name']  = $row->FirstName . ' ' . $row->LastName;
								$data['shift'] = $shift;
								$data['TimeIn']  =     $row333->TimeIn;
								$data['TimeOut']  =  $row333->TimeOut;
								$data['CheckOutLoc']  =  $row333->CheckOutLoc;
								$data['checkInLoc']  =  $row333->checkInLoc;
								$data['latit_in']  =  $row333->latit_in;
								$data['longi_in']  =  $row333->longi_in;
								$data['latit_out']  =  $row333->latit_out;
								$data['longi_out']  =  $row333->longi_out;
								$data['status']  =  $row333->status;
								$data['date']  = $date;
								$res[]         = $data;
							}
					   
					}
				}
			$data['earlyLeavings'] = $res;  
		}
		echo json_encode($data, JSON_NUMERIC_CHECK);
       
    }
	

	public function getCDateAttnDesgWiseCount_new()
    {
        // getting counting of attending/onbreak/exits and not attending emps
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $att   = isset($_REQUEST['att']) ? $_REQUEST['att'] : 0;
        $date   = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        $desg   = isset($_REQUEST['desg']) ? $_REQUEST['desg'] : '';
        $datafor   = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : '';
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d',strtotime($date));
        $time = date('H:i:s');
        $data = array();
       $cdate = date('Y-m-d');
	   $data['present'] =0;
		$data['absent'] =0;
		$data['latecomings'] =0;
		$data['earlyleavings'] =0;
	   
	   $desg_cond='';
	   $desg_cond1='';
	   
	   
	   if($desg!=0)
	   {
		   $desg_cond=' and Desg_id='.$desg;
		   $desg_cond1 =' and Designation='.$desg;
	   }
	   
         //today attendance
		 
			$query  = $this->db->query("SELECT count(id) as present FROM `AttendanceMaster` WHERE `AttendanceDate`=? ".$desg_cond." and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0 )",
			array($date,$orgid));
			if($row =$query->result()){
					$data['present'] = $row[0]->present;
			}
		
			/* if($date==date('Y-m-d')){
				$query= $this->db->query("Select count(ID) as total from EmployeeMaster where  OrganizationId = ? AND Is_Delete=0 and archive = 1    AND ( ID NOT IN (SELECT EmployeeId from AttendanceMaster where  AttendanceStatus in (1,4,8) AND AttendanceDate = ?)  AND  Shift NOT IN (Select id from ShiftMaster where OrganizationId = ? AND TimeIn > ?) )",array($orgid,$date,$orgid,$time));
					if($row =$query->result())
						$data['absent'] = $row[0]->total ;
			}else{
				$data['absent']=0;
				  $query  = $this->db->query("SELECT count(Id) as total FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and AttendanceStatus in (2,6,7)  AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) ",array($date,$orgid));
			
				if($row=$query->result())
					 $data['absent']=$row[0]->total;	
			} */
			
			if($date!=date('Y-m-d')){// for other deay's absentees
				$sql = "SELECT count(id) as absent FROM `AttendanceMaster` WHERE `AttendanceDate`=$date and  OrganizationId=$orgid and (AttendanceStatus=2 or AttendanceStatus=6 or AttendanceStatus=7 ) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) ".$desg_cond."  ";
				$query  = $this->db->query($sql);
				if($row =$query->result()){
					$data['absent'] = $row[0]->absent;
				}	
			}else{ // for today's absentees
				$query  = $this->db->query("SELECT count(id) FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and AttendanceStatus in (2,6,7) ".$desg_cond."",array($date,$orgid));
			$temp=array();
            $temp =  $query->result();
			
			$query  = $this->db->query("SELECT count(id) as absent
						FROM  `EmployeeMaster` 
						WHERE  `OrganizationId` =?
						AND ARCHIVE =1 ".$desg_cond1."
						AND is_Delete = 0 AND  Id NOT 
						IN (
						SELECT EmployeeId
						FROM AttendanceMaster
						WHERE AttendanceDate =  ?
						AND  `OrganizationId` =?
						)
						AND (
						SELECT  `TimeIn` 
						FROM  `ShiftMaster` 
						WHERE  `Id` = Shift
						AND TimeIn <  ?
						)",array($orgid,$date,$orgid,$time));
						if($row =$query->result())
							$data['absent'] = $row[0]->absent ;
			}
			
            $query         = $this->db->query("SELECT count(id) as latecomings FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? ".$desg_cond." and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) ", array(
                $date,
                $orgid
            ));
            if($row =$query->result()){
					$data['latecomings'] = $row[0]->latecomings;
			}
				
	
            $query         = $this->db->query("SELECT count(id) as earlyleavings FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? ".$desg_cond." and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )", array( $date,   $orgid  ));
             if($row =$query->result()){
					$data['earlyleavings'] = $row[0]->earlyleavings;
			}	
	
        echo json_encode($data); 
    }
	
	public function getChartDataToday(){
		$orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
		$empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
		if($empid!=0)
        	$zone  = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d');
        $time = date('H:i:s');
		$data=array();
		$data['present'] =0;
		$data['absent'] =0;
		$data['late'] =0;
		$data['early'] =0;
		//PRESENT COUNT
		$query= $this->db->query("SELECT count(id) as present  FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )", array($date,  $orgid ));
			if($row =$query->result())
				$data['present'] = $row[0]->present;
		
		//ABSENT COUNT
		/*
		$query= $this->db->query("SELECT count(Id) as total FROM `EmployeeMaster` WHERE `OrganizationId`=? ",
		array($orgid)
		);
		if($row =$query->result())
			$data['absent'] = $row[0]->total - $data['present'];
		*/
		$data['absent']=0;
		$query  = $this->db->query("SELECT count(Id) as total FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and AttendanceStatus in (2,6,7) ",array($date,$orgid));
            if($row=$query->result())
				 $data['absent']=$row[0]->total;
			$query  = $this->db->query("SELECT count(id) as total
						FROM  `EmployeeMaster` 
						WHERE  `OrganizationId` =?
						AND ARCHIVE =1
						AND Is_Delete=0
						AND Id NOT 
						IN (
						SELECT EmployeeId
						FROM AttendanceMaster
						WHERE AttendanceDate =  ?
						AND  `OrganizationId` =?
						)
						AND (
						SELECT  `TimeIn` 
						FROM  `ShiftMaster` 
						WHERE  `Id` = Shift
						AND TimeIn <  ?
						)",array($orgid,$date,$orgid,$time));
		if($row=$query->result())
			$data['absent']+=$row[0]->total;
						
		//////// today_late
		$query = $this->db->query("SELECT count(Id) as late FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )", array(
			$date,
			$orgid
		));
		if($row =$query->result())
			$data['late']=$row[0]->late;
			
			
        ////////today_early
		$query = $this->db->query("SELECT count(Id) as early FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )", array(
			$date,
			$orgid
		));
		if($row =$query->result())
			$data['early']=$row[0]->early;
		
		echo json_encode($data);
			
	}
	public function getChartDataYes(){
		$orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
		$empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
		if($empid!=0)
        	$zone  = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d', strtotime(' -1 day'));
        $time = date('H:i:s');
		$data=array();
		$data['present'] =0;
		$data['absent'] =0;
		$data['late'] =0;
		$data['early'] =0;
		//PRESENT COUNT
		$query= $this->db->query("SELECT count(id) as present  FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) ",array(
		$date,$orgid
            ));
			if($row =$query->result())
				$data['present'] = $row[0]->present;
		
		//ABSENT COUNT
		$query  = $this->db->query("SELECT count(Id) as total FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and AttendanceStatus in (2,6,7)  AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) ",array($date,$orgid));
            if($row=$query->result())
				 $data['absent']=$row[0]->total;
			 
		 
			
		//////// today_late
		$query = $this->db->query("SELECT count(Id) as late FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)  ", array(
			$date,
			$orgid
		));
		if($row =$query->result())
			$data['late']=$row[0]->late;
			
			
        ////////today_early
		$query = $this->db->query("SELECT count(Id) as early FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) ", array(
			$date,
			$orgid
		));
		if($row =$query->result())
			$data['early']=$row[0]->early;
		
		echo json_encode($data);
			
	}
	
	public function getChartDataCDate(){
		$orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
		$date = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
		$empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : '';
		$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $date =date('Y-m-d',strtotime($date));
        $time = date('H:i:s');
		$cdate = date('Y-m-d');
		$data=array();
		$data['present'] =0;
		$data['absent'] =0;
		$data['late'] =0;
		$data['early'] =0;
		/*$adminstatus = getAdminStatus($empid);
		$cond = "";
		if($adminstatus == '2')
		{ 
	     	$dptid = getDepartmentIdByEmpID($empid);
			$cond = " AND Department = $dptid  ";
			$cond1 = " AND Dept_id = $dptid  ";
		}*/
		//PRESENT COUNT
		$query= $this->db->query("SELECT count(id) as present  FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )", array(
				$date,																																																												
                $orgid
            ));
			if($row =$query->result())
				$data['present'] = $row[0]->present;
		
		//ABSENT COUNT
		if($date==date('Y-m-d')){
			$query= $this->db->query("Select count(ID) as total from EmployeeMaster where  OrganizationId = ? AND Is_Delete=0 and archive = 1    AND ( ID NOT IN (SELECT EmployeeId from AttendanceMaster where  AttendanceStatus in (1,4,8,5,3) AND AttendanceDate = ?)  AND  Shift NOT IN (Select id from ShiftMaster where OrganizationId = ? AND TimeIn > ?) )",
			array($orgid,$date,$orgid,$time));
			if($row =$query->result())
				$data['absent'] = $row[0]->total ;
		}else{
			$data['absent']=0;
		//$query  = $this->db->query("SELECT count(Id) as total FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and AttendanceStatus in (2,6,7) ",array($date,$orgid));
		
			$query  = $this->db->query("SELECT count(Id) as total FROM `AttendanceMaster` WHERE `AttendanceDate`=? and  OrganizationId=? and AttendanceStatus in (2,6,7)  AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) ",array($date,$orgid));
		
            if($row=$query->result())
				 $data['absent']=$row[0]->total;
			/*$query  = $this->db->query("SELECT count(id) as total FROM  `EmployeeMaster` 
						WHERE  `OrganizationId` =? AND ARCHIVE =1 AND Id NOT IN ( SELECT EmployeeId FROM AttendanceMaster WHERE AttendanceDate =  ? AND  `OrganizationId` =? ) AND ( SELECT  `TimeIn`  FROM  `ShiftMaster`  WHERE  `Id` = Shift AND TimeIn <  ? )",array($orgid,$date,$orgid,$time));
		if($row=$query->result())
			$data['absent']+=$row[0]->total;*/
		}
		//////// today_late
		$query = $this->db->query("SELECT count(Id) as late FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )", array(
			$date,
			$orgid
		));
		if($row =$query->result())
			$data['late']=$row[0]->late;
			
			
        ////////today_early
		$query = $this->db->query("SELECT count(Id) as early FROM `AttendanceMaster` WHERE (time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and TimeOut!='00:00:00' ) and `AttendanceDate`=? and  OrganizationId=? and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 )", array(
			$date,
			$orgid
		));
		if($row =$query->result())
			$data['early']=$row[0]->early;
		
			 $query = $this->db->query("select Shift,Id , FirstName , LastName  from EmployeeMaster where OrganizationId = $orgid and Id IN (select EmployeeId from AttendanceMaster where OrganizationId = $orgid and AttendanceDate='$date' and TimeIn != '00:00:00' AND TimeOut!='00:00:00'  ) AND is_Delete=0 order by FirstName") ;
		 $res   = array();
		 $cond  = '';
        foreach ($query->result() as $row) {
            $ShiftId = $row->Shift;
            $EId     = $row->Id;
            $query   = $this->db->query("select TimeIn,TimeOut,shifttype from ShiftMaster where Id = $ShiftId");
            if ($data123 = $query->row()) {
                $shiftout = $data123->TimeOut;
                $shiftout1 = $date. ' '.$data123->TimeOut;
				if($data123->shifttype==2)
				{
					$nextdate = date('Y-m-d',strtotime($date . "+1 days"));
					 $shiftout1 = $nextdate.' '.$data123->TimeOut;
				}
                $shift    = substr($data123->TimeIn, 0, 5) . ' - ' . substr($data123->TimeOut, 0, 5);
                $ct       = date('H:i:s');
               
                    if ($cdate == $date)
                        $cond = "    and TimeOut !='00:00:00'";
                    $query333 = $this->db->query("select SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out from AttendanceMaster where  EmployeeId =$EId and if(timeoutdate = '0000-00-00' , TimeOut  <  '$shiftout' , CONCAT(timeoutdate,' ' ,TimeOut)  <  '$shiftout1' ) and AttendanceDate='$date'" . $cond);
					
					
                    if ($row333 = $query333->row()) {
                        $a               = new DateTime($row333->TimeOut);
                        $b               = new DateTime($data123->TimeOut);
                        $interval        = $a->diff($b);
                        $data['earlyby'] = $interval->format("%H:%I");
                        $data['timeout'] = substr($row333->TimeOut, 0, 5);
                        $data['name']  = $row->FirstName . ' ' . $row->LastName;
                        $data['shift'] = $shift;
                        $data['TimeIn']  =     $row333->TimeIn;
                        $data['TimeOut']  =  $row333->TimeOut;
                        $data['CheckOutLoc']  =  $row333->CheckOutLoc;
                        $data['checkInLoc']  =  $row333->checkInLoc;
                        $data['latit_in']  =  $row333->latit_in;
                        $data['longi_in']  =  $row333->longi_in;
                        $data['latit_out']  =  $row333->latit_out;
                        $data['longi_out']  =  $row333->longi_out;
                        $data['date']  = $date;
                        $res[]         = $data;
                    }
                
            }
        }
		  $data['earlyLeavings'] =	 $res;
		
		echo json_encode($data);
			
    }
    function getChartDataLast_7_30()
    {
        
        /*
		$lim=isset($_REQUEST['limit'])?$_REQUEST['limit']:'0';
		$orgid=isset($_REQUEST['refno'])?$_REQUEST['refno']:'0';
		//$datafor=isset($_REQUEST['datafor'])?$_REQUEST['datafor']:'absent';
		$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
		$end   = date("Y-m-d");
        $start = date("Y-m-d");
		$data=array();
		if($lim=='l7'){ // Last 7 days
			$end  = date("Y-m-d", strtotime("-1 days"));
            $start = date("Y-m-d", strtotime('-6 day', strtotime($end)));
			$start = \DateTime::createFromFormat('Y-m-d', $start);
            $end   = \DateTime::createFromFormat('Y-m-d', $end);
		}else if($lim=='l30'){ // Last 30 days
			$end  = date("Y-m-d", strtotime("-1 days"));
            $start = date("Y-m-d", strtotime('-29 day', strtotime($end)));
			$start = \DateTime::createFromFormat('Y-m-d', $start);
            $end   = \DateTime::createFromFormat('Y-m-d', $end);
		}
		$datePeriod = new \DatePeriod($start, new \DateInterval('P1D'), $end->modify('+1day'));
		///////getting data
		
			foreach ($datePeriod as $date) {
				 $dt              = $date->format('Y-m-d');
				$query           = $this->db->query("
				SELECT count(Id) as total,'$dt' as AttendanceDate
                            FROM `EmployeeMaster` 
                            WHERE EmployeeMaster.OrganizationId =$orgid
                             AND archive=1 and IF(EmployeeMaster.CreatedDate!='0000-00-00 00:00:00', CreatedDate < '$dt', 1) and  Id 
                            NOT IN (
                            SELECT `EmployeeId` 
                            FROM `AttendanceMaster` 
                            WHERE `AttendanceDate` 
                            IN (
                            '$dt'
                            )
                            AND AttendanceMaster.OrganizationId =$orgid
                            AND `AttendanceStatus` not in(3,5,6)
                            )
                            ORDER BY EmployeeMaster.Id");
                if($row=$query->result()){
					$data1['date']=date('dM',strtotime($row[0]->AttendanceDate));
					$data1['total']=$row[0]->total;
					$data[]=$data1;
				}
			}
		///////getting data/
		echo json_encode($data);
		*/
		
		$lim=isset($_REQUEST['limit'])?$_REQUEST['limit']:'0';
		$orgid=isset($_REQUEST['refno'])?$_REQUEST['refno']:'0';
		$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $end   = date("Y-m-d");
        $start = date("Y-m-d");
		
			//$end  = date("Y-m-d", strtotime("-1 days"));
            //$start = date("Y-m-d", strtotime('-6 day', strtotime($end)));
		//$datafor=isset($_REQUEST['datafor'])?$_REQUEST['datafor']:'absent';
		
			$res=array();
			$data=array();
			$month                = date('m');
            $year                 = date('Y');
            $arr=array();
            
            if($lim=='Last 7 days '){ // Last 7 days

                $end  = date("Y-m-d", strtotime("-1 days"));
                $end1  = date("Y-m-d", strtotime("-1 days"));
                $start = date("Y-m-d", strtotime('-6 day', strtotime($end)));
                $start1 = date("Y-m-d", strtotime('-6 day', strtotime($end)));
                //$start = \DateTime::createFromFormat('Y-m-d', $start);
                //$end   = \DateTime::createFromFormat('Y-m-d', $end);
                
            }else if($lim=='Last 30 days '){ // Last 30 days
            //}else if($lim=='130'){ // Last 30 days
    
                
                $end  = date("Y-m-d", strtotime("-1 days"));
                $start = date("Y-m-d", strtotime('-29 day', strtotime($end)));
                $end1  = date("Y-m-d", strtotime("-1 days"));
                $start1 = date("Y-m-d", strtotime('-29 day', strtotime($end)));
                //$start = \DateTime::createFromFormat('Y-m-d', $start);
                //$end   = \DateTime::createFromFormat('Y-m-d', $end);
    
            }
            else if($lim=='Last 14 days '){ // Last 30 days
                //}else if($lim=='130'){ // Last 30 days
                    
                    $end  = date("Y-m-d", strtotime("-1 days"));
                    $start = date("Y-m-d", strtotime('-13 day', strtotime($end)));
                    $end1  = date("Y-m-d", strtotime("-1 days"));
                    $start1 = date("Y-m-d", strtotime('-13 day', strtotime($end)));
                    //$start = \DateTime::createFromFormat('Y-m-d', $start);
                    //$end   = \DateTime::createFromFormat('Y-m-d', $end);
    
                    // var_dump($end);
                    // var_dump($start);
                    // die;
        
                }
                else if($lim=='This month '){ // Last 30 days
                    //}else if($lim=='130'){ // Last 30 days
                        
                        $end  = date("Y-m-d", strtotime("-1 days"));
                        $start = date("Y-m-d", strtotime('first day of this month', strtotime($end)));
                        $end1  = date("Y-m-d", strtotime("-1 days"));
                        $start1 = date("Y-m-d", strtotime('first day of this month', strtotime($end)));
                        //$start = \DateTime::createFromFormat('Y-m-d', $start);
                        //$end   = \DateTime::createFromFormat('Y-m-d', $end);
        
                        // var_dump($end);
                        // var_dump($start);
                        // die;
            
                    }
                    else if($lim=='Last month'){ // Last 30 days
                        //}else if($lim=='130'){ // Last 30 days
                            
                            $end  = date("Y-m-d", strtotime("last day of last month"));
                            $start = date("Y-m-d", strtotime('first day of this month', strtotime($end)));
                            $end1  = date("Y-m-d", strtotime("last day of last month"));
                            $start1 = date("Y-m-d", strtotime('first day of this month', strtotime($end)));
                            //$start = \DateTime::createFromFormat('Y-m-d', $start);
                            //$end   = \DateTime::createFromFormat('Y-m-d', $end);
                
                        }

            $query                = $this->db->query("SELECT count(`EmployeeId`) as A FROM AttendanceMaster  WHERE AttendanceStatus =2 and  (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "')   AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)   and  `OrganizationId`=" . $orgid);
			if($row=$query->result()){
				$arr['event']        = 'A';				
				$arr['total']        = $row[0]->A;				
			}
			$data[]=$arr;
            $query                = $this->db->query("SELECT count(`EmployeeId`) as P FROM AttendanceMaster  WHERE AttendanceStatus in (1,4,7,8) and (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "')    AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)    and  `OrganizationId`=" . $orgid);
            if($row=$query->result()){
				$arr['event']        = 'P';
				$arr['total']        = $row[0]->P;
			}$data[]=$arr;
            $query                = $this->db->query("select count(Id) as LC from AttendanceMaster where (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "') and `OrganizationId`=" . $orgid . " and time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId) and AttendanceStatus in (1,4,7,8,3,5 )  AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)   ");
			if($row=$query->result()){
				$arr['event']        = 'LC';
				$arr['total']        = $row[0]->LC;
			}$data[]=$arr;
            $query                = $this->db->query("select count(Id) as EL from AttendanceMaster where (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "') and `OrganizationId`=" . $orgid . " and TimeOut !='00:00:00' and time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and AttendanceStatus in (1,4,7,8,3,5) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) ");
            if($row=$query->result()){
				$arr['event']        = 'EL';
				$arr['total']        = $row[0]->EL;
			}$data[]=$arr;
        echo json_encode($data);
        
    }
	function getChartDataLast_7(){/*
		$lim=isset($_REQUEST['limit'])?$_REQUEST['limit']:'0';
		$orgid=isset($_REQUEST['refno'])?$_REQUEST['refno']:'0';
		//$datafor=isset($_REQUEST['datafor'])?$_REQUEST['datafor']:'absent';
		$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
		$end   = date("Y-m-d");
        $start = date("Y-m-d");
		$data=array();
		if($lim=='l7'){ // Last 7 days
			$end  = date("Y-m-d", strtotime("-1 days"));
            $start = date("Y-m-d", strtotime('-6 day', strtotime($end)));
			$start = \DateTime::createFromFormat('Y-m-d', $start);
            $end   = \DateTime::createFromFormat('Y-m-d', $end);
		}else if($lim=='l30'){ // Last 30 days
			$end  = date("Y-m-d", strtotime("-1 days"));
            $start = date("Y-m-d", strtotime('-29 day', strtotime($end)));
			$start = \DateTime::createFromFormat('Y-m-d', $start);
            $end   = \DateTime::createFromFormat('Y-m-d', $end);
		}
		$datePeriod = new \DatePeriod($start, new \DateInterval('P1D'), $end->modify('+1day'));
		///////getting data
		
			foreach ($datePeriod as $date) {
				 $dt              = $date->format('Y-m-d');
				$query           = $this->db->query("
				SELECT count(Id) as total,'$dt' as AttendanceDate
                            FROM `EmployeeMaster` 
                            WHERE EmployeeMaster.OrganizationId =$orgid
                             AND archive=1 and IF(EmployeeMaster.CreatedDate!='0000-00-00 00:00:00', CreatedDate < '$dt', 1) and  Id 
                            NOT IN (
                            SELECT `EmployeeId` 
                            FROM `AttendanceMaster` 
                            WHERE `AttendanceDate` 
                            IN (
                            '$dt'
                            )
                            AND AttendanceMaster.OrganizationId =$orgid
                            AND `AttendanceStatus` not in(3,5,6)
                            )
                            ORDER BY EmployeeMaster.Id");
                if($row=$query->result()){
					$data1['date']=date('dM',strtotime($row[0]->AttendanceDate));
					$data1['total']=$row[0]->total;
					$data[]=$data1;
				}
			}
		///////getting data/
		echo json_encode($data);
		*/
		
		$lim=isset($_REQUEST['limit'])?$_REQUEST['limit']:'0';
		$orgid=isset($_REQUEST['refno'])?$_REQUEST['refno']:'0';
		$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
		
			$end  = date("Y-m-d", strtotime("-1 days"));
            $start = date("Y-m-d", strtotime('-6 day', strtotime($end)));
		//$datafor=isset($_REQUEST['datafor'])?$_REQUEST['datafor']:'absent';
		
			$res=array();
			$data=array();
			$month                = date('m');
            $year                 = date('Y');
			$arr=array();
            $query                = $this->db->query("SELECT count(`EmployeeId`) as A FROM AttendanceMaster  WHERE AttendanceStatus =2 and  (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "')   AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)   and  `OrganizationId`=" . $orgid);
			if($row=$query->result()){
				$arr['event']        = 'A';				
				$arr['total']        = $row[0]->A;				
			}
			$data[]=$arr;
            $query                = $this->db->query("SELECT count(`EmployeeId`) as P FROM AttendanceMaster  WHERE AttendanceStatus in (1,4,7,8) and (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "')    AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)    and  `OrganizationId`=" . $orgid);
            if($row=$query->result()){
				$arr['event']        = 'P';
				$arr['total']        = $row[0]->P;
			}$data[]=$arr;
            $query                = $this->db->query("select count(Id) as LC from AttendanceMaster where (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "') and `OrganizationId`=" . $orgid . " and time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId) and AttendanceStatus in (1,4,7,8,3,5 )  AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)   ");
			if($row=$query->result()){
				$arr['event']        = 'LC';
				$arr['total']        = $row[0]->LC;
			}$data[]=$arr;
            $query                = $this->db->query("select count(Id) as EL from AttendanceMaster where (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "') and `OrganizationId`=" . $orgid . " and TimeOut !='00:00:00' and time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId) and AttendanceStatus in (1,4,7,8,3,5) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) ");
            if($row=$query->result()){
				$arr['event']        = 'EL';
				$arr['total']        = $row[0]->EL;
			}$data[]=$arr;
		echo json_encode($data);
	}
	function getChartDataLast_30(){
		$lim=isset($_REQUEST['limit'])?$_REQUEST['limit']:'0';
		$orgid=isset($_REQUEST['refno'])?$_REQUEST['refno']:'0';
		$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
		
			$end  = date("Y-m-d", strtotime("-1 days"));
            $start = date("Y-m-d", strtotime('-29 day', strtotime($end)));
		//$datafor=isset($_REQUEST['datafor'])?$_REQUEST['datafor']:'absent';
		
			$res=array();
			$data=array();
			$month                = date('m');
            $year                 = date('Y');
			$arr=array();
            $query                = $this->db->query("SELECT count(`EmployeeId`) as A FROM AttendanceMaster  WHERE AttendanceStatus =2 and  (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "')  AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) and   `OrganizationId`=" . $orgid);
			if($row=$query->result()){
				$arr['event']        = 'A';				
				$arr['total']        = $row[0]->A;				
			}
			$data[]=$arr;
            $query                = $this->db->query("SELECT count(`EmployeeId`) as P FROM AttendanceMaster  WHERE AttendanceStatus in (1,4,7,8,3,5) and (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "')   AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)   and  `OrganizationId`=" . $orgid);
            if($row=$query->result()){
				$arr['event']        = 'P';
				$arr['total']        = $row[0]->P;
			}$data[]=$arr;
            $query                = $this->db->query("select count(Id) as LC from AttendanceMaster where (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "') and `OrganizationId`=" . $orgid . " and time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)     and AttendanceStatus in (1,4,7,8,3,5)");
			if($row=$query->result()){
				$arr['event']        = 'LC';
				$arr['total']        = $row[0]->LC;
			}$data[]=$arr;
            $query                = $this->db->query("select count(Id) as EL from AttendanceMaster where (AttendanceDate BETWEEN  '" . $start . "' and '" . $end . "') and `OrganizationId`=" . $orgid . " and TimeOut !='00:00:00' and time(TimeOut) < (select time(TimeOut) from ShiftMaster where ShiftMaster.Id=shiftId)  AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0)  and AttendanceStatus in (1,4,7,8,3,5)");
            if($row=$query->result()){
				$arr['event']        = 'EL';
				$arr['total']        = $row[0]->EL;
			}$data[]=$arr;
		echo json_encode($data);
		
	}
    function getAttnDataLast(){  //Last 7 or last 30
		
		$lim=  isset($_REQUEST['limit'])?$_REQUEST['limit']:'0';
		$orgid = isset($_REQUEST['refno'])?$_REQUEST['refno']:'0';
		$datafor= isset($_REQUEST['datafor'])?$_REQUEST['datafor']:'';
		$datafor= isset($_REQUEST['datafor'])?$_REQUEST['datafor']:'';
		$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
		$end   = date("Y-m-d");
        $start = date("Y-m-d");
        $data=array();

        if($lim=='l7'){ // Last 7 days
			$end  = date("Y-m-d", strtotime("-1 days"));
			$end1  = date("Y-m-d", strtotime("-1 days"));
            $start = date("Y-m-d", strtotime('-6 day', strtotime($end)));
            $start1 = date("Y-m-d", strtotime('-6 day', strtotime($end)));
			$start = \DateTime::createFromFormat('Y-m-d', $start);
            $end   = \DateTime::createFromFormat('Y-m-d', $end);
		}else if($lim=='l30'){ // Last 30 days
			$end  = date("Y-m-d", strtotime("-1 days"));
            $start = date("Y-m-d", strtotime('-29 day', strtotime($end)));
			$end1  = date("Y-m-d", strtotime("-1 days"));
            $start1 = date("Y-m-d", strtotime('-29 day', strtotime($end)));
			$start = \DateTime::createFromFormat('Y-m-d', $start);
            $end   = \DateTime::createFromFormat('Y-m-d', $end);
		}else
        
        if($lim=='Last 7 days '){ // Last 7 days

            $end  = date("Y-m-d", strtotime("-1 days"));
            $end1  = date("Y-m-d", strtotime("-1 days"));
        
            $start = date("Y-m-d", strtotime('-6 day', strtotime($end)));
            $start1 = date("Y-m-d", strtotime('-6 day', strtotime($end)));
			$start = \DateTime::createFromFormat('Y-m-d', $start);
            $end   = \DateTime::createFromFormat('Y-m-d', $end);
            
        }else if($lim=='Last 30 days '){ // Last 30 days
        //}else if($lim=='130'){ // Last 30 days

            
			$end  = date("Y-m-d", strtotime("-1 days"));
            $start = date("Y-m-d", strtotime('-29 day', strtotime($end)));
			$end1  = date("Y-m-d", strtotime("-1 days"));
            $start1 = date("Y-m-d", strtotime('-29 day', strtotime($end)));
			$start = \DateTime::createFromFormat('Y-m-d', $start);
            $end   = \DateTime::createFromFormat('Y-m-d', $end);

        }
        else if($lim=='Last 14 days '){ // Last 14 days
                
                $end  = date("Y-m-d", strtotime("-1 days"));
                $start = date("Y-m-d", strtotime('-13 day', strtotime($end)));
                $end1  = date("Y-m-d", strtotime("-1 days"));
                $start1 = date("Y-m-d", strtotime('-13 day', strtotime($end)));
                $start = \DateTime::createFromFormat('Y-m-d', $start);
                $end   = \DateTime::createFromFormat('Y-m-d', $end);
    }

            else if($lim=='This month '){ // this month
                    
                    $end  = date("Y-m-d", strtotime("-1 days"));
                    $start = date("Y-m-d", strtotime('first day of this month', strtotime($end)));
                    $end1  = date("Y-m-d", strtotime("-1 days"));
                    $start1 = date("Y-m-d", strtotime('first day of this month', strtotime($end)));
                    $start = \DateTime::createFromFormat('Y-m-d', $start);
                    $end   = \DateTime::createFromFormat('Y-m-d', $end);
        
                }
                
                else if($lim=='Last month'){ // Last month
                        
                        $end  = date("Y-m-d", strtotime("last day of last month"));
                        $start = date("Y-m-d", strtotime('first day of this month', strtotime($end)));
                        $end1  = date("Y-m-d", strtotime("last day of last month"));
                        $start1 = date("Y-m-d", strtotime('first day of this month', strtotime($end)));
                        $start = \DateTime::createFromFormat('Y-m-d', $start);
                        $end   = \DateTime::createFromFormat('Y-m-d', $end);
            
                    }
        
        
		   $datePeriod = new \DatePeriod($start, new \DateInterval('P1D'), $end->modify('+1day'));
		///////getting data
			
			if($datafor=='present'){
				$res        = array();
				foreach ($datePeriod as $date) {
					$dt    = $date->format('Y-m-d');
					$query = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,AttendanceDate FROM `AttendanceMaster` WHERE `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid . " and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) order by DATE(AttendanceDate) desc,name");
					//$res[] = $query->result();
					$data['elist'][] =  $query->result();
					/* foreach ($query->result() as $row1){
						$data1                   = array();
						$data1['name']           = $row1->name;
						$data1['AttendanceDate'] = date("d M", strtotime($row1->AttendanceDate));
						$data1['TimeIn']         = $row1->TimeIn;
						$data1['TimeOut']        = $row1->TimeOut;
						$res[]                  = $data1;
					} */
				}
				//$data['elist'][] = array_reverse($res);		
			}else if($datafor=='absent'){
				$res = array();

				$query = $this->db->query("SELECT EmployeeId , AttendanceDate FROM `AttendanceMaster` WHERE OrganizationId = ? and AttendanceStatus in (2,6,7) and `AttendanceDate` between ? and ? AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) order by AttendanceDate ", array($orgid , $start1 , $end1));
				foreach ($query->result() as $row) {
							$data1                   = array();
							//$data['name']=ucwords(getEmpName($row->Id));
							$data1['name']           = getEmpName($row->EmployeeId);
							$data1['AttendanceDate'] = date("Y-m-d", strtotime($row->AttendanceDate));
							$data1['TimeIn']         = '-';
							$data1['TimeOut']        = '-';
							$res[]                  = $data1;
				}
				$data['elist'][] =array_reverse($res);
			}else if($datafor=='latecomings'){
				$res        = array();
				foreach ($datePeriod as $date) {
					$dt    = $date->format('Y-m-d');
					$query = $this->db->query("SELECT (select CONCAT(FirstName,' ',LastName)  from EmployeeMaster where id= `EmployeeId`) as name , `TimeIn`, `TimeOut` ,AttendanceDate FROM `AttendanceMaster` WHERE (time(TimeIn) > (select time(TimeIn) from ShiftMaster where ShiftMaster.Id=shiftId)) and `AttendanceDate`  ='" . $dt . "' and `OrganizationId`=" . $orgid . " and (AttendanceStatus=1 or AttendanceStatus=3 or AttendanceStatus=4 or AttendanceStatus=5 or AttendanceStatus=8 ) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) order by DATE(AttendanceDate) desc,name");
					$res[] = $query->result();
					/* foreach ($query->result() as $row1){
						$data1                   = array();
						$data1['name']           = $row1->name;
						$data1['AttendanceDate'] = date("d M", strtotime($row1->AttendanceDate));
						$data1['TimeIn']         = $row1->TimeIn;
						$data1['TimeOut']        = $row1->TimeOut;
						$res[]                  = $data1;
					} */
				}
				$data['elist'] = $res;
				//$data['elist'][] = array_reverse($res);
			}else if($datafor=='earlyleavings'){
				$res        = array();
				foreach ($datePeriod as $date) {
					$dt    = $date->format('Y-m-d');
						$query = $this->db->query("select Shift,Id , FirstName , LastName  from EmployeeMaster where OrganizationId = $orgid and Id IN (select EmployeeId from AttendanceMaster where OrganizationId = $orgid and AttendanceDate='$dt ' and TimeIn != '00:00:00'   ) AND is_Delete=0 order by FirstName");
					 $innerarr   = array();
					 $cond  = '';
					foreach ($query->result() as $row) {
						$ShiftId = $row->Shift;
						$EId     = $row->Id;
						$query   = $this->db->query("select TimeIn,TimeOut,shifttype from ShiftMaster where Id = $ShiftId");
						if ($data123 = $query->row()) {
							$shiftout = $data123->TimeOut;
							$shiftout1 = $dt. ' '.$data123->TimeOut;
							if($data123->shifttype==2)
							{
								$nextdate = date('Y-m-d',strtotime($dt . "+1 days"));
								 $shiftout1 = $nextdate.' '.$data123->TimeOut;
							}
							$shift    = substr($data123->TimeIn, 0, 5) . ' - ' . substr($data123->TimeOut, 0, 5);
							$ct       = date('H:i:s');
								$query333 = $this->db->query("select SUBSTR(TimeIn, 1, 5) as `TimeIn`, SUBSTR(TimeOut, 1, 5) as `TimeOut` ,'Present' as status,EntryImage,ExitImage,SUBSTR(checkInLoc, 1, 40) as checkInLoc, SUBSTR(CheckOutLoc, 1, 40) as CheckOutLoc,latit_in,longi_in,latit_out,longi_out from AttendanceMaster where  EmployeeId =$EId and if(timeoutdate = '0000-00-00' , TimeOut  <  '$shiftout' , CONCAT(timeoutdate,' ' ,TimeOut)  <  '$shiftout1' ) and AttendanceDate='$dt' and TimeOut !='00:00:00' ");
								
								
								if ($row333 = $query333->row()) {
									$a               = new DateTime($row333->TimeOut);
									$b               = new DateTime($data123->TimeOut);
									$interval        = $a->diff($b);
									$data1['earlyby'] = $interval->format("%H:%I");
									$data1['timeout'] = substr($row333->TimeOut, 0, 5);
									$data1['name']  = $row->FirstName . ' ' . $row->LastName;
									$data1['shift'] = $shift;
									$data1['status'] = $row333->status;
									$data1['TimeIn']  =     $row333->TimeIn;
									$data1['TimeOut']  =  $row333->TimeOut;
									//$data1['AttendanceDate']  = $dt;
									$data1['AttendanceDate']  = $dt;
									$innerarr[]         = $data1;
								}
							
						}
					}

					$res[] = $innerarr;
				}
				$data['elist'] = $res;
			}
			
		///////getting data/
		echo json_encode($data);
		////////////

	}
	
	public function updatePermission()
    {
		
		$result = array();
		$count=0; $count1=0; $errorMsg=""; $successMsg=""; $status=false;
		$data = array();
        $mdate = date("Y-m-d H:i:s");
		
		$mid   = isset($_REQUEST['userid']) ? $_REQUEST['userid'] : '0';	//USER ID CONTAINS IN ARRAY FIRST VALUE;
		$orgid = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : '0';	//ORG ID CONTAINS IN ARRAY SECOND VALUE;
		$res1 = json_decode(isset($_REQUEST['jsondata']) ? $_REQUEST['jsondata'] : '0', true);
		foreach($res1 as $for1) {
			$whereCondition= $array= array('RoleId'=>$for1['id'],'OrganizationId'=>$orgid);
			$this->db->where($whereCondition);
			$this->db->delete('UserPermission');
			Trace("Deleted with the roleid = ".$for1['id']);
			foreach($for1['permissions'] as $res) {
				Trace("Inserted");
				$data1 = array(
					'RoleId'=>$res['rolename'],
					'ModuleId'=>$res['modulename'],
					'ViewPermission'=>$res['vsts'],
					'EditPermission'=>$res['ests'],
					'DeletePermission'=>$res['dsts'],
					'AddPermission'=>$res['asts'],
					'OrganizationId'=>$orgid,
					//'LastModifiedDate'=>$orgid,
					'LastModifiedDate'=>$mdate,
					//'LastModifiedById'=>$mdate,
					'LastModifiedById'=>$mid,
					'CreatedDate'=>$mdate,
					'CreatedById'=>$mid,
					'OwnerId'=>$mid
				);
				$query=$this->db->insert('UserPermission',$data1);
				if($query){
				$count++;	
				}
			}
		}
		if ($count>=1) {
			//$empid=Utils::getName($did,'EmployeeIncentive','EmployeeId',$this->db);
			//$empname=Utils::getName($empid,'EmployeeMaster','FirstName',$this->db);
			$status =true;
			$successMsg = "User Permission is updated successfully";
		} else {
			$status =false;
			$errorMsg = "Problem while  updating User Permission.";
		}
		
		$result["data"] =$data;
		$result['status']=$status;
		$result['successMsg']=$successMsg;
		$result['errorMsg']=$errorMsg;
		return $result;
    }
	
	 
    
	 public function reqForTimeOff__new(){
      
        $uid    = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $location    = isset($_REQUEST['location']) ? $_REQUEST['location'] : 0;
        $orgid    = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $latit    = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : 0;
        $longi    = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : 0;
       
		$timeoffstatus = isset($_REQUEST['timeoffstatus']) ? $_REQUEST['timeoffstatus'] : 0;
        $timeoffid = isset($_REQUEST['timeoffid']) ? $_REQUEST['timeoffid'] : 0;
        $reason = isset($_REQUEST['reason']) ? $_REQUEST['reason'] : '';
        $device ='1';
        
		$zone    = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $pastdate   = date('Y/m/d',strtotime("-1 days"));
        $today   = date("Y-m-d");
        $time   = date("H:i")=="00:00"?"00:01":date("H:i");  
        $data           = array();
        $data['status'] = 'false';
        //    echo "INSERT INTO `Timeoff`(`EmployeeId`, `TimeofDate`, `TimeFrom`, `TimeTo`, `Reason`,`ApprovalSts`,`CreatedDate`,`OrganizationId`) VALUES ($uid,'$date','$stime','$etime','$reason',3,'$date',$orgid)";
        
		$query = $this->db->query("SELECT id from AttendanceMaster where EmployeeId = $uid and AttendanceStatus in (1,4,8) and AttendanceDate = '$date' and TimeIn != '00:00:00' AND TimeOut != '00:00:00' ");
		if ($this->db->affected_rows())
		{
			 $data['status'] = 'false2';
			 echo json_encode($data);
			die();
		}
		
		$query = $this->db->query("SELECT id from AttendanceMaster where EmployeeId = $uid and AttendanceStatus in (1,4,8) and AttendanceDate in ('$date' , '$pastdate') and TimeIn!='00:00:00' AND TimeOut = '00:00:00' ");
		if (!$this->db->affected_rows())
		{
			 $data['status'] = 'false1';
			 echo json_encode($data);
			die();
		}
		
		
		if($timeoffstatus ==  2 && $timeoffid != 0 )
		{
		  $query = $this->db->query("update Timeoff set TimeTo = ?,TimeoffEndDate = ?,ModifiedDate=? , LatOut=?,LongOut=?,LocationOut=? where id = ? " , array($time ,$today,$stamp,$latit,$longi,$location,$timeoffid));
		}	
		else{
        $query = $this->db->query("INSERT INTO `Timeoff`(`EmployeeId`, `TimeofDate`, `TimeFrom`,TimeTo, `Reason`,`ApprovalSts`,`CreatedDate`,`OrganizationId`,LocationIn,LatIn,longIn,Device) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
		array(
            $uid,
            $date,
            $time,
			'00:00:00',
            $reason,
            2,
            $stamp,
            $orgid,
			$location,
			$latit,
			$longi,
			$device
        ));
		}
        if ($this->db->affected_rows())
            $data['status'] = 'true';
        
        echo json_encode($data);
        
    }
	
	public function Createtimeoff(){
		$result = array();
		$count=0;$count1=0;$count2=0; $errorMsg=""; $successMsg=""; $status=false;
		$data = array();
		$orgid= isset($_REQUEST['orgid'])?$_REQUEST['orgid']:'0';
		$userid = isset($_REQUEST['uid'])?$_REQUEST['uid']:'0';
		if($userid!=0)
        	$zone  = getEmpTimeZone($userid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $month = date("Y-m-d H:i:s");
		$mdate = date("Y-m-d H:i:s");
		$mdate1= date('M d, Y h:i A ', strtotime($mdate));
		// $mdate2 = date('d/m/Y h:i A ', strtotime($mdate));

		$timeoffdate = isset($_REQUEST['date'])?date('Y-m-d',strtotime($_REQUEST['date'])):'';

		$timeoffdateformatted = isset($_REQUEST['date'])?date('Y-m-d',strtotime($_REQUEST['date'])):'';
		$fromtime=isset($_REQUEST['stime'])?date('H:i', strtotime($_REQUEST['stime'])):'';
		$fromtime1  = date('h:i a', strtotime($fromtime));
		$totime=isset($_REQUEST['etime'])?date('H:i', strtotime($_REQUEST['etime'])):'';
		$totime1 = date('h:i a', strtotime($totime));
		$timeoffreason=isset($_REQUEST['reason'])?$_REQUEST['reason']:'0';
		
		//$shiftId=isset($_REQUEST['shiftId'])?$_REQUEST['shiftId']:'0';
		
		try{
			$arr = array();
			$arr[0] = $userid;
			$arr[1] = $timeoffdate;
			$arr[2] = $fromtime;
			$arr[3] = $totime;
			$arr[4] = $timeoffreason;
			$arr[5] = $month;
			$arr[6] = $month;
			$arr[7] = $orgid;
			
			$approvelink="";
		    $rejectlink="";
			$shiftId = getShiftIdByEmpID($userid);
			$shifttype= getShiftType($shiftId);
			$checkShiftQuery=0;
			if($shifttype==1)
				$checkShiftQuery = $this->db->query("SELECT s.Id FROM ShiftMaster s,EmployeeMaster e WHERE ADDTIME(?, '59') between s.TimeIn and TimeOut and ? between s.TimeIn and TimeOut and e.Id = ? and e.Shift = s.Id",
			array($fromtime,$totime,$userid));
			else
				$checkShiftQuery = $this->db->query("SELECT s.Id FROM ShiftMaster s,EmployeeMaster e WHERE e.Id = ? and e.Shift = s.Id",
			array($userid));
			
			$count2 =  $checkShiftQuery->num_rows();
			//Trace("------------> check if Timeoff inside Shift -----> ".$checkShiftQuery);
			if($count2>0){
			$checkTimeOffQuery = $this->db->query("SELECT * FROM Timeoff WHERE ADDTIME(?, '59') between TimeFrom and TimeTo and TimeofDate = ? and EmployeeId = ? AND ApprovalSts not in(5,4,1)",
			array($fromtime, $timeoffdate, $userid));			
			
			$count1 =  $checkTimeOffQuery->num_rows();
			//Trace("------------> Time off check if exist -----> ".$checkTimeOffQuery);
			if($count1==0){				
			$sql = "INSERT INTO Timeoff (EmployeeId, TimeofDate,TimeFrom,TimeTo,Reason,CreatedDate,ModifiedDate,OrganizationId) VALUES (?,?,?,?,?,?,?,?)";	
				$query1 = $this->db->query($sql,$arr);
				$leaveid = $this->db->insert_id();
				$count =  $this->db->affected_rows();			
				
				if ($count == 1) {
				/*generate mail and alert for task requested*/
				/* Alerts::generateActionAlerts(36,$leaveid,$orgid,$this->db); */
						
				//$empid=Utils::getName($did, 'Timeoff','EmployeeId',$this->db);
				$empname=getName('EmployeeMaster','FirstName','Id',$userid);
				//$msg="$empname applied for TimeOff  ";
				//$sql = "INSERT INTO ActivityHistoryMaster ( LastModifiedById, Module, ActionPerformed,  OrganizationId) VALUES (?, ?, ?, ?)";
				//$query = $this->db->query($sql,array($userid, "Profiles", $msg, $orgid));
				
				
					$status =true;
					$hr=0;
					$successMsg = "Your application for Time off has been sent successfully";;
					
				/*	$sql = "select EmployeeMaster.Id from DesignationMaster,EmployeeMaster where DesignationMaster.Id=EmployeeMaster.Designation and DesignationMaster.HrSts=1 and EmployeeMaster.OrganizationId=? and DesignationMaster.OrganizationId=? and  EmployeeMaster.DOL='0000-00-00'";  */  //Comment made by Pratibha
				
				$sql = "SELECT EmployeeId FROM UserMaster WHERE OrganizationId = ? and HRSts=1 ";
					$query = $this->db->query($sql,array( $orgid));					
					/* if($r=$query->fetch()){
						$hr=$r->EmployeeId;
					} */
					$count=$query->num_rows();
					foreach($query->result() as $r){
						$hr=$r->EmployeeId;
					}
					$senior = $this->getApprovalLevelEmp($userid, $orgid, 8);
					if($senior!=0)
					{
						Trace("Senior not zero");
						$temp1 = explode(",", $senior);
						for($i=0;$i<count($temp1);$i++)
						{
							if($temp1[$i] == $hr){
								unset($temp1[$i]);
							}
						}
						$senior=implode(',',$temp1);
						if($hr !=0){
							if($senior!='')
								$senior.=','.$hr;
							else
								$senior=$hr;
						}
						$temp = explode(",", $senior);
						for($i=0; $i<count($temp); $i++)
						{
							Trace($temp[$i]);
							if($temp[$i] != 0){
								
								///////// fetching timeoff approval history ///////////
								
								$approverhistory="";
									$sql = "SELECT * FROM TimeoffApproval WHERE OrganizationId = ? AND TimeofId = ? AND ApproverSts<>3 ";
									$query = $this->db->query($sql,array($orgid, $leaveid));
									/* $query->execute(array($orgid, $leaveid)); */
									$count =  $query->num_rows();
									if($count>=1){
										$approverhistory="<p><b>Approval History</b></p>
										<table border='1' style=' border-collapse: collapse;width:70%'>
										<tr style=' background-color: rgba(107, 58, 137, 0.91);color: rgba(255, 247, 247, 1);'>
															
															<th>Approval Status</th>
															<th>Approver</th>
															<th>Approval Date</th>
															<th>Remarks</th>
										</tr>
										";
									}
									foreach($query->result() as $r){
										$approvername=$this->getEmployeeName($r->ApproverId);
										$approvalsts=$r->ApproverSts;
										if($approvalsts==1){
											$approvalsts="Rejected";
										}elseif($approvalsts==2){
											$approvalsts="Approved";
										}elseif($approvalsts==3){
											$approvalsts="Pending";
										}elseif($approvalsts==4){
											$approvalsts="Cancel";
										}elseif($approvalsts==5){
											$approvalsts="Withdrawn";
										}
										$approvaldate="";
										$approvaldate=date('dd/mm/yyyy',strtotime($r->ApprovalDate));
										$approvercomment=$r->ApproverComment;
										$approverhistory.="<tr>
														
															<th>$approvalsts</th>
																<th>$approvername</th>
															<th>$approvaldate</th>
															<th>$approvercomment</th>
														</tr>";
									}
									
									if($count>=1){
										$approverhistory.="</table>";
									}
								
								$approvelink="https://ubitech.ubihrm.com/approvalbymail/viewapprovetimeoffapproval/$temp[$i]/$orgid/$leaveid/2";
								$rejectlink="https://ubitech.ubihrm.com/approvalbymail/viewapprovetimeoffapproval/$temp[$i]/$orgid/$leaveid/1";
								
								$sql = "INSERT INTO TimeoffApproval ( TimeofId, ApproverId, ApproverSts, CreatedDate , OrganizationId) VALUES (?, ?, ?, ?, ?)";
								$query = $this->db->query($sql,array($leaveid, $temp[$i], 3, $month, $orgid));
								/* $query->execute(array($leaveid, $temp[$i], 3, $month, $orgid)); */
								$empname=getName('EmployeeMaster','FirstName','Id',$userid);
								$seniorname=getName('EmployeeMaster','FirstName','Id',$temp[$i]);
								Trace("$i ".$i);
								if($i==0)
								{
									
									$senioremail=decode5t(getName('EmployeeMaster','CompanyEmail','Id',$temp[$i]));
									Trace("sernioer email".$senioremail);
									$title="Timeoff requested by $empname";
									$msg="<table>
														<tr><td>Hello $seniorname,</td></tr>
												<tr><td>$empname has requested for timeoff.</td></tr>
														
														<tr><td>Applied on:  $mdate1</td></tr>
														<tr><td>Reason for timeoff: $timeoffreason</td></tr>
														<tr><td>Application Date: $timeoffdateformatted</td></tr>
														<tr><td>Duration: from $fromtime1 to $totime1</td></tr>
														<tr></td></td></tr>
														<tr></td></td></tr>
														<tr></td></td></tr>
														<tr></td>Thanks</td></tr>




														
														</table>
														
															$approverhistory
														
														<table>
														<tr><td><br/><br/>
														
																<a href='$approvelink'   style='text-decoration:none;padding: 10px 15px; background: #ffffff; color: green;
																-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; border: solid 1px green; text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.4); -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2); -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);'>Approve</a>
																&nbsp;&nbsp;
																&nbsp;&nbsp;
																<a href='$rejectlink'  style='text-decoration:none;padding: 10px 15px; background: #ffffff; color: brown;
																-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; border: solid 1px brown; text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.4); -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2); -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);'>Reject</a>
																<br/><br/>
																</td>															
																</tr>
													</table>";
									Trace($senioremail." ".$msg);
									/* Utils::sendMail($senioremail,$empname,$title,$msg); */
									sendEmail_new($senioremail,$title,$msg,"","",$empname,$orgid);
								}else{
									Trace("i!=0 ".$i);
								}
							}
						}
					}else{
						Trace("Get senior id");
						$senior=$this->getSeniorId($userid, $orgid);
						
						if($senior != $hr){
							if($hr !=0)
								$senior.=','.$hr;
						}
						$temp = explode(",", $senior);
						for($i=0; $i<count($temp); $i++)
						{
							if($temp[$i] != 0){
									///////// fetching timeoff approval history ///////////
								
								$approverhistory="";
									$sql = "SELECT * FROM TimeoffApproval WHERE OrganizationId = ? AND TimeofId = ? AND ApproverSts<>3 ";
									$query = $this->db->query($sql,array($orgid, $leaveid));
									/* $query->execute(array($orgid, $leaveid)); */
									$count =  $query->num_rows();
									if($count>=1){
										$approverhistory="<p><b>Approval History</b></p>
										<table border='1' style=' border-collapse: collapse;width:70%'>
										<tr style=' background-color: rgba(107, 58, 137, 0.91);color: rgba(255, 247, 247, 1);'>
															
															<th>Approval Status</th>
															<th>Approver</th>
															<th>Approval Date</th>
															<th>Remarks</th>
										</tr>
										";
									}
									foreach($query->result() as $r){
										$approvername=$this->getEmployeeName($r->ApproverId);
										$approvalsts=$r->ApproverSts;
										if($approvalsts==1){
											$approvalsts="Rejected";
										}elseif($approvalsts==2){
											$approvalsts="Approved";
										}elseif($approvalsts==3){
											$approvalsts="Pending";
										}elseif($approvalsts==4){
											$approvalsts="Cancel";
										}elseif($approvalsts==5){
											$approvalsts="Withdrawn";
										}
										$approvaldate="";
										$approvaldate=date('dd/mm/yyyy',strtotime($r->ApprovalDate));
										$approvercomment=$r->ApproverComment;
										$approverhistory.="<tr>
															
															<th>$approvalsts</th>
															<th>$approvername</th>
															<th>$approvaldate</th>
															<th>$approvercomment</th>
														</tr>";
									}
									
									if($count>=1){
										$approverhistory.="</table>";
									}
								
								$sql = "INSERT INTO TimeoffApproval ( TimeofId, ApproverId, ApproverSts, CreatedDate ,   OrganizationId) VALUES (?, ?, ?, ?, ?)";
								$query = $this->db->query($sql,array($leaveid, $temp[$i], 3, $month, $orgid));
								/* $query->execute(array($leaveid, $temp[$i], 3, $month, $orgid)); */
								$empname=getName('EmployeeMaster','FirstName','Id',$userid);
								$seniorname=getName('EmployeeMaster','FirstName','Id',$temp[$i]);
								if($i==0){
								$senioremail=decode5t(getName('EmployeeMaster','CompanyEmail','Id',$temp[$i]));
								$title="Timeoff requested by $empname";
								$msg="<table>
												<tr><td>Hello $seniorname,</td></tr>
												<tr><td>$empname has requested for timeoff.</td></tr>
												<tr><td>Applied on:  $mdate1</td></tr>
												<tr><td>Reason for timeoff : $timeoffreason</td></tr>
												<tr><td>Application Date: $timeoffdateformatted</td></tr>
												<tr><td>Duration : from $fromtime1 to $totime1</td></tr>

												<tr><td> </td></tr>
												<tr><td> </td></tr>
												<tr><td> </td></tr>
												<tr><td>Thanks </td></tr>
												</table>

												
												$approverhistory
												
												<table>
												<tr><td><br/><br/>
														<a href='$approvelink'   style='text-decoration:none;padding: 10px 15px; background: #ffffff; color: green;
														-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; border: solid 1px green; text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.4); -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2); -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);'>Approve</a>
														&nbsp;&nbsp;
														&nbsp;&nbsp;
														<a href='$rejectlink'  style='text-decoration:none;padding: 10px 15px; background: #ffffff; color: brown;
														-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; border: solid 1px brown; text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.4); -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2); -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);'>Reject</a>
														<br/><br/>
														</td>															
														</tr>	
											</table>";
								Trace($senioremail." ".$msg);
								sendEmail_new($senioremail,$title,$msg,"","",$empname,$orgid);
								}else{
									Trace("else i!=0");
								}
							}
						}
					}
					
					
				} else {
				   $status =false;
				   $errorMsg = EMPLOYEELEAVE_MODULE_CREATION_FAILED;
				}
			}else{
				$status =false;
				$errorMsg = "Timeoff already exist";
			}
			}else{
				$status =false;
				$errorMsg = "Timeoff should be between shift timing";
			}
			
		
		}catch(Exception $e) {
			$errorMsg = 'Message: ' .$e->getMessage();
		}
	
		$result["data"] =$data;
		$result['status']=$status;
		$result['successMsg']=$successMsg;
		$result['errorMsg']=$errorMsg;
		
        // default return
        return $result;
    }
	
			public function getApprovalLevelEmp($empid, $orgid, $processtype)
	{
		//processtype 1 for leave, 2 for salary advance, 3 for document request, 4 for resignation, 5 for termination
		//$orgid = $_SESSION['ubihrm_org_id'];	//ORG ID CONTAINS IN ARRAY SECOND VALUE;
		//$processtype = 1;
		$id = "0";
		$seniorid=0;
		$designation=0;
		
		if($empid!="0" && $empid!="")
		{
			$sql = "SELECT ReportingTo, Designation FROM EmployeeMaster WHERE OrganizationId = ? and Id = ? ";
			$query = $this->db->query($sql,array($orgid, $empid));
			/* $query->execute(array($orgid, $empid)); */
			foreach($query->result() as $row)
			{
				$seniorid = $row->ReportingTo;
				$designation = $row->Designation;
			}
			
			if($seniorid!=0 && $designation !=0)
			{
				$sql = "SELECT RuleCriteria, Designation,HrStatus FROM ApprovalProcess WHERE OrganizationId = ? and Designation = ?  and ProcessType = ? ";
				$query = $this->db->query($sql,array($orgid, $designation, $processtype));
				/* $query->execute(array($orgid, $designation, $processtype)); */
				if($query->num_rows()>0)
				{
					$rule = "";
					$sts = "";
					 if($row = $query->result()){
						$rule = $row[0]->RuleCriteria;
						$sts = $row[0]->HrStatus; 
					 }
					/* foreach($query->result() as $row)
					{
						$rule = $row->RuleCriteria;
						$sts = $row->HrStatus;
					} */
					$reportingto = $this->getSeniorId($empid, $orgid);
					$seniorid = "";
					
					$sql = "SELECT Id, Designation FROM EmployeeMaster WHERE OrganizationId = ? and DOL='0000-00-00' and Designation in ( $rule )  and Id in ( $reportingto ) order by FIELD(Designation, $rule)"; /////////
					///////////sts=0 for all the designation and employee,if sts=1 then hierarchy employee will come///////
					//if($sts==0)
					//$sql = "SELECT Id, Designation FROM EmployeeMaster WHERE OrganizationId = ? and DOL='0000-00-00' and Designation in ( $rule )";
				
				
					$query = $this->db->query($sql,array($orgid));
					/* $query->execute(array($orgid)); */
					foreach($query->result() as $row)
					{
						if($seniorid=="")
						$seniorid = $row->Id;
						else
						$seniorid .= ",".$row->Id;
					}
				}
			}
		}
			return $seniorid;
	}
	
	public function getSeniorId($empid, $orgid)
	{
		//$orgid = $_SESSION['ubihrm_org_id'];	//ORG ID CONTAINS IN ARRAY SECOND VALUE;
		$id = "0";
		
		$parentid=$empid;
		if($parentid!="0" && $parentid!="")
		{
				$sql1 = "SELECT ReportingTo FROM EmployeeMaster WHERE OrganizationId = ? and Id in ( $parentid ) and  DOL='0000-00-00' ";
				
				$query1 = $this->db->query($sql1,array($orgid));
				/* $query1->execute(array($orgid)); */
				$parentid="";
				foreach($query1->result() as $row1)
				{
				$id = $row1->ReportingTo;
					
				}
				
			
		}
			return $id;
	}
	
	public function UpdateTimeoffSts()
    {
		$result = array();
		$count=0; $errorMsg=""; $successMsg=""; $status=false;
		$data = array();
		try{
			$uid=isset($_REQUEST['uid'])?$_REQUEST['uid']:'0';
			$orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:'0';
			$timeoffid=isset($_REQUEST['timeoffid'])?$_REQUEST['timeoffid']:'0';
			$val=isset($_REQUEST['timeoffsts'])?$_REQUEST['timeoffsts']:'0';
			
			$sql = "UPDATE Timeoff SET ApprovalSts = ? WHERE  Id = ?";
			$query = $this->db->query($sql,array( 5,$timeoffid));
			/* $query->execute(array( 5,$timeoffid)); */
			$count =  $this->db->affected_rows();
			
			$sql = "UPDATE TimeoffApproval  SET ApproverSts = ? WHERE  	TimeofId = ? ";
			$query = $this->db->query($sql,array( 5,$timeoffid));
			
			$sql1="select * from Timeoff where Id = ?";
			$query1=$this->db->query($sql1,array($timeoffid));
			/* $query1->execute(array($timeoffid)); */
			foreach($query1->result() as $row){
				$timeoffdateformatted=date('Y-m-d',strtotime($row->TimeofDate));
				$fromtime=$row->TimeFrom;
				$totime=$row->TimeTo;
				$timeoffreason=$row->Reason;	
			}
			Trace($sql." ".$timeoffid);
			if($count>=1){
				$sendmail=$this->getSeniorId($uid,$orgid);
				$assignedbyemail=decode5t(getName('EmployeeMaster','CompanyEmail','Id',$sendmail));
				$assignedbyname=$this->getEmployeeName($sendmail);
				$assignedtoname=$this->getEmployeeName($uid);
				$fname=getName('EmployeeMaster','FirstName','Id',$uid);
				$gender=getName('EmployeeMaster','Gender','Id',$uid);
				$genderverb="";
				if($gender==1)
				$genderverb="his";
				elseif($gender==2)
				$genderverb="her";
				$sub="Withdraw timeoff by $assignedtoname";
				$msg="
				<table>
				<tr><td>Dear $assignedbyname,</td></tr>
				<tr><td></td></tr>
				<tr><td>$assignedtoname withdrawn $genderverb request for timeoff.</td></tr>
				<tr><td><b>Details are given below:</b></td></tr>
				<tr><td>Reason for timeoff: $timeoffreason</td></tr>
				<tr><td>Date: $timeoffdateformatted</td></tr>
				<tr><td>Duration: from $fromtime to $totime</td></tr>
				
					
				</table>";
				
				$sts=sendEmail_new($assignedbyemail,$sub,$msg,"","",$fname);
				Trace($sts." ".$assignedbyemail." ".$sub." ".$msg);
				$status =true;
				$successMsg="Timeoff application has been successfully withdrawn.";
			}else {
			   $status =false;
			   $errorMsg="Timeoff application has been already withdrawn.";
			}
		}catch(Exception $e) {
			$errorMsg = 'Message: ' .$e->getMessage();
		}
		$result["data"] =$data;
		$result['status']=$status;
		$result['successMsg']=$successMsg;
		$result['errorMsg']=$errorMsg;
		
		return $result;
    }
	
	public function CreateBulkAtt()
    {
		$result = array();
		$count=0; $errorMsg=""; $successMsg=""; $status=false;
		$data = array();
        $mid   = isset($_REQUEST['uid'])?$_REQUEST['uid']:0;	//USER ID CONTAINS IN ARRAY FIRST VALUE;
		$orgid = isset($_REQUEST['org_id'])?$_REQUEST['org_id']:0;//ORG ID CONTAINS IN ARRAY SECOND VALUE;
		
		$location = isset($_REQUEST['location'])?$_REQUEST['location']:0;
		$lat = isset($_REQUEST['lat'])?$_REQUEST['lat']:0;
		$long = isset($_REQUEST['long'])?$_REQUEST['long']:0;
		$platform = isset($_REQUEST['platform'])?$_REQUEST['platform']:"";
		
		
		if($mid!=0)
        	$zone  = getEmpTimeZone($mid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
		//$zone    = getTimeZone($orgid);
        date_default_timezone_set($zone);
		//$attlist = isset($_REQUEST['attlist'])?$_REQUEST['attlist']:'';//ORG ID CONTAINS IN ARRAY SECOND VALUE;
				
		$mdate = date("Y-m-d H:i:s");
		$time = date("H:i:s");
		$date = date("Y-m-d");
		//$tdate = date("Y-m-d");
		$res1 = isset($_REQUEST['attlist'])?json_decode($_REQUEST['attlist'], true):''; 
		$skip=0;
		try{
		foreach($res1 as $res)
		{
            //AutoTimeOffEnd($res['Id'], $orgid, $time, $date, $mdate, $location, $lat, $long);
            AutoTimeOffEndWL($res['Id'], $orgid ,$time ,$date ,$mdate);
            /////////// This query is from auto visit out/////////////
            //$query=$this->db->query("update `checkin_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00'",array('Auto Visit Out Punched',$res['Id']));
            /////////// This query is from auto visit out/////////////
            //Trace('timeout11:'.$res['timeout']);
		    //Trace($res['Id']);
			$tdate = isset($res['data_date'])?$res['data_date']:date("Y-m-d");
			$count +=1;
			if(isset($res['Attid'])&& $res['Attid']!="0"){
				
				$overtime='00:00';
					$shifttype=getShiftType($res['shift']);
					$timeoutdate=$tdate;
					if($shifttype!=1){ // multi-date shift
						if(strtotime($res['timein']) > strtotime($res['timeout'])){ 
							$timeoutdate=date("Y-m-d", strtotime($timeoutdate. ' + 1 days'));
							$queryot = $this->db->query("SELECT SUBTIME( SUBTIME( timein, timeout ) , SUBTIME(  '".$res['timein']."',  '".$res['timeout']."' ) ) AS overtime FROM ShiftMaster WHERE id =".$res['shift']);
							if($rowot = $queryot->row())
								$overtime=$rowot->overtime;	
						}else{
							$queryot = $this->db->query("SELECT SUBTIME( SUBTIME(  '".$res['timeout']."',  '".$res['timein']."' ) , SUBTIME( timein, timeout ) ) AS overtime FROM ShiftMaster WHERE id=".$res['shift']);
							if($rowot = $queryot->row())
								$overtime=$rowot->overtime;
						}
					}else { // single date shift
						$queryot = $this->db->query("SELECT SUBTIME( SUBTIME( timein, timeout ) , SUBTIME(  '".$res['timein']."',  '".$res['timeout']."' ) ) AS overtime FROM ShiftMaster WHERE id =".$res['shift']);
						
						if($rowot = $queryot->row())
								$overtime=$rowot->overtime;	
					}
				//Trace("Attid11");
			//Trace($res['Attid']);
				
			$queryy = $this->db->query("UPDATE AttendanceMaster SET TimeOut=?, ExitImage=?,timeoutdate=?,Overtime=?, device=? where Id=? and AttendanceDate=?",array($res['timeout'],'https://ubiattendance.ubihrm.com/assets/img/managerdevice.png',$timeoutdate,$overtime,'AppManager',$res['Attid'],$tdate));
			//Trace($queryy);
			/* $query1 = $this->db->prepare($sql1);
			$query1->execute(array($res[$i]['Id'],  $tdate)); */
			$count1 =  $this->db->affected_rows();
			}
			else{
				Trace("inelse");
				//Trace($res['Attid']);
			//if($res['mark']==0){
			/* echo($res['attsts']);
			return; */
			
			$query1 = $this->db->query("Select EmployeeId from AttendanceMaster WHERE EmployeeId=? and AttendanceDate=?",array($res['Id'],  $tdate));
			/* $query1 = $this->db->prepare($sql1);
			$query1->execute(array($res[$i]['Id'],  $tdate)); */
			$count1 =  $query1->num_rows();				
			if($count1==0)
			{	
			$empdept=getName('EmployeeMaster','Department','Id',$res['Id']);
			$empdesig=getName('EmployeeMaster','Designation','Id',$res['Id']);
			$emparea_assign=getName('EmployeeMaster','area_assigned','Id',$res['Id']);
			//////----------------getting shift info
            
				if($res['attsts']==2)
				{
					$data1 = array(
					'EmployeeId'=>$res['Id'],
					'AttendanceDate'=>$tdate,
					'AttendanceStatus'=>$res['attsts'],
					'ShiftId'=>$res['shift'],
					'Dept_id'=>$empdept,
					'Desg_id'=>$empdesig,
					'areaId'=>$emparea_assign,
					'OrganizationId'=>$orgid,
					'CreatedDate'=>$mdate,
					'CreatedById'=>$mid,
					'LastModifiedDate'=>$mdate,
					'LastModifiedById'=>$mid,
					'OwnerId'=>$mid,
					'device'=>'AppManager',
					'timeoutdate'=>$res['todate'],
					'platform'=>$platform
				);
				$query=$this->db->insert('AttendanceMaster',$data1);
				
				}else{
					if($res['timein']=="0:0" || $res['timein']=="00:00:00" || $res['timein']=="00:00")
						$res['timein']="00:01:00";
					if($res['timeout']=="00:00" || $res['timeout']=="0:0")
						$res['timeout']="23:59:00";
					
					//$res['timein']=date('H:i');		//need current time for bulk attendance
					$res['timein']=date('H:i',strtotime($res['timein']));
					//$res['timein']=date('H:i',strtotime($res['timein']));//commented because need current time for bulk attendance
					$res['timeout']=date('H:i',strtotime($res['timeout']));
					$overtime='00:00';
					$shifttype=getShiftType($res['shift']);
				//	$queryot = $this->db->query("SELECT subtime(subtime('".$res['timeout']."','".$res['timein']."'), (select subtime(timeout,timein) from ShiftMaster where id=".$res['shift'].")) as overtime");
					
					$timeindate=date("Y-m-d");
					$timeoutdate=$tdate;
					if($shifttype!=1){ // multi-date shift
						if(strtotime($res['timein']) > strtotime($res['timeout'])){ 
							$timeoutdate=date("Y-m-d", strtotime($timeoutdate. ' + 1 days'));
							$queryot = $this->db->query("SELECT SUBTIME( SUBTIME( timein, timeout ) , SUBTIME(  '".$res['timein']."',  '".$res['timeout']."' ) ) AS overtime FROM ShiftMaster WHERE id =".$res['shift']);
							if($rowot = $queryot->row())
								$overtime=$rowot->overtime;	
						}else{
							$queryot = $this->db->query("SELECT SUBTIME( SUBTIME(  '".$res['timeout']."',  '".$res['timein']."' ) , SUBTIME( timein, timeout ) ) AS overtime FROM ShiftMaster WHERE id=".$res['shift']);
							if($rowot = $queryot->row())
								$overtime=$rowot->overtime;
						}
					}else { // single date shift
						$queryot = $this->db->query("SELECT SUBTIME( SUBTIME( timein, timeout ) , SUBTIME(  '".$res['timein']."',  '".$res['timeout']."' ) ) AS overtime FROM ShiftMaster WHERE id =".$res['shift']);
						
						if($rowot = $queryot->row())
								$overtime=$rowot->overtime;	
					}
					$data1=array();
					//Trace('timeout22:'.$res['timeout']);
					if($res['timeout']=="00:00"){
					$data1 = array(
					'EmployeeId'=>$res['Id'],
					'AttendanceDate'=>$tdate,
					'AttendanceStatus'=>$res['attsts'],
					'TimeIn'=>$res['timein'],
					'timeindate'=>$timeindate,
					'ShiftId'=>$res['shift'],
					'Dept_id'=>$empdept,
					'Desg_id'=>$empdesig,
					'areaId'=>$emparea_assign,
					'OrganizationId'=>$orgid,
					'CreatedDate'=>$mdate,
					'CreatedById'=>$mid,
					'LastModifiedDate'=>$mdate,
					'LastModifiedById'=>$mid,
					'OwnerId'=>$mid,
					'device'=>'AppManager',
					'EntryImage'=>'https://ubiattendance.ubihrm.com/assets/img/managerdevice.png',
					'platform'=>$platform,
				);
				}else{
				$data1 = array(
					'EmployeeId'=>$res['Id'],
					'AttendanceDate'=>$tdate,
					'AttendanceStatus'=>$res['attsts'],
					'TimeIn'=>$res['timein'],
					'timeindate'=>$timeindate,
					'timeoutdate'=>$timeoutdate,
					'TimeOut'=>$res['timeout'],
					'Overtime'=>$overtime,
					'ShiftId'=>$res['shift'],
					'Dept_id'=>$empdept,
					'Desg_id'=>$empdesig,
					'areaId'=>$emparea_assign,
					'OrganizationId'=>$orgid,
					'CreatedDate'=>$mdate,
					'CreatedById'=>$mid,
					'LastModifiedDate'=>$mdate,
					'LastModifiedById'=>$mid,
					'OwnerId'=>$mid,
					'device'=>'AppManager',
					'EntryImage'=>'https://ubiattendance.ubihrm.com/assets/img/managerdevice.png',
					'ExitImage'=>'https://ubiattendance.ubihrm.com/assets/img/managerdevice.png',
                   'platform'=>$platform,					
				);
				}//'timeoutdate'=>$res['todate']
				/*'checkInLoc'=>$location
					'latit_in'=>$lat,
					'longi_in'=>$long,
					'CheckOutLoc'=>$location,
					'latit_out'=>$lat,
					'longi_out'=>$long,*/
				$query=$this->db->insert('AttendanceMaster',$data1);
				$to=decode5t(getName('EmployeeMaster','CompanyEmail','Id',$res['Id']));
				$subject= $res['Name'].' has punched Time in';
				$msg=$res['Name'].' has punched Time in at '.$res['timein'].' from '.$location;
				
				}		
					if($query){
						$count++;
					}
			}else{
				$skip++;
			}
		//}
		}
		}
		}catch(Exception $e) {
			$errorMsg = 'Message: ' .$e->getMessage();
		}
        if ($count >0) {
			$count = $count-count($res1);
			   $status =true;
			   $successMsg = $count." records saved successfully";
			if($skip>0){
				$successMsg .= $skip." records skipped";
			}
        } else {
           $status =false;
		   $errorMsg = 'There is some problem while creating record';
        }
		
		$result["data"] =$data;
		$result['status']=$status;
		$result['successMsg']=$successMsg;
		$result['errorMsg']=$errorMsg;
		
        // default return
        return $result;
    }
	public function getDeptEmp()
    {
		// this function use in 
		$result = array();
		$res = array();
		$count=0; $errorMsg=""; $successMsg=""; $status=false;
		$data = array();
		$cond= "";
		$OutPushNotificationStatus=0;
		$InPushNotificationStatus=0;
		$orgid  = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : 0;
		$deptid = isset($_REQUEST['dept']) ? $_REQUEST['dept'] : 0;	
		$empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;	
		$datafor = isset($_REQUEST['datafor']) ? $_REQUEST['datafor'] : '';
		//$date = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
		//$zone   = getTimeZone($orgid);
		$zone    = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);		
		$sts=1;	
		$date = date('Y-m-d');
		$Predate=date('Y-m-d',strtotime("-1 days"));
		if($datafor=="Yesterday"){
		//$date = '2017-06-02';
		$date = $Predate;
		$res['data_date']=$Predate;
		
		}
		else{
			$res['data_date']=$date;
			$date =$date;
		}
		
		$adminstatus = getAdminStatus($empid);
		$cond = "";
		if($adminstatus == '2')
		{
	     	$dptid = getDepartmentIdByEmpID($empid);
			$cond = " AND Department = $dptid  ";
		}
		
		if($datafor==''){
		   $attcond="and Id not in (select EmployeeId from AttendanceMaster where AttendanceDate='$date' and OrganizationId = $orgid )";
		}
		else if($datafor=='All'){
		$attcond='';
		}
		
		else if($datafor=='Yesterday')
		{
		    $attcond="$cond and Id in (select EmployeeId from AttendanceMaster where  AttendanceDate='$date' and OrganizationId = $orgid and ((TimeIn!='00:00:00' AND TimeOut !='00:00:00' and device ='Auto Time Out' AND Timein = Timeout )))";
		}
		
		else{
			$attcond = "$cond and Id not in (select EmployeeId from AttendanceMaster where  AttendanceDate='$date' and OrganizationId = $orgid and ((TimeIn!='00:00:00' AND TimeOut!='00:00:00') or AttendanceStatus=2))";
		}
		
		//$date = '2017-06-02';
		////////  FIND OUT THE HOLIDAY ON THE DAY OF ATTENDANCE  ///////////////////////
		
		$holidaycount=0;
		$query = $this->db->query("SELECT  Id FROM HolidayMaster WHERE OrganizationId = '$orgid'  and ('$date' between DateFrom and DateTo)");
		try{
			
			$holidaycount =  $query->num_rows();
		}
		catch(Exception $e) 
		{
			$errorMsg = 'Message: ' .$e->getMessage();
		}
		
		///////// FETCH ALL RECORD OF ALL EMPLOYEE FOR THE DAY OF ATTENDANCE  ////////////////////////
			$query = $this->db->query("SELECT Id, EmployeeCode,InPushNotificationStatus, OutPushNotificationStatus, FirstName, LastName, Shift, ImageName FROM EmployeeMaster WHERE OrganizationId = '$orgid' and Is_Delete=0 and archive!=0 and (DOL='0000-00-00' OR DOL>='$date') and DOJ<='$date'  $attcond Order by FirstName, LastName");
			
			
			//Department='$deptid' and
			//Trace("SELECT Id, EmployeeCode, FirstName, LastName, Shift, ImageName FROM EmployeeMaster WHERE OrganizationId = '$orgid' and Is_Delete=0 and (DOL='0000-00-00' OR DOL>='$date') and DOJ<='$date' and Department='$deptid' and Id not in (select EmployeeId from AttendanceMaster where AttendanceDate='$date' and OrganizationId = $orgid) Order by FirstName, LastName");
			//$query = $this->db->prepare($sql);
			try{
				  $count =  $query->num_rows();
			}catch(Exception $e) {
				$errorMsg = 'Message: ' .$e->getMessage();
			}
			if($count>=1)
			{
			$status=true;
			$successMsg=$count." record found";
			foreach ($query->result() as $row)
			{
				$res['Attid']="0";
				///////////	 FIND OUT THE ANY LEAVE ON THE DAY OF ATTENDANCE FOR THE EMPLOYEE  //////////////////////
					
					//////////////  FIND OUT THE WEEK OFF AND HALF DAY ON THE DAY OF ATTENDANCE FOR AN EMPLOYEE /////////
				$OutPushNotificationStatus=$row->OutPushNotificationStatus;
				$InPushNotificationStatus=$row->InPushNotificationStatus;
				$empid=$row->Id;
				$weekofflg=false;$halfflg=false;
				$weekno=weekOfMonth($date);
				$dayofdate= 1 + date("w", strtotime($date));
				$query2 = $this->db->query("SELECT WeekOff FROM ShiftMasterChild where ShiftId=(select shift from EmployeeMaster where Id=$empid) and Day=$dayofdate");
				//Utils::Trace($sql2." Date used - ".$leavefrom." day of month -  ".$dayofdate." week of month - ".$weekno);
				$week="";
				if($row2 = $query2->row())
				{
					$week=$row2->WeekOff;
				}
				$weekarr=explode(",",$week);
				if($query2->num_rows()>0){
					if($weekarr[$weekno-1]==1)
					{
						$weekofflg=true; 
					}
					else if($weekarr[$weekno-1]==2)
					{
						$halfflg=true;  
					}
				}
						/////////////////////////////////////////////////////////////////////////////////////////////////////		
					//// status 1 for present, 2 for absent, 3 for weekoff, 4 for halfday,
					////	5 for holiday, 6 for leave, 7 for comp off, 8 for work from home
						$sts=1;
						if($holidaycount>0){
							$sts=5;
						}
						if($halfflg){
							$sts=4;
						}
						if($weekofflg){
							$sts=3;
						}
						
						
						
						
						$query3 = $this->db->query("SELECT * FROM AttendanceMaster where EmployeeId=$empid and AttendanceDate='$date' and  (TimeOut='00:00:00' or TimeIn=TimeOut )");
						$res['rtimein']="00:00:00";
						$res['rtimeout']="00:00:00";
						if($query3->num_rows()>0){
							
							
						if($row3 = $query3->row()){
							$rtimein=$row3->TimeIn;
							$rtimeout=$row3->TimeOut;
							$res['Attid']=$row3->Id;
							$res['device']=$row3->device;
						}
						
						
						if($rtimein!=''){
							$res['rtimein']=$row3->TimeIn;	
							}
							if($rtimeout!=''){
							$res['rtimeout']=$row3->TimeOut;	
							}
							}
							
							$res['id'] = $row->Id;
							$res['empcode'] = $row->EmployeeCode;
							$FirstName=trim($row->FirstName);
							$FirstName=preg_replace('/\s\s+/', ' ',$FirstName);
							//echo $FirstName;
							$LastName=trim($row->LastName);
							//$LastName=preg_replace('/\s\s+/', '',$LastName);
							//echo $LastName;
							$res['name'] = ucwords(strtolower($FirstName." ".$LastName));
							//echo $res['name'];
							//echo '<br>';
							//$res['timein'] = "00:00";
							//$res['timeout'] = "00:00";
							$res['shifttimein'] = "00:00";
							$res['shifttimeout'] = "00:00";
							//$res['shifttimeinbreak'] = "00:00";
							//$res['shifttimeoutbreak'] = "00:00";
							//$res['totaltime'] = "00:00";
							$res['overtime'] = "00:00";
							$res['attsts'] = ''.$sts;
							$res['todate'] = $date;
							$res['shift'] = $row->Shift;
							$res['shiftname'] = getName('ShiftMaster','Name','Id',$row->Shift);
							$res['shifttype'] = getName('ShiftMaster','shifttype','Id',$row->Shift);
							$res['OutPushNotificationStatus'] = $OutPushNotificationStatus;
							$res['InPushNotificationStatus'] = $InPushNotificationStatus;
							
							if ($row->ImageName != "") {
								$dir             = $orgid . "/" . $row->ImageName;
								$res['img'] = 'https://ubitech.ubihrm.com/public/uploads/'. $dir;
							   // $data['profile'] = "http://ubiattendance.ubihrm.com/" . $dir;
							} else {
								$res['img'] = "http://ubiattendance.ubihrm.com/assets/img/avatar.png";
							}
							//if(($weekofflg) || ($holidaycount>0) || $leavecount>0){
							//}else{
							$query1 = $this->db->query("SELECT TimeIn, TimeOut, TimeInBreak, TimeOutBreak, TIME_FORMAT(TIMEDIFF( TIME_FORMAT(TIMEDIFF(TimeOut, TimeIn),'%H:%i'),TIME_FORMAT(TIMEDIFF(TimeOutBreak, TimeInBreak),'%H:%i')),'%H:%i') as totaltime FROM ShiftMaster WHERE Id = ? ",array($row->Shift));
							/* $query1 = $this->db->prepare($sql1);
							$query1->execute(array($row->Shift)); */
							foreach ($query1->result() as $row1)
							{
							//	$res['totaltime'] = $row1->totaltime;
								
								$res['timein'] = $row1->TimeIn;
								
								$res['shifttimein'] = $row1->TimeIn;
								
								//$res['shifttimeinbreak'] = $row1->TimeInBreak;
								//$res['shifttimeoutbreak'] = $row1->TimeOutBreak;
								//////////// if half day then time should change to half day
								if($halfflg){
									$res['timeout'] = $row1->TimeInBreak;
									$res['shifttimeout'] = $row1->TimeInBreak;
								}else{
									$res['timeout'] = $row1->TimeOut;
									$res['shifttimeout'] = $row1->TimeOut;
								}
						$res['timein'] = ($row1->TimeIn!='00:00:00')?date('H:i',strtotime($row1->TimeIn)):$row1->TimeIn;

							}
							if(($weekofflg) || ($holidaycount>0))
							{
								$res['timein'] = "00:00";
								$res['timeout'] = "00:00";
								//$res['totaltime'] = "00:00";
								$res['overtime'] = "00:00";
							}
							
					
							//$res['timein'] = date('H:i');   //need current time for bulk attendance in timein
							$res['mark'] = 0;
							//}
							$data[] = $res;
						//}
					
			
			}
			}else{
				$status=false;
				$errorMsg = 'error...';
			}
			
			// list of employees who has already marked their attendance---
			/*$query = $this->db->query("SELECT e.Id, e.EmployeeCode, e.FirstName, e.LastName, e.Shift, e.ImageName, a.Id as aid, a.TimeIn, a.TimeOut, a.AttendanceStatus, a.AttendanceDate FROM EmployeeMaster e, AttendanceMaster a WHERE e.OrganizationId = '$orgid' and e.Is_Delete=0 and e.Id!='$empid'  and e.Id= a.EmployeeId and a.AttendanceDate='$date' and a.OrganizationId=e.OrganizationId Order by a.TimeIn desc");//and e.Department='$deptid'
			try{
				$count =  $query->num_rows();
			}catch(Exception $e) {
				$errorMsg = 'Message: ' .$e->getMessage();
			}
			if($count>=1)
			{
				$status=true;
				$successMsg=$count." record found";
				foreach ($query->result() as $row)
				{
				
					$empid=$row->Id;
					$res = array();
					$res['id'] = $row->Id;
					$res['empcode'] = $row->EmployeeCode;
					$res['name'] = ucwords(strtolower($row->FirstName." ".$row->LastName));
					$res['timein'] = ($row->TimeIn!='00:00:00')?date('H:i',strtotime($row->TimeIn)):$row->TimeIn;
					$res['timeout'] = ($row->TimeOut!='00:00:00')?date('H:i',strtotime($row->TimeOut)):$row->TimeOut;
					$res['attsts'] = ($row->AttendanceStatus==1)?'P':'A';
					$res['todate'] = $date;
					$res['shift'] = $row->Shift;
					$res['mark'] = 1;
					$res['shiftname'] = getName('ShiftMaster','Name','Id',$row->Shift);
					$res['shifttype'] = getName('ShiftMaster','shifttype','Id',$row->Shift);
					if ($row->ImageName != "") {
						$dir = $orgid . "/" . $row->ImageName;
						$res['img'] = IMGURL. $dir;
					} else {
						$res['img'] = "http://ubiattendance.ubihrm.com/assets/img/avatar.png";
					}
					$data[] = $res;
				}
			}*/
		$result['data'] =$data;
		$result['status']=$status;
		$result['successMsg']=$successMsg;
		$result['errorMsg']=$errorMsg;
		echo json_encode($data);
		//return $result;
    }
  public function SaveImageFaceId()
    {
        $userid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $faceid = "";
        $persongroup_id = "";
        $personid = "";
        $fid = "0";
        $persistedfaceid = "0";
        $attImage = 0;
        $zone    = getEmpTimeZone($userid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);		
        $date = date("Y-m-d H:i:s");
        $date1 = date("Y-m-d");
		$FaceIdRegPerm=getNotificationPermission($orgid,'FaceIdReg');
        $new_name = "https://ubitech.ubihrm.com/public/avatars/male.png";
        $attImage = getAttImageStatus($orgid);
         if($attImage){ // true, image must be uploaded. false, optional image
			 $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name)){
			Trace('image not uploaded--'.$userid);
			$result['status']=3;
			$result['errorMsg']='Error in moving the image. Try later.';
			$result['successMsg'] = '';
			echo json_encode($result);
			return;
			}	
			$new_name =IMGURL.$new_name;
		}

		/////////// Push Notifiction ///////////


		 $string=getOrgName($orgid);
                    $string=ucwords($string);
        
                    $string = str_replace('', '-', $string); // Replaces all spaces with hyphens.
        
                    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
                    
                    $orgTopic=$string.$orgid;

           /////////// Push Notifiction /////////////////
        
        $sql7 = "select PersonGroupId from licence_ubiattendance where OrganizationId = $orgid and Addon_FaceRecognition='1'";
        $query7 = $this
            ->db
            ->query($sql7);
        if ($row7 = $query7->row())
        {
            $persongroup_id = $row7->PersonGroupId;
            $flag = '1';

        }

        if ($flag == '1')
        {
            $sql4 = "select PersonId,FirstName,LastName from  EmployeeMaster where Id=$userid";
            $query4 = $this
                ->db
                ->query($sql4);
            if ($row4 = $query4->row())
            {
                $personid = $row4->PersonId;
                $firstname = $row4->FirstName;
                //$lastname = $row4->LastName;
                
            }

            if ($personid == "")
            {
                $personid = create_person($persongroup_id, $firstname);
                $sql5 = "update EmployeeMaster set PersonId = '$personid' where Id=$userid";
                $query5 = $this
                    ->db
                    ->query($sql5);

            }
        }
        $fid = getfaceid($new_name);
        if ($fid == '0')
        {
            $result['facerecog'] = 'NO_FACE_DETECTED';
            $this->db->close();
            echo json_encode($result);
            return;

        }
        $sql6 = "select PersistedFaceId from Persisted_Face where EmployeeId = $userid";
        $query6 = $this
            ->db
            ->query($sql6);
        if ($row6 = $query6->row())
        {

            if ($row6->PersistedFaceId == '0')

            {   $faceid=getfaceid($new_name);
            	if ($faceid == '0')
        {
            $result['facerecog'] = 'NO_FACE_DETECTED';
            $this->db->close();
            echo json_encode($result);
            return;

        }
            	$personobj = face_identify($faceid, $persongroup_id);
            	if($personobj=='0'){
            		$persistedfaceid = add_face($persongroup_id, $personid, $new_name);
            	}else{
            		$result['facerecog'] = 'FACE_ID_ALREADY_EXISTS';
            		$result['status'] = '7';
                    $this->db->close();
                    echo json_encode($result);
                    return;
            	}
                
                if ($persistedfaceid == '0')
        {
            $result['facerecog'] = 'NO_FACE_DETECTED';
            $this->db->close();
            echo json_encode($result);
            return;

        }

                $sql6 = "update Persisted_Face set PersistedFaceId = '$persistedfaceid' ,profileimage='$new_name',ModifiedDate='$date' where EmployeeId=$userid";
                $query6 = $this
                    ->db
                    ->query($sql6);
                persongrouptrain($persongroup_id);
                $result['facerecog'] = 'FACE_DETECTED';
                ////// Push Notification and Mail///////////////

                 
             	if($FaceIdRegPerm==9|| $FaceIdRegPerm==13 || $FaceIdRegPerm==11|| $FaceIdRegPerm==15){
             	sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$firstname Face ID has been registered", "");
             }
              if($FaceIdRegPerm==5 || $FaceIdRegPerm==13 || $FaceIdRegPerm==7|| $FaceIdRegPerm==15){
                $query= $this->db->query("Select email from admin_login where OrganizationId=$orgid and status=1");
                foreach($query->result() as $row){
                 $email= $row->email;

             $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$firstname.' Face ID has been registered
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "Face ID Registration(".$date1.")";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                //    sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                //    sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
             

                 ////// Push Notification ///////////////

            }
        }else{
        	$faceid=getfaceid($new_name);
        	if ($faceid == '0')
        {
            $result['facerecog'] = 'NO_FACE_DETECTED';
            $this->db->close();
            echo json_encode($result);
            return;

        }
            	$personobj = face_identify($faceid, $persongroup_id);
            	if($personobj=='0'){
            		$persistedfaceid = add_face($persongroup_id, $personid, $new_name);
            	}else{
            		$result['facerecog'] = 'FACE_ID_ALREADY_EXISTS';
            		$result['status'] = '7';
                    $this->db->close();
                    echo json_encode($result);
                    return;
            	}
        	
        	if ($persistedfaceid == '0')
        {
            $result['facerecog'] = 'N0_FACE_DETECTED';
            $this->db->close();
            echo json_encode($result);
            return;

        }
        $sql6 = "insert into Persisted_Face(PersonId,PersistedFaceId,EmployeeId,profileimage,OrganizationId,CreatedDate,ModifiedDate) values ('$personid','$persistedfaceid',$userid,'$new_name',$orgid,'$date','$date')";
        $query6 = $this
            ->db
            ->query($sql6);
        persongrouptrain($persongroup_id);
        $result['facerecog'] = 'FACE_DETECTED';
         ////// Push Notification and Mail///////////////

                 
             	if($FaceIdRegPerm==9|| $FaceIdRegPerm==13 || $FaceIdRegPerm==11|| $FaceIdRegPerm==15){
             	sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$firstname Face ID has been registered", "");
             }
              if($FaceIdRegPerm==5 || $FaceIdRegPerm==13 || $FaceIdRegPerm==7|| $FaceIdRegPerm==15){
                $query= $this->db->query("Select email from admin_login where OrganizationId=$orgid and status=1");
                foreach($query->result() as $row){
                 $email= $row->email;

             $message = '<html>
					<head>
					<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
					<meta name=Generator content="Microsoft Word 12 (filtered)">
					<style>
					</style>

					</head>

					<body lang=EN-US link=blue vlink=purple>

					<hr>
					<br>
					'.$firstname.' Face ID has been registered
					</br>
					</hr>


					</body>

					</html>
                    ';
                    $headers = '';
                    $subject = "Face ID Registration(".$date1.")";
					//Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   //sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
             

                 ////// Push Notification ///////////////
        }

        
        
        $this->db->close();
        echo json_encode($result);

    }
    public function saveImageSandbox()
    {
        $userid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $addr = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $aid = isset($_REQUEST['aid']) ? $_REQUEST['aid'] : 0;
        $act = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'TimeIn';
        $shiftId = isset($_REQUEST['shiftid']) ? $_REQUEST['shiftid'] : 0;
        $orgid = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $latit = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        $FakeLocationStatus = isset($_REQUEST['FakeLocationStatus']) ? $_REQUEST['FakeLocationStatus'] : 0;
        $city   = isset($_REQUEST['city']) ? $_REQUEST['city'] : '';
        $appVersion   = isset($_REQUEST['appVersion']) ? $_REQUEST['appVersion'] : '';
        $geofence   = isset($_REQUEST['geofence']) ? $_REQUEST['geofence'] : '';
        //$name   = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'User';
        $orgTopic   = isset($_REQUEST['globalOrgTopic']) ? $_REQUEST['globalOrgTopic'] : '';
        $name=getEmpName($userid);
        $geofencePerm=getNotificationPermission($orgid,'OutsideGeofence');
        $SuspiciousSelfiePerm=getNotificationPermission($orgid,'SuspiciousSelfie');
        $SuspiciousDevicePerm=getNotificationPermission($orgid,'SuspiciousDevice');
         
        // $zone    = getTimeZone($orgid);
        $zone = getEmpTimeZone($userid, $orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $stamp = date("Y-m-d H:i:s");
        $date = date("Y-m-d");
        $today = date("Y-m-d");
         $currDate=date("Y-m-d");
        $time = date("H:i") == "00:00" ? "23:59" : date("H:i");
        //echo $time;


        $FakeLocationStatusTimeIn = 0;
        $FakeLocationStatusTimeOut = 0;
        $deviceidmobile  = isset($_REQUEST['deviceidmobile']) ? $_REQUEST['deviceidmobile'] : '';
        $devicenamebrand  = isset($_REQUEST['devicenamebrand']) ? $_REQUEST['devicenamebrand'] : '';
        $verifieddevice='';
        $faceid = "";
        //$personid='bf159541-1e75-4cbd-acca-bcb8f4e07b3a';
        $persongroup_id = "";
        $suspicioustimein_status = "0";
        $suspicioustimeout_status = "0";
        $timein_confidence = "";
        $timeout_confidence = "";
        $profileimage="";
        $suspiciousdevice=0;
        $ssdisapp_sts=1;
        $attendance_sts=1;

        $sql= "select Addon_DeviceVerification from licence_ubiattendance where OrganizationId = $orgid";
		$query=$this->db->query($sql);
		if ($row = $query->row()) {
                     $deviceverificationperm = $row->Addon_DeviceVerification;
                      
                     }

         if($deviceverificationperm==1){            

		$sql= "select DeviceId from EmployeeMaster where Id = $userid";
		$query=$this->db->query($sql);
		 if ($row = $query->row()) {
                     $verifieddevice = $row->DeviceId;
                      if($verifieddevice==$deviceidmobile)
                 {
                 	$suspiciousdevice=0;
                 }
                 else
                 {
                    $suspiciousdevice=1;
                    if($SuspiciousDevicePerm==9|| $SuspiciousDevicePerm==13||$SuspiciousDevicePerm==11|| $SuspiciousDevicePerm==15){
                sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name's Attendance Device does not match", "");
             }
              if($SuspiciousDevicePerm==5 || $SuspiciousDevicePerm==13|| $SuspiciousDevicePerm==7|| $SuspiciousDevicePerm==15){
                $query= $this->db->query("Select email from admin_login where OrganizationId=$orgid and status=1");
                foreach($query->result() as $row){
                 $email= $row->email;

             $message = '<html>
                    <head>
                    <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
                    <meta name=Generator content="Microsoft Word 12 (filtered)">
                    <style>
                    </style>

                    </head>

                    <body lang=EN-US link=blue vlink=purple>

                    <hr>
                    <br>
                    '.$name."'s".' Attendance Device is different from their registered Device ID
                    </br>
                    </hr>


                    </body>

                    </html>
                    ';
                    $headers = '';
                    $subject = "Suspicious Device(".$date.")";
                    //Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                  // sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                  // sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
                 }
                     
                  
                 }
             }

             if($geofence=="Outside Geofence"){
                if($geofencePerm==9|| $geofencePerm==13||$geofencePerm==11|| $geofencePerm==15){
                sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name has punched Attendance outside Geofence", "");
             }
              if($geofencePerm==5 || $geofencePerm==13||$geofencePerm==7 || $geofencePerm==15){
                $query= $this->db->query("Select email from admin_login where OrganizationId=$orgid and status=1");
                foreach($query->result() as $row){
                 $email= $row->email;

             $message = '<html>
                    <head>
                    <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
                    <meta name=Generator content="Microsoft Word 12 (filtered)">
                    <style>
                    </style>

                    </head>

                    <body lang=EN-US link=blue vlink=purple>

                    <hr>
                    <br>
                    '.$name.' has punched Time outside Geofence
                    </br>
                    </hr>


                    </body>

                    </html>
                    ';
                    $headers = '';
                    $subject = "Outside Geofence(".$date.")";
                    //Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   //sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
             }
	
        $reportNotificationSent = 0;
	
        $deviceverificationperm=0;
        $flag = '0';
        if ($act == 'TimeIn')
        {
            $FakeLocationStatusTimeIn = $FakeLocationStatus;
        }
        else
        {
            $FakeLocationStatusTimeOut = $FakeLocationStatus;
        }
        $dept = getDepartmentIdByEmpID($userid);
        $desg = getDesignationIdByEmpID($userid);
        $hourltRate = getHourlyRateIdByEmpID($userid);

        $reportNotificationSent = 0;
        $personid = "";
        $persistedfaceid = "0";

        
        $sql7 = "select PersonGroupId from licence_ubiattendance where OrganizationId = $orgid and Addon_FaceRecognition='1'";
        $query7 = $this
            ->db
            ->query($sql7);
        if ($row7 = $query7->row())
        {
            $persongroup_id = $row7->PersonGroupId;
            $flag = '1';

        }

        if ($flag == '1')
        {
            $sql4 = "select PersonId,FirstName,LastName from  EmployeeMaster where Id=$userid";
            $query4 = $this
                ->db
                ->query($sql4);
            if ($row4 = $query4->row())
            {
                $personid = $row4->PersonId;
                $firstname = $row4->FirstName;
                //$lastname = $row4->LastName;
                
            }

            if ($personid == "")
            {
                $personid = create_person($persongroup_id, $firstname);
                $sql5 = "update EmployeeMaster set PersonId = '$personid' where Id=$userid";
                $query5 = $this
                    ->db
                    ->query($sql5);

            }
        }

        $sql6= "select ssdisapp_sts from admin_login where OrganizationId = $orgid";
        $query6=$this->db->query($sql6);
        if ($row6 = $query6->row()) {
                     $ssdisapp_sts = $row6->ssdisapp_sts;
                      
                     }


        if ($shiftId == 0) $shiftId = getShiftIdByEmpID($userid);
        ////////---------------checking and marking "timeOff" stop (if exist)
        

////////---------------checking and marking "timeOff" stop (if exist)
        AutoTimeOffEnd($userid, $orgid, $time, $date, $stamp, $addr, $latit, $longi); // auto timeOff end
        //AutoVisitOutEnd($userid, $orgid, $time, $addr, $latit, $longi);
        /////////// This query is from auto visit out/////////////
        $query=$this->db->query("update `checkin_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00'",array('Visit out not punched',$userid));
        /////////// This query is from auto visit out/////////////

        $today = date('Y-m-d');

        ////////---------------checking and marking "timeOff" stop (if exist)--/end
        $count = 0;
        $orgname = "";
        $orgnameForNoti = "";
        $errorMsg = "";
        $successMsg = "";
        $status = 0;
        $resCode = 0;
        $serversts = 1;
        $sto = '00:00:00';
        $sti = '00:00:00';
        $shifttype = '';
        $data = array();
        $data['msg'] = 'Mark visit under process';
        $data['res'] = 0;
        $attImage = 0;
        $new_name = "https://ubitech.ubihrm.com/public/avatars/male.png";
        $attImage = getAttImageStatus($orgid);
        $img123    = isset($_FILES['file']) ? true : false;
		//$tempimagestatus =isset($_REQUEST['tempimagestatus'])?false:true;
		if($attImage){ // true, image must be uploaded. false, optional image
            $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
           if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name)){
           Trace('image not uploaded--'.$userid);
           $result['status']=3;
           $result['errorMsg']='Error in moving the image. Try later.';
           $result['successMsg'] = '';
           echo json_encode($result);
           return;
           }	
           $new_name =IMGURL.$new_name;
       }
        /*
        if ($attImage)
        {
            $new_name = $userid . '_' . date('dmY_His') . ".jpg";
            if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name))
            {
                Trace('image not uploaded--' . $userid);
                $result['status'] = 3;
                $result['errorMsg'] = 'Error in moving the image. Try later.';
                $result['successMsg'] = '';
                echo json_encode($result);
                return;
            }
            $new_name = IMGURL . $new_name;
        }
        */
        // Go ahead if image is optional or image uploaded successfully
        

        //   $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
        /*    if (move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name))
        
        //if(true)
            {*/
        $sql = '';
        //////----------------getting shift info
        $stype = 0;
        $sql1 = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;

        try
        {
            $result1 = $this
                ->db
                ->query($sql1);
            if ($row1 = $result1->row())
            {
                $stype = $row1->stype;
                $sti = $row1->TimeIn;
                $sto = $row1->TimeOut;
                $shifttype = $row1->shifttype;
            }
        }
        catch(Exception $e)
        {
            Trace('Error_3: ' . $e->getMessage());
        }
        if ($shifttype == 2 && $act == 'TimeIn')
        { // multi date shift case
            if ($time < $sto)
            { // time in should mark in last day date
                try
                {
                    $ldate = date("Y-m-d", strtotime("-1 days"));
                    $sql = "select Id as ttl from AttendanceMaster where AttendanceDate='$ldate' and EmployeeId=$userid";
                    $res = $this
                        ->db
                        ->query($sql);
                    if ($res->num_rows() > 0)
                    { // if attn already marked in previous date
                        $date = date("Y-m-d");
                    }
                    else $date = date("Y-m-d", strtotime("-1 days"));

                }
                catch(Exception $e)
                {

                }
            }
            //else  time in should mark in current day's date
            
        }
        else if ($shifttype == 2 && $act == 'TimeOut')
        {
            if ($time < $sti)
            { // time in should mark in last day date
                try
                {

                    $date = date("Y-m-d", strtotime("-1 days"));
                }
                catch(Exception $e)
                {

                }
            }
        }

        //	echo $date;
        //	return false;
        //////----------------/gettign shift info
        Trace($act . ' AID' . $aid . 'UserId' . $userid);
        if ($aid == 0 && $act == 'TimeOut')
        {
            $sqlId = "select Id from  AttendanceMaster where EmployeeId=$userid and TimeOut='00:00:00' Order by AttendanceDate desc Limit 1";
            $resId = $this
                ->db
                ->query($sqlId);
            if ($rowId = $resId->row())
            {
                $aid = $rowId->Id;
            }
            Trace('After Fetch: ' . $act . ' AID' . $aid . 'UserId' . $userid);
        }
        /*********
        EmployeeMaster
        ***********/
        if ($aid != 0 && $act != 'TimeIn') //////////////updating path of employee profile picture in database/////////////
        
        {

            if ($stype < 0)
            { //// if shift is end whthin same date
                /*face recognition code starts here */
                if ($flag == '1')
                {
                    $sql6 = "select PersistedFaceId from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                    if ($row6 = $query6->row())
                    {

                        if ($row6->PersistedFaceId == '0')
                        {
                            $fid = getfaceid($new_name);
                            if ($fid == '0')
                            {
                                $result['facerecog'] = '5';
                                $this
                                    ->db
                                    ->close();
                                echo json_encode($result);
                                return;

                            }
                            $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                            $sql6 = "update Persisted_Face set PersistedFaceId = '$persistedfaceid',profileimage='$new_name' where EmployeeId=$userid";
                            $query6 = $this
                                ->db
                                ->query($sql6);
                            persongrouptrain($persongroup_id);

                        }
                        //face id will be generated here
                        $faceid = getfaceid($new_name);
                        if ($faceid == '0')
                        {
                            $suspicioustimeout_status = '1';
                            if($ssdisapp_sts=='0'){
                              $attendance_sts=2;
                              }
                        }
                        else
                        {
                            //face verification will take place over here
                            $timeout_confidence = face_verify($faceid, $personid, $persongroup_id);
                            if ($timeout_confidence < '0.75') 
                                { $suspicioustimeout_status = '1';
                            if($ssdisapp_sts=='0'){
                              $attendance_sts=2;
                              }
                            }
                        }
                    }
                    else
                    { //face will be added here
                        $fid = getfaceid($new_name);
                        if ($fid == '0')
                        {
                            $result['facerecog'] = '5';
                            $this
                                ->db
                                ->close();
                            echo json_encode($result);
                            return;

                        }
                        $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                        $sql6 = "insert into Persisted_Face(PersonId,	PersistedFaceId,EmployeeId,profileimage) values ('$personid','$persistedfaceid',$userid,'$new_name')";
                        $query6 = $this
                            ->db
                            ->query($sql6);
                        $faceid = $persistedfaceid;
                        persongrouptrain($persongroup_id);
                    }
                     $sql6 = "select profileimage from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                        if ($row6 = $query6->row())
                    {
                        $profileimage=$row6->profileimage;
                    }
                }
                /*face recognition code ends here */

                $sql = "UPDATE `AttendanceMaster` SET  `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time',`SuspiciousDeviceTimeOutStatus`='$suspiciousdevice',`TimeOutDeviceName`='$devicenamebrand',`TimeOutDeviceId`='$deviceidmobile',TimeOutFaceId='$faceid',TimeOutConfidence='$timeout_confidence',SuspiciousTimeOutStatus='$suspicioustimeout_status',`AttendanceStatus`=$attendance_sts,timeoutcity='$city',PersistedFaceTimeOut='$profileimage', LastModifiedDate='$stamp',overtime =(SELECT subtime(TIMEDIFF ( CONCAT('$date', ' ','$time'),CONCAT(AttendanceDate , '  ', timein)),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$currDate',TimeOutAppVersion='$appVersion',TimeOutGeoFence='$geofence' WHERE id=$aid and `EmployeeId`=$userid   and TimeOut='00:00:00'"; //and SUBTIME(  `TimeOut` ,  `TimeIn` ) >'00:05:00'";

                 if($suspicioustimeout_status=='1'){
                    
                    if($SuspiciousSelfiePerm==9|| $SuspiciousSelfiePerm==13||$SuspiciousSelfiePerm==11|| $SuspiciousSelfiePerm==15){
                sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name's Attendance Selfie does not match", "");
             }
              if($SuspiciousSelfiePerm==5 || $SuspiciousSelfiePerm==13|| $SuspiciousSelfiePerm==7|| $SuspiciousSelfiePerm==15){
                $query= $this->db->query("Select email from admin_login where OrganizationId=$orgid and status=1");
                foreach($query->result() as $row){
                 $email= $row->email;

             $message = '<html>
                    <head>
                    <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
                    <meta name=Generator content="Microsoft Word 12 (filtered)">
                    <style>
                    </style>

                    </head>

                    <body lang=EN-US link=blue vlink=purple>

                    <hr>
                    <br>
                    '.$name."'s".' Attendance Selfie does not match with the Face ID
                    </br>
                    </hr>


                    </body>

                    </html>
                    ';
                    $headers = '';
                    $subject = "Suspicious Selfie(".$date.")";
                    //Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
                }
                
            }
            else
            {
                //////getting timein information
                $sql = "select Timein as timein_time, Attendancedate as timein_date from AttendanceMaster where Id=" . $aid;
                $timein_date = '';
                $timein_time = '';
                $res = $this
                    ->db
                    ->query($sql);
                if ($r = $res->result())
                {
                    $timein_date = $r[0]->timein_date;
                    $timein_time = $r[0]->timein_time;
                }
                //////getting timein information/
                /*	echo $timein_date.' '.$timein_time;
                echo '---';
                echo $date.' '.$time;
                echo '***';
                */
                // shift hours
                $shiftHours = '';
                $sql = "select subtime('24:00:00',subtime(timein,timeout)) as shiftHours from ShiftMaster where Id=$shiftId";
                //$sql="select subtime('30:00:00','21:00:00') as shiftHours from ShiftMaster where id=$shiftId";
                $res = $this
                    ->db
                    ->query($sql);
                if ($r = $res->result()) $shiftHours = $r[0]->shiftHours;

                // time spent
                //		echo $timein_date.' '.$timein_time.'-------';
                //		echo $date.' '.$time.'-------';
                $start = date_create($timein_date . ' ' . $timein_time);
                $end = date_create($date . ' ' . $time);
                $diff = date_diff($end, $start);
                $hrs = 0;
                if ($diff->d == 1) // if shift is running more than 24 hrs
                $hrs = 24;
                $timeSpent = str_pad($hrs + $diff->h, 2, "0", STR_PAD_LEFT) . ':' . str_pad($diff->i, 2, "0", STR_PAD_LEFT) . ':00';

                //echo 'TimeSpent:'.$timeSpent;
                //echo 'shiftHours:'.$shiftHours;
                /*face recognition code starts here */
                if ($flag == '1')
                {
                    $sql6 = "select PersistedFaceId from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                    if ($row6 = $query6->row())
                    {

                        if ($row6->PersistedFaceId == '0')
                        {
                            $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                            $sql6 = "update Persisted_Face set PersistedFaceId = '$persistedfaceid',profileimage='$new_name' where EmployeeId=$userid";
                            $query6 = $this
                                ->db
                                ->query($sql6);
                            persongrouptrain($persongroup_id);

                        }
                        //face id will be generated here
                        $faceid = getfaceid($new_name);
                        if ($faceid == '0')
                        {
                            $suspicioustimeout_status = '1';
                            if($ssdisapp_sts=='0'){
                              $attendance_sts=2;
                              }
                        }
                        else
                        {
                            //face verification will take place over here
                            $timeout_confidence = face_verify($faceid, $personid, $persongroup_id);
                            if ($timeout_confidence < '0.75') { $suspicioustimeout_status = '1';
                        if($ssdisapp_sts=='0'){
                        $attendance_sts=2;
                     }
                 }
                        }
                    }
                    else
                    { //face will be added here
                        $fid = getfaceid($new_name);
                        if ($fid == '0')
                        {
                            $result['facerecog'] = '5';
                            $this
                                ->db
                                ->close();
                            echo json_encode($result);
                            return;

                        }
                        $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                        $sql6 = "insert into Persisted_Face(PersonId,	PersistedFaceId,EmployeeId,profileimage) values ('$personid','$persistedfaceid',$userid,'$new_name')";
                        $query6 = $this
                            ->db
                            ->query($sql6);
                        $faceid = $persistedfaceid;
                        persongrouptrain($persongroup_id);
                    }
                    $sql6 = "select profileimage from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                        if ($row6 = $query6->row())
                    {
                        $profileimage=$row6->profileimage;
                    }
                }
                /*face recognition code ends here */

                $sql = "UPDATE `AttendanceMaster` SET `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time',`SuspiciousDeviceTimeOutStatus`='$suspiciousdevice',`TimeOutDeviceName`='$devicenamebrand',`TimeOutDeviceId`='$deviceidmobile',TimeOutFaceId='$faceid',timeoutcity='$city',TimeOutConfidence='$timeout_confidence',SuspiciousTimeOutStatus='$suspicioustimeout_status',PersistedFaceTimeOut='$profileimage',`AttendanceStatus`=$attendance_sts, LastModifiedDate='$stamp' ,overtime = subtime('$timeSpent','$shiftHours'),timeoutdate='$currDate',TimeOutAppVersion='$appVersion',TimeOutGeoFence='$geofence'
                WHERE id=$aid and `EmployeeId`=$userid and TimeOut='00:00:00' ORDER BY `AttendanceDate` DESC LIMIT 1";
                //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)

                 if($suspicioustimeout_status=='1'){
                    
                    if($SuspiciousSelfiePerm==9|| $SuspiciousSelfiePerm==13||$SuspiciousSelfiePerm==11|| $SuspiciousSelfiePerm==15){
                sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name's Attendance Selfie does not match", "");
             }
              if($SuspiciousSelfiePerm==5 || $SuspiciousSelfiePerm==13|| $SuspiciousSelfiePerm==7|| $SuspiciousSelfiePerm==15){
                $query= $this->db->query("Select email from admin_login where OrganizationId=$orgid and status=1");
                foreach($query->result() as $row){
                 $email= $row->email;

             $message = '<html>
                    <head>
                    <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
                    <meta name=Generator content="Microsoft Word 12 (filtered)">
                    <style>
                    </style>

                    </head>

                    <body lang=EN-US link=blue vlink=purple>

                    <hr>
                    <br>
                    '.$name."'s".' Attendance Selfie does not match with the Face ID
                    </br>
                    </hr>


                    </body>

                    </html>
                    ';
                    $headers = '';
                    $subject = "Suspicious Selfie(".$date.")";
                    //Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   // sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
                }
                
            }
            /*   $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp' ,overtime =(SELECT subtime(subtime('$time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$date'
                WHERE id=$aid and `EmployeeId`=$userid  ORDER BY `AttendanceDate` DESC LIMIT 1";*/
            //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
            //----------push check code
            try
            {
                $push = "push/";
                if (!file_exists($push)) mkdir($push, 0777, true);
                $filename = $push . $orgid . ".log";
                $fp = fopen($filename, "a+");
                fclose($fp);
            }
            catch(Exception $e)
            {
                echo $e->getMessage();
            }
            //----------push check code
            
        } //LastModifiedDate
        else
        {
            ///-------- code for prevent duplicacy in a same day   code-001
            $sql = "select * from  AttendanceMaster where EmployeeId=$userid and AttendanceDate= '$today'";

            try
            {
                $result1 = $this
                    ->db
                    ->query($sql);
                if ($this
                    ->db
                    ->affected_rows() < 1)
                { ///////code-001 (ends)
                    $area = getAreaId($userid);
                   // if ($orgid == '10932') { // only for welspun
                        $area = getNearLocationOfEmp($latit, $longi, $userid);
                   // }

                    /*face recognition code starts here */
                    if ($flag == '1')
                    {
                        $sql6 = "select PersistedFaceId from Persisted_Face where EmployeeId = $userid";
                        $query6 = $this
                            ->db
                            ->query($sql6);
                        if ($row6 = $query6->row())
                        {

                            if ($row6->PersistedFaceId == '0')
                            {
                                $fid = getfaceid($new_name);
                                if ($fid == '0')
                                {
                                    $result['facerecog'] = '5';
                                    $this
                                        ->db
                                        ->close();
                                    echo json_encode($result);
                                    return;

                                }
                                $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                                $sql6 = "update Persisted_Face set PersistedFaceId = '$persistedfaceid' ,profileimage='$new_name' where EmployeeId=$userid";
                                $query6 = $this
                                    ->db
                                    ->query($sql6);
                                persongrouptrain($persongroup_id);

                            }
                            //face id will be generated here
                            $faceid = getfaceid($new_name);
                            if ($faceid == '0')
                            {
                                $suspicioustimein_status = '1';
                                if($ssdisapp_sts=='0'){
                              $attendance_sts=2;
                              }

                            }
                            else
                            {
                                //face verification will take place over here
                                $timein_confidence = face_verify($faceid, $personid, $persongroup_id);
                                if ($timein_confidence < '0.75') { $suspicioustimein_status = '1';
                        if($ssdisapp_sts=='0'){
                        $attendance_sts=2;
                     }
                 }
                            }

                        }
                        else
                        { //face will be added here
                            $fid = getfaceid($new_name);
                            if ($fid == '0')
                            {
                                $result['facerecog'] = '5';
                                $this
                                    ->db
                                    ->close();
                                echo json_encode($result);
                                return;

                            }
                            $persistedfaceid = add_face($persongroup_id, $personid, $new_name);
                            $sql6 = "insert into Persisted_Face(PersonId,	PersistedFaceId,EmployeeId,profileimage) values ('$personid','$persistedfaceid',$userid,'$new_name')";
                            $query6 = $this
                                ->db
                                ->query($sql6);
                            persongrouptrain($persongroup_id);
                            $faceid = $persistedfaceid;
                            if ($persistedfaceid == '0')
                            {
                                $suspicioustimein_status = '1';
                            }
                        }
                        $sql6 = "select profileimage from Persisted_Face where EmployeeId = $userid";
                    $query6 = $this
                        ->db
                        ->query($sql6);
                        if ($row6 = $query6->row())
                    {
                        $profileimage=$row6->profileimage;
                    }
                    }
                    /*face recognition code ends here */

                    $sql = "INSERT INTO `AttendanceMaster`(`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`,`EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`,`ShiftId`,Dept_id,Desg_id,areaId,HourlyRateId, `OrganizationId`,
      `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `Overtime`, `EntryImage`, `checkInLoc`,`device`,latit_in,longi_in,timeindate,Platform,`TimeInDeviceName`,`TimeInDeviceId`,`SuspiciousDeviceTimeInStatus`,TimeInFaceId,SuspiciousTimeInStatus,TimeInConfidence,PersistedFaceTimeIn,timeincity,TimeInAppVersion,TimeInGeoFence)
      VALUES ($FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,$userid,'$date',$attendance_sts,'$time',$shiftId,$dept,$desg,$area,$hourltRate,$orgid,'$date',$userid,'$stamp',$userid,$userid,'00:00:00','" . $new_name . "','$addr','mobile','$latit','$longi','$today',' $platform','$devicenamebrand','$deviceidmobile', '$suspiciousdevice','$faceid','$suspicioustimein_status','$timein_confidence','$profileimage','$city','$appVersion','$geofence')"; 
                    Trace('User Attendance: ' . $userid . ' ' . $sql); 
                     if($suspicioustimein_status=='1'){
                    if($SuspiciousSelfiePerm==9|| $SuspiciousSelfiePerm==13||$SuspiciousSelfiePerm==11|| $SuspiciousSelfiePerm==15){
                sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name's Attendance Selfie does not match", "");
             }
              if($SuspiciousSelfiePerm==5 || $SuspiciousSelfiePerm==13||$SuspiciousSelfiePerm==7|| $SuspiciousSelfiePerm==15){
                $query= $this->db->query("Select email from admin_login where OrganizationId=$orgid and status=1");
                foreach($query->result() as $row){
                 $email= $row->email;

             $message = '<html>
                    <head>
                    <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
                    <meta name=Generator content="Microsoft Word 12 (filtered)">
                    <style>
                    </style>

                    </head>

                    <body lang=EN-US link=blue vlink=purple>

                    <hr>
                    <br>
                    '.$name."'s".' Attendance Selfie does not match with the Face ID
                    </br>
                    </hr>


                    </body>

                    </html>
                    ';
                    $headers = '';
                    $subject = "Suspicious Selfie(".$date.")";
                    //Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   //sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
                }

                }
                else $sql = '';
            }
            catch(Exception $e)
            {
                Trace('Error_2: ' . $e->getMessage());
                $errorMsg = 'Message: ' . $e->getMessage();
                $status = 0;
            }
        }
        try
        {
            $query = $this
                ->db
                ->query($sql);
            if ($this
                ->db
                ->affected_rows() > 0 && $act == 'TimeIn')
            {
                //----------push check code
                try
                {
                    $push = "push/";
                    if (!file_exists($push)) mkdir($push, 0777, true);
                    $filename = $push . $orgid . ".log";
                    $fp = fopen($filename, "a+"); 
                    fclose($fp);
                }
                catch(Exception $e)
                {
                    echo $e->getMessage();
                }
                //----------push check code
                $resCode = 0;
                $status = 1; // update successfully
                $successMsg = "Image uploaded successfully.";
                //////////////////----------------mail send if attndnce is marked very first time in org ever
                $sql = "SELECT  `Email`,ReportNotificationSent,Name  FROM `Organization` WHERE `Id`=" . $orgid;
                $to = '';
                $query1 = $this
                    ->db
                    ->query($sql);
                if ($row = $query1->result())
                {
                    $to = $row[0]->Email;
                    $reportNotificationSent = $row[0]->ReportNotificationSent;
                    $orgname = $row[0]->Name;

                }

                //////////////////----------------/mail send if attndnce is marked very first time in org ever
                
            }
            else
            {
                $status = 2; // no changes found
                $errorMsg .= "Failed to upload Image/No Check In found today.";
            }
        }
        catch(Exception $e)
        {
            Trace('Error_1: ' . $e->getMessage());
            $errorMsg = 'Message: ' . $e->getMessage();
            $status = 0;
        }
        /*  } else {
            Trace('image not uploaded--');
            $status   = 3; // error in uploading image
            $errorMsg = 'Message: error in uploading image';
        }*/

        //emp
        $result['status'] = $status;
        $result['successMsg'] = $successMsg;
        $result['errorMsg'] = $errorMsg;
        //$result['location']=$addr;
        /***    Logic for sending first time in  push notification of employee to admin  ****/
        $EmployeeName = '';
        if ($reportNotificationSent == 0)
        {
            $query1 = $this
                ->db
                ->query("SELECT count(*) as count FROM `AttendanceMaster` as A inner join UserMaster as U where A.OrganizationId=$orgid and A.EmployeeId=U.EmployeeId and U.appSuperviserSts=0 ");
            if ($row = $query1->result())
            {
                $count = $row[0]->count;
                if ($count == 1)
                {
                    $sqlId = "select FirstName from  EmployeeMaster where Id=$userid";
                    $resId = $this
                        ->db
                        ->query($sqlId);
                    if ($rowId = $resId->row())
                    {
                        $EmployeeName = $rowId->FirstName;
                    }
                    $orgnameForNoti = ucwords($orgname);
                    $orgnameForNoti = preg_replace("/[^a-zA-Z]+/", "", $orgnameForNoti);
                    $orgnameForNoti = str_replace(".", "", $orgnameForNoti . $orgid);
                    sendManualPushNotification("('$orgnameForNoti' in topics) && ('admin' in topics) ", "Bingo! $EmployeeName has punched Time in.", "You can check his Attendance");
                    $this
                        ->db
                        ->query("update Organization set ReportNotificationSent=1 where Id=$orgid");
                }

            }
        }
        /***    Logic for sending first time in push notification of employee to admin   ****/
        $this
            ->db
            ->close();
        echo json_encode($result);
    }

  public function saveImage()
    {
        $userid  = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $addr    = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $aid     = isset($_REQUEST['aid']) ? $_REQUEST['aid'] : 0;
        $act     = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'TimeIn';
        $shiftId = isset($_REQUEST['shiftid']) ? $_REQUEST['shiftid'] : 0;
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $platform   = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        $FakeLocationStatus= isset($_REQUEST['FakeLocationStatus']) ? $_REQUEST['FakeLocationStatus'] : 0;
        $deviceidmobile  = isset($_REQUEST['deviceidmobile']) ? $_REQUEST['deviceidmobile'] : '';
        $devicenamebrand  = isset($_REQUEST['devicenamebrand']) ? $_REQUEST['devicenamebrand'] : '';
        $city   = isset($_REQUEST['city']) ? $_REQUEST['city'] : '';

        $appVersion   = isset($_REQUEST['appVersion']) ? $_REQUEST['appVersion'] : '';
        $geofence   = isset($_REQUEST['geofence']) ? $_REQUEST['geofence'] : '';

        $orgTopic   = isset($_REQUEST['globalOrgTopic']) ? $_REQUEST['globalOrgTopic'] : '';
        //$name   = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'User';
        $geofencePerm=getNotificationPermission($orgid,'OutsideGeofence');
        $SuspiciousDevicePerm=getNotificationPermission($orgid,'SuspiciousDevice');
        $name=getEmpName($userid);
        $zone    = getEmpTimeZone($userid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $today   = date("Y-m-d");
        $currDate= date("Y-m-d");
        $time   = date("H:i")=="00:00"?"00:01":date("H:i");       

        $verifieddevice='';
        $FakeLocationStatusTimeIn=0;
        $FakeLocationStatusTimeOut=0;
        $faceid="";
        //$personid='bf159541-1e75-4cbd-acca-bcb8f4e07b3a';
        $persongroup_id="";
        $suspicioustimein_status="0";
        $suspicioustimeout_status="0";
        $timein_confidence="";
        $timeout_confidence=""; 
        $flag='0';
        if($act=='TimeIn'){
            $FakeLocationStatusTimeIn=$FakeLocationStatus;
        }
        else{
            $FakeLocationStatusTimeOut=$FakeLocationStatus;
        }
		$dept=getDepartmentIdByEmpID($userid);
		$desg=getDesignationIdByEmpID($userid);
		$hourltRate=getHourlyRateIdByEmpID($userid);
		
		$orgides = array(68313,68312,68257,68256,68255,68254);
		if(in_array($orgid , $orgides))
		{
			$this->saveGeolocation_auto($userid,$orgid);
		}
	
		$reportNotificationSent=0;

		$suspiciousdevice=0;
		$deviceverificationperm=0;
		
		//exit();
        
		$sql= "select Addon_DeviceVerification from licence_ubiattendance where OrganizationId = $orgid";
		$query=$this->db->query($sql);
		if ($row = $query->row()) {
                     $deviceverificationperm = $row->Addon_DeviceVerification;
                      
                     }

         if($deviceverificationperm==1){            

		$sql= "select DeviceId from EmployeeMaster where Id = $userid";
		$query=$this->db->query($sql);
		 if ($row = $query->row()) {
                     $verifieddevice = $row->DeviceId;
                      if($verifieddevice==$deviceidmobile)
                 {
                 	$suspiciousdevice=0;
                 }
                 else
                 {
                    $suspiciousdevice=1;
                    if($SuspiciousDevicePerm==9|| $SuspiciousDevicePerm==13||$SuspiciousDevicePerm==11|| $SuspiciousDevicePerm==15){
                sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name's Attendance Device does not match", "");
             }
              if($SuspiciousDevicePerm==5 || $SuspiciousDevicePerm==13|| $SuspiciousDevicePerm==7|| $SuspiciousDevicePerm==15){
                $query= $this->db->query("Select email from admin_login where OrganizationId=$orgid and status=1");
                foreach($query->result() as $row){
                 $email= $row->email;

             $message = '<html>
                    <head>
                    <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
                    <meta name=Generator content="Microsoft Word 12 (filtered)">
                    <style>
                    </style>

                    </head>

                    <body lang=EN-US link=blue vlink=purple>

                    <hr>
                    <br>
                    '.$name."'s".' Attendance Device is different from their registered Device ID
                    </br>
                    </hr>


                    </body>

                    </html>
                    ';
                    $headers = '';
                    $subject = "Suspicious Device(".$date.")";
                    //Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   //sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
                 }
                     
                  
                 }
             }

             if($geofence=="Outside Geofence"){
                if($geofencePerm==9|| $geofencePerm==13||$geofencePerm==11|| $geofencePerm==15){
                sendManualPushNotification("('$orgTopic' in topics) && ('admin' in topics)", "$name has punched Attendance outside Geofence", "");
             }
              if($geofencePerm==5 || $geofencePerm==13||$geofencePerm==7|| $geofencePerm==15){
                $query= $this->db->query("Select email from admin_login where OrganizationId=$orgid and status=1");
                foreach($query->result() as $row){
                 $email= $row->email;

             $message = '<html>
                    <head>
                    <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
                    <meta name=Generator content="Microsoft Word 12 (filtered)">
                    <style>
                    </style>

                    </head>

                    <body lang=EN-US link=blue vlink=purple>

                    <hr>
                    <br>
                    '.$name.' has punched Time outside Geofence
                    </br>
                    </hr>


                    </body>

                    </html>
                    ';
                    $headers = '';
                    $subject = "Outside Geofence(".$date.")";
                    //Trace(" empid-".$emp_id." orgid-".$orgid." email=".$email."  Message body- ".$message);
                   sendEmail_new($email, $subject, $message, $headers);
                   //sendEmail_new('nitin@ubitechsolutions.com', $subject, $message, $headers);
                   //sendEmail_new('shashank@ubitechsolutions.com', $subject, $message, $headers);
                }
               }
             }

		

         
		
		$insert_updateid = (int)$aid;
		
		if($shiftId==0)
			$shiftId=getShiftIdByEmpID($userid);
        ////////---------------checking and marking "timeOff" stop (if exist)
        // $zone    = getTimeZone($orgid);
        
		
	AutoTimeOffEnd($userid, $orgid, $time, $date, $stamp, $addr, $latit, $longi); // auto timeOff end
        //AutoVisitOutEnd($userid, $orgid, $time, $addr, $latit, $longi);
        /////////// This query is from auto visit out/////////////
        $query=$this->db->query("update `checkin_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00'",array('Auto Visit Out Punched',$userid));
        /////////// This query is from auto visit out/////////////
        $today  = date('Y-m-d');
       
        ////////---------------checking and marking "timeOff" stop (if exist)--/end
        $count      = 0;
		$orgname="";
		$orgnameForNoti="";
        $errorMsg   = "";
        $successMsg = "";
        $status     = 0;
        $resCode    = 0;
        $serversts  = 1;
		$sto='00:00:00';
		$sti='00:00:00';
		$shifttype='';
		$data=array();
		$data['msg']='Mark visit under process';
		$data['res']=0;
		$attImage=0;
		$new_name   = "https://ubitech.ubihrm.com/public/avatars/male.png";
		$attImage=getAttImageStatus($orgid);
		$img123    = isset($_FILES['file']) ? true : false;
		$tempimagestatus =isset($_REQUEST['tempimagestatus'])?false:true;
		if($attImage){ // true, image must be uploaded. false, optional image
            $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
           if (!move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name)){
           Trace('image not uploaded--'.$userid);
           $result['status']=3;
           $result['errorMsg']='Error in moving the image. Try later.';
           $result['successMsg'] = '';
           echo json_encode($result);
           return;
           }	
           $new_name =IMGURL.$new_name;
       } // Go ahead if image is optional or image uploaded successfully
		
		
     //   $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
    /*    if (move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name))
        
        //if(true)
            {*/
            $sql = '';
			//////----------------getting shift info
            $stype = 0;
            $sql1  = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;
			
            try {
                $result1 = $this->db->query($sql1);
                if ($row1 = $result1->row()) {
                    $stype = $row1->stype;
					$sti=$row1->TimeIn;
					$sto=$row1->TimeOut;
					$shifttype=$row1->shifttype;
                }
            }
            catch (Exception $e) {
                Trace('Error_3: ' . $e->getMessage());
            }
			if($shifttype==2 && $act=='TimeIn'){ // multi date shift case
				if($time<$sto){ // time in should mark in last day date
					try{
						$ldate   = date("Y-m-d",strtotime("-1 days"));
						$sql="select Id as ttl from AttendanceMaster where AttendanceDate='$ldate' and EmployeeId=$userid";
						$res=$this->db->query($sql);
						if($res->num_rows() > 0){// if attn already marked in previous date
							$date   = date("Y-m-d");
						}
						else
							$date   = date("Y-m-d",strtotime("-1 days"));
							
					}catch(Exception $e){
						
					}
				}
				//else  time in should mark in current day's date
            }
            else if($shifttype==2 && $act=='TimeOut'){
                if($time<$sti){ // time in should mark in last day date
					try{
                        
						
							$date   = date("Y-m-d",strtotime("-1 days"));
					}catch(Exception $e){
						
					}
				}
            }
			
		//	echo $date;
		//	return false;
			
            //////----------------/gettign shift info
            Trace($act.' AID'.$aid.'UserId'.$userid);
            if($aid==0 && $act=='TimeOut'){
            	$sqlId = "select Id from  AttendanceMaster where EmployeeId=$userid and TimeOut='00:00:00' Order by AttendanceDate desc Limit 1";
            	$resId=$this->db->query($sqlId);
            	 if ($rowId = $resId->row()) {
                    $aid = $rowId->Id;
                }
                Trace('After Fetch: '.$act.' AID'.$aid.'UserId'.$userid);
            }
            /*********
			EmployeeMaster
			***********/
            if ($aid != 0 && $act!='TimeIn') //////////////updating path of employee profile picture in database/////////////
                {
                if ($stype < 0){ //// if shift is end whthin same date

                	
                     $sql = "UPDATE `AttendanceMaster` SET  `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time',`SuspiciousDeviceTimeOutStatus`='$suspiciousdevice',`TimeOutDeviceName`='$devicenamebrand',`timeoutcity`='$city',`TimeOutDeviceId`='$deviceidmobile', LastModifiedDate='$stamp',overtime =(SELECT subtime(TIMEDIFF ( CONCAT('$date', ' ','$time'),CONCAT(AttendanceDate , '  ', timein)),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$currDate',TimeOutAppVersion='$appVersion',TimeOutGeoFence='$geofence' WHERE id=$aid and `EmployeeId`=$userid   and TimeOut='00:00:00'"; //and SUBTIME(  `TimeOut` ,  `TimeIn` ) >'00:05:00'";
                }
                else{
					//////getting timein information
					$sql="select Timein as timein_time, Attendancedate as timein_date from AttendanceMaster where Id=".$aid;
					$timein_date='';
					$timein_time='';
					$res=$this->db->query($sql);
					if($r= $res->result()){
							$timein_date=$r[0]->timein_date;
							$timein_time=$r[0]->timein_time;
					}
					//////getting timein information/
				/*	echo $timein_date.' '.$timein_time;
					echo '---';
					echo $date.' '.$time;
					echo '***';
					*/
					// shift hours
					$shiftHours='';
					$sql="select subtime('24:00:00',subtime(timein,timeout)) as shiftHours from ShiftMaster where Id=$shiftId";
					//$sql="select subtime('30:00:00','21:00:00') as shiftHours from ShiftMaster where id=$shiftId";
					$res=$this->db->query($sql);
					if($r= $res->result())
						$shiftHours=$r[0]->shiftHours;
					
					// time spent
			//		echo $timein_date.' '.$timein_time.'-------';
			//		echo $date.' '.$time.'-------';
					$start = date_create($timein_date.' '.$timein_time);
					$end = date_create($date.' '.$time);
					$diff=date_diff($end,$start);
					$hrs=0;
					if($diff->d==1)// if shift is running more than 24 hrs
						$hrs=24;
					$timeSpent=str_pad($hrs+ $diff->h, 2, "0", STR_PAD_LEFT).':'.str_pad($diff->i, 2, "0", STR_PAD_LEFT).':00';

					
           
					
					//echo 'TimeSpent:'.$timeSpent;
					//echo 'shiftHours:'.$shiftHours;
                    $sql = "UPDATE `AttendanceMaster` SET `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time',`SuspiciousDeviceTimeOutStatus`='$suspiciousdevice',`TimeOutDeviceName`='$devicenamebrand',`timeoutcity`='$city',`TimeOutDeviceId`='$deviceidmobile', LastModifiedDate='$stamp' ,overtime = subtime('$timeSpent','$shiftHours'),timeoutdate='$currDate',TimeOutAppVersion='$appVersion',TimeOutGeoFence='$geofence'
                WHERE id=$aid and `EmployeeId`=$userid and TimeOut='00:00:00' ORDER BY `AttendanceDate` DESC LIMIT 1";
                //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
				}
                 /*   $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp' ,overtime =(SELECT subtime(subtime('$time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime ),timeoutdate='$date'
                WHERE id=$aid and `EmployeeId`=$userid  ORDER BY `AttendanceDate` DESC LIMIT 1";*/
                //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
                //----------push check code
                try {
                    $push = "push/";
                    if (!file_exists($push))
                        mkdir($push, 0777, true);
                    $filename = $push . $orgid . ".log";
                    $fp       = fopen($filename, "a+");
                    fclose($fp);
                }
                catch (Exception $e) {
                    echo $e->getMessage();
                }
                //----------push check code
            } //LastModifiedDate
            else{
                ///-------- code for prevent duplicacy in a same day   code-001
                $sql = "select * from  AttendanceMaster where EmployeeId=$userid and AttendanceDate= '$today'";
                
                try {
                    $result1 = $this->db->query($sql);
                    if ($this->db->affected_rows() < 1) { ///////code-001 (ends)
                        $area = getAreaId($userid);
					   //if($orgid=='10932'){      // only for welspun
                        	$area = getNearLocationOfEmp($latit, $longi,$userid);
                       // }

                       
                        $sql  = "INSERT INTO `AttendanceMaster`(`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`,`EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`,`ShiftId`,Dept_id,Desg_id,areaId,HourlyRateId, `OrganizationId`,
      `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `Overtime`, `EntryImage`, `checkInLoc`,`device`,latit_in,longi_in,timeindate,Platform,`TimeInDeviceName`,`TimeInDeviceId`,`SuspiciousDeviceTimeInStatus`, `timeincity`,TimeInAppVersion,TimeInGeoFence)
      VALUES ($FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,$userid,'$date',1,'$time',$shiftId,$dept,$desg,$area,$hourltRate,$orgid,'$date',$userid,'$stamp',$userid,$userid,'00:00:00','" . $new_name . "','$addr','mobile','$latit','$longi','$today',' $platform','$devicenamebrand','$deviceidmobile', '$suspiciousdevice','$city','$appVersion','$geofence')";
      					Trace('User Attendance: '.$userid.' '.$sql);
                    } else
                        $sql = '';
                }
                catch (Exception $e) {
                    Trace('Error_2: ' . $e->getMessage());
                    $errorMsg = 'Message: ' . $e->getMessage();
                    $status   = 0;
                }                
            }
            try {
                $query = $this->db->query($sql);
                if ($this->db->affected_rows() > 0 && $act == 'TimeIn') {
					$insert_updateid  = $this->db->insert_id();
                    //----------push check code
                    try {
                        $push = "push/";
                        if (!file_exists($push))
                            mkdir($push, 0777, true);
                        $filename = $push . $orgid . ".log";
                        $fp       = fopen($filename, "a+");
                        fclose($fp);
                    }
                    catch (Exception $e) {
                        echo $e->getMessage();
                    }
                    //----------push check code       
                    $resCode    = 0;
                    $status     = 1; // update successfully
                    $successMsg = "Image uploaded successfully.";
                    //////////////////----------------mail send if attndnce is marked very first time in org ever
                    $sql        = "SELECT  `Email`,ReportNotificationSent,Name  FROM `Organization` WHERE `Id`=" . $orgid;
                    $to         = '';
                    $query1     = $this->db->query($sql);
                    if ($row = $query1->result()) {
                        $to = $row[0]->Email;
						$reportNotificationSent=$row[0]->ReportNotificationSent;
						$orgname=$row[0]->Name;
						
                    }
                    
                    //////////////////----------------/mail send if attndnce is marked very first time in org ever
                } else {
                    $status = 2; // no changes found
                    $errorMsg .= "Failed to upload Image/No Check In found today.";
                }
            }
            catch (Exception $e) {
                Trace('Error_1: ' . $e->getMessage());
                $errorMsg = 'Message: ' . $e->getMessage();
                $status   = 0;
            }
      /*  } else {
            Trace('image not uploaded--');
            $status   = 3; // error in uploading image
            $errorMsg = 'Message: error in uploading image';
        }*/
		
		//emp
		
        $result['status']     = $status;
        $result['successMsg'] = $successMsg;
        $result['errorMsg']   = $errorMsg;
		$result['insert_updateid']   = $insert_updateid ;
        //$result['location']=$addr;
         /***    Logic for sending first time in  push notification of employee to admin  ****/
		$EmployeeName='';
		 if($reportNotificationSent==0){
			$query1     = $this->db->query("SELECT count(*) as count FROM `AttendanceMaster` as A inner join UserMaster as U where A.OrganizationId=$orgid and A.EmployeeId=U.EmployeeId and U.appSuperviserSts=0 ");
			if ($row = $query1->result()) {
				$count = $row[0]->count;
				if($count==1){
					$sqlId = "select FirstName from  EmployeeMaster where Id=$userid";
					$resId=$this->db->query($sqlId);
					 if ($rowId = $resId->row()) {
						$EmployeeName = $rowId->FirstName;
					}
					$orgnameForNoti=ucwords($orgname);
					$orgnameForNoti=preg_replace("/[^a-zA-Z]+/", "", $orgnameForNoti);
					$orgnameForNoti=str_replace(".","",$orgnameForNoti.$orgid);
					sendManualPushNotification("('$orgnameForNoti' in topics) && ('admin' in topics) ","Bingo! $EmployeeName has punched Time in.","You can check his Attendance");
					$this->db->query("update Organization set ReportNotificationSent=1 where Id=$orgid");
				}
				
			}
		 }
		 /***    Logic for sending first time in push notification of employee to admin   ****/
		 $this->db->close();
        echo json_encode($result);
    }
	
	
	///////////created by shashank
	  
public function saveImageFromChrome()
    {
        $userid  = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $addr    = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $aid     = isset($_REQUEST['aid']) ? $_REQUEST['aid'] : 0;
        $act     = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'TimeIn';
        $shiftId = isset($_REQUEST['shiftid']) ? $_REQUEST['shiftid'] : 0;
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $data   = isset($_REQUEST['data']) ? $_REQUEST['data'] : '0.0';
        $result  = array();
       // echo "aid from server:".$aid;
        $dept=getDepartmentIdByEmpID($userid);
        $desg=getDesignationIdByEmpID($userid);
        $shiftId=getShiftIdByEmpID($userid);
        //$zone = getTimeZone($orgid);
        $zone    = getEmpTimeZone($userid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $today = date('Y-m-d');
	    $date	=date('Y-m-d');
	    $time   = date("H:i");
	    $stamp   = date('Y-m-d');
        $count      = 0;
        $errorMsg   = "";
        $successMsg = "";
        $status     = 0;
        $resCode    = 0;
        $serversts  = 1;
        $new_name   = $userid . '_' . date('dmY_His') . ".jpg";
    if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
        $data = substr($data, strpos($data, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif
		$types = array('jpg','jpeg','gif','png');
        if (!in_array($type,$types)) {
            throw new \Exception('invalid image type');
        }

        $data = base64_decode($data);

        if ($data === false) {
            throw new \Exception('base64_decode failed');
        }
    } else {
        throw new \Exception('did not match data URI with image data');
    }
  



        
        //if (move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/" . $new_name))
        //if(true)
        
        
			if(LOCATION=='online') 
			{
			
			 /*$result_save= S3::putObject($data, 'ubiattendanceimages', 'attendance_images/'.$new_name, S3::ACL_PUBLIC_READ);	
			if (!$result_save){
			Trace('image not uploaded--'.$userid);
			$result['status']=3;
			$result['errorMsg']='Error in moving the image, try later.';
			$result['successMsg'] = '';
			echo json_encode($result);
			return;
			}	
			//correctImageOrientation("uploads/" . $new_name);
			$new_name = IMGPATH.'attendance_images/'.$new_name;*/
			 
			 if (!file_put_contents("tempimage/" . $new_name, $data)){
			         Trace('image not uploaded--'.$userid);
			         $result['status']=3;
			         $result['errorMsg']='Error in moving the image, try later.';
			          $result['successMsg'] = '';
			          echo json_encode($result);
			          return; 
			      }
				  $file = TEMPIMAGE.$new_name;
				  exec("aws s3 mv $file s3://ubiattendanceimages/attendance_images/");
				  $new_name= IMGPATH.'attendance_images/'.$new_name;
			
			}
			else
			{
			   if (!file_put_contents("uploads/" . $new_name, $data)){
			   Trace('image not uploaded--'.$userid);
			   $result['status']=3;
			   $result['errorMsg']='Error in moving the image, try later.';
			   $result['successMsg'] = '';
			   echo json_encode($result);
			   return;
			   }	
			//correctImageOrientation("uploads/" . $new_name);
			   $new_name =IMGURL.$new_name;
			}
        
        
        
        
        
        
      
               // echo "inside if";
            $sql = '';
            
            //////----------------getting shift info
            $stype = 0;
            $sql1  = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype FROM ShiftMaster where id=" . $shiftId;
            try {
                $result1 = $this->db->query($sql1);
                if ($row1 = $result1->row()) {
                    $stype = $row1->stype;
                }
            }
            catch (Exception $e) {
                Trace('Error_3: ' . $e->getMessage());
            }
            //////----------------/gettign shift info
            
            if ($aid != 0) //////////////updating path of employee profile picture in database/////////////
                {
                if ($stype < 0) //// if shift is end whthin same date
                    $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp',overtime =(SELECT subtime(subtime('$time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime )
                WHERE id=$aid and `EmployeeId`=$userid  and date(AttendanceDate) = '$date' "; //and SUBTIME(  `TimeOut` ,  `TimeIn` ) >'00:05:00'";
                else
                    $sql = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $new_name . "',CheckOutLoc='$addr',latit_out='$latit',longi_out='$longi', TimeOut='$time', LastModifiedDate='$stamp' ,overtime =(SELECT subtime(subtime('$time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$shiftId)) as overTime )
                WHERE id=$aid and `EmployeeId`=$userid  ORDER BY `AttendanceDate` DESC LIMIT 1";
                //and date(AttendanceDate) = DATE_SUB('$date', INTERVAL 1 DAY)
                //----------push check code
                try {
                    $push = "push/";
                    if (!file_exists($push))
                        mkdir($push, 0777, true);
                    $filename = $push . $orgid . ".log";
                    $fp       = fopen($filename, "a+");
                    fclose($fp);
                }
                catch (Exception $e) {
                    echo $e->getMessage();
                }
                //----------push check code
            } //LastModifiedDate
            else if ($aid == 0) {
                ///-------- code for prevent duplicacy in a same day   code-001
                $sql = "select * from  AttendanceMaster where EmployeeId=$userid and AttendanceDate= '$today'";
                
                try {
                    $result1 = $this->db->query($sql);
                    if ($this->db->affected_rows() < 1) { ///////code-001 (ends)
                        $area = getAreaId($userid);
                        $sql  = "INSERT INTO `AttendanceMaster`(`EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`,`ShiftId`,Dept_id,Desg_id,areaId, `OrganizationId`,
      `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `OwnerId`, `Overtime`, `EntryImage`, `checkInLoc`,`device`,latit_in,longi_in)
      VALUES ($userid,'$date',1,'$time',$shiftId,$dept,$desg,$area,$orgid,'$date',$userid,'$stamp',$userid,$userid,'00:00:00','" .$new_name . "','$addr','mobile','$latit','$longi')";
                    } else
                        $sql = '';
                }
                catch (Exception $e) {
                    Trace('Error_2: ' . $e->getMessage());
                    $errorMsg = 'Message: ' . $e->getMessage();
                    $status   = 0;
                }
            }
            
            try {
                $query = $this->db->query($sql);
				 if ($this->db->affected_rows() > 0) {
                    //----------push check code
                    try {
                        $push = "push/";
                        if (!file_exists($push))
                            mkdir($push, 0777, true);
                        $filename = $push . $orgid . ".log";
                        $fp       = fopen($filename, "a+");
                        fclose($fp);
                    }
                    catch (Exception $e) {
                        echo $e->getMessage();
                    }
                    //----------push check code
                    
                    
                    $resCode    = 0;
                    $status     = 1; // update successfully
                    $successMsg = "Image uploaded successfully.";
				 } else {
                    $status = 2; // no changes found
                    $errorMsg .= "Failed to upload Image/No Check In found today.";
                }
				/*echo '---------------------'.$this->db->affected_rows().'--'.$act.'-------------------';
                if ($this->db->affected_rows() > 0 && $act == 'TimeOut') {
                    //----------push check code
                    try {
                        $push = "push/";
                        if (!file_exists($push))
                            mkdir($push, 0777, true);
                        $filename = $push . $orgid . ".log";
                        $fp       = fopen($filename, "a+");
                        fclose($fp);
                    }
                    catch (Exception $e) {
                        echo $e->getMessage();
                    }
                    //----------push check code
                    
                    
                    $resCode    = 0;
                    $status     = 1; // update successfully
                    $successMsg = "Image uploaded successfully.";
                    //////////////////----------------mail send if attndnce is marked very first time in org ever
                    $sql        = "SELECT  `Email`  FROM `Organization` WHERE `Id`=" . $orgid;
                    $to         = '';
                    $query1     = $this->db->query($sql);
                    if ($row = $query1->result()) {
                        $to = $row[0]->Email;
                    }
                    
                    //////////////////----------------/mail send if attndnce is marked very first time in org ever
                } else {
                    $status = 2; // no changes found
                    $errorMsg .= "Failed to upload Image/No Check In found today.";
                }*/
            }
            catch (Exception $e) {
                Trace('Error_1: ' . $e->getMessage());
                $errorMsg = 'Message: ' . $e->getMessage();
                $status   = 0;
            }
       
        $result['status']     = $status;
        $result['successMsg'] = $successMsg;
        $result['errorMsg']   = $errorMsg;
        //$result['location']=$addr;
        
        echo json_encode($result);
    }

	
	public function backgroundLocationService()
    {
    
   		$latitude = isset($_REQUEST['latitude']) ? $_REQUEST['latitude'] : "";
   		$longitude = isset($_REQUEST['longitude']) ? $_REQUEST['longitude'] : "";
   		$address = isset($_REQUEST['address']) ? $_REQUEST['address'] : "";
   		$empid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : "";
   		$orgid =getOrgIdByEmpId($empid);
   		
   		$areaId = getName('EmployeeMaster','area_assigned','Id',$empid);
   		$lat_lang = getName('Geo_Settings','Lat_Long','Id',$areaId);
		$radius = getName('Geo_Settings','Radius','Id',$areaId);
		$arr1 = explode(",",$lat_lang);
		$isOutOfFence = 0;
		$isOutOfFence1=0;
		
		if(count($arr1)>1){
		
			$a=floatval($arr1[0]);
			$b=floatval($arr1[1]);
			
			$d1 = distance($a, $b, $latitude, $longitude, "K");
			
			if($d1 > $radius){
				$isOutOfFence = 1;
				$isOutOfFence1=1;
			}
		}
		
   		$zone = getTimeZone($orgid);
        date_default_timezone_set($zone);
   		
   		$mdate = date("Y-m-d H:i:s");
   		$date = date("Y-m-d");
   		$time =date("H:i:s");
   		$location_arr = array(
   			'latitude' => $latitude,
   			'longitude' => $longitude,
   			'address' => $address,
   			"time" => $mdate,
   			'isOutSideFence' => $isOutOfFence
   		);
   		
   				$location_json = json_encode($location_arr);
   		
   		     	$sql = "select * from  BackgroundAppLocation where EmployeeId=$empid and date(CreatedDate)= '$date' and OrganizationId=$orgid";
                
                try {
                    $result1 = $this->db->query($sql);
                    if ($this->db->affected_rows() < 1) { 
                    	$sql="INSERT INTO `BackgroundAppLocation`(`CreatedDate`,`LocationJson`, `IsOutSideFence`, `OutSideHours`, `EmployeeId`, `OrganizationId`, `UpdatedDate`) VALUES (?,?,?,?,?,?,?)";
						$query=$this->db->query($sql,array($mdate,$location_json,$isOutOfFence1,"",$empid,$orgid,$mdate));
                    }else{
                    Trace(" Update ");
                    $row=$result1->row();
                    //Trace('Old Date'.$row->UpdatedDate);
                    $odatetime=strtotime(date('H:i',strtotime($row->UpdatedDate)));
                   // Trace('olddatetime: '.$odatetime);
                    //Trace('New Date'.$mdate);
                    $udatetime=strtotime(date('H:i',strtotime($mdate)));
                    //Trace('UpdatedDatetime: '.$udatetime);
                    $diff=($udatetime-$odatetime)/60;
                    //Trace('Difference: '.$diff);
                    //if($diff>5){
                    
                    Trace(" Update Done");
                    $location_json =  $location_json.",";
                    $query = $this->db->query("update BackgroundAppLocation set LocationJson=CONCAT(?,LocationJson),UpdatedDate=? WHERE `EmployeeId`=? and OrganizationId=? and date(CreatedDate)= ? ", array(            
		            $location_json,
        		    $mdate,
            		$empid,
            		$orgid,
            		$date
        			));
        			}
                    //}
                }catch(Exception $e){
                	Trace('Exceptipn occured');
                }
   		

		
		Trace($location_json);
       
    }
    
    
    
	public function getEmplolyeeTrackTime()
    {
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $empid   = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
        
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        
        $date = isset($_REQUEST['date'])?date('Y-m-d',strtotime($_REQUEST['date'])):'';
        
		$query  = $this->db->query("SELECT LocationJson from `BackgroundAppLocation` WHERE EmployeeId=? and date(CreatedDate)= ? and OrganizationId=?",
		array($empid,$date,$orgid));
		 $jsonData       = "";
		foreach ($query->result() as $row1)
							{
							//	$res['totaltime'] = $row1->totaltime;
								
								$jsonData = "[".$row1->LocationJson."]";
								
							}
           
		 
        echo $jsonData;
    }
    
	public function getClientsDDList()
    {
        $orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:0;
        $query = $this->db->query("SELECT `Id`, `Company` as Name, status as archive FROM `ClientMaster`  WHERE OrganizationId=? order by Company", array(
            $orgid
        ));
        echo json_encode($query->result());
    }
	
	public function getAreaStatus(){
		$arr='0';
		
		$empid=isset($_REQUEST['empid'])?$_REQUEST['empid']:0;
		$a=isset($_REQUEST['lat'])?$_REQUEST['lat']:0.0;
		$b=isset($_REQUEST['long'])?$_REQUEST['long']:0.0;
		
		$areaId=getAreaId($empid);
		$areaInfo=json_decode(getAreaInfo($areaId));
			
		if($areaInfo){
		$d1 = calDistance($a,$b,$areaInfo->lat,$areaInfo->long,"K");
							// outside the fenced area
		if($d1 <= $areaInfo->radius)
			$arr='1';					// within the fenced area
		}
		echo json_encode($arr); 
		
	}
	
	
	public function marktimeoff()
	{
	  
	    $userid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
		$timeoffid     = isset($_REQUEST['timeoffid']) ? $_REQUEST['timeoffid'] :0;
        $addr    = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
       
		//$zone    = getTimeZone($orgid);
		$zone    = getEmpTimeZone($userid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
		$stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $time   = date("H:i")=="00:00"?"23:59":date("H:i");
		$data=array();
		$data['msg']='Mark Attendance under process';
		$data['res']=0;

		//$client_name=$cid;//getClientName($cid);
		$new_name   ="https://ubitech.ubihrm.com/public/avatars/male.png";
		
		    $new_name   = $userid . '_' .date('dmY_His') . ".jpg";
			if(LOCATION=='online') 
			{
			 $tmpfile = $_FILES['file']['tmp_name'];
			 $result_save= S3::putObject(S3::inputFile($tmpfile), 'ubiattendanceimages', 'tmeoff_images/'.$new_name, S3::ACL_PUBLIC_READ);	
			if (!$result_save){
			Trace('image not uploaded--'.$userid);
			$data['res']='0';
			$data['msg']='Error in moving the image, try later.';
			echo json_encode($data);
			return;
			}	
			   $new_name = IMGPATH.'timeoff_images/'.$new_name;
			}
			else
			{
			   if(!move_uploaded_file($_FILES["file"]["tmp_name"], "flexi/" . $new_name)){
			   $data['res']='0';
			   $data['msg']='Error in moving the image, try later.';
			   echo json_encode($data);
			   return;
			}	
			
			   $new_name =IMGUR2.$new_name; 
			}
				
		 $query = $this->db->query("Update Timeoff set Timeoff_start = ?,ModifiedDate = ?,timeoffstartimg=?,locationin=?,latin=?,longin=? where id = ? ", array($stamp,$date,$new_name,$addr,$latit,$longi,$timeoffid));	
			if($query>0){
				$data['res']='1';
				$data['msg']=' marked successfully.';
			}else{
				$data['res']='0';
				$data['msg']='Unable to mark Attendance, try later.';
			}
			echo json_encode($data);  
	}
	public function saveFlexi(){
		$userid  = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
		$cid  = isset($_REQUEST['cid']) ? $_REQUEST['cid'] : 0;
        $addr    = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $FakeLocationStatus   = isset($_REQUEST['FakeLocationStatus']) ? $_REQUEST['FakeLocationStatus'] : 0;
       
		//$zone    = getTimeZone($orgid);
		$zone    = getEmpTimeZone($userid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
		$stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $time   = date("H:i")=="00:00"?"23:59":date("H:i");
		$data=array();
		$data['msg']='Mark Attendance under process';
		$data['res']=0;
		//$visitImage=getVisitImageStatus($orgid);
		
		//echo $visitImage;
	//	return false;
		$client_name=$cid;//getClientName($cid);
		$new_name   ="https://ubitech.ubihrm.com/public/avatars/male.png";
		
		    $new_name   = $userid . '_' .date('dmY_His') . ".jpg";
			if(LOCATION=='online') 
			{
			 $tmpfile = $_FILES['file']['tmp_name'];
			 $result_save= S3::putObject(S3::inputFile($tmpfile), 'ubiattendanceimages', 'flexi_images/'.$new_name, S3::ACL_PUBLIC_READ);	
			if (!$result_save){
			Trace('image not uploaded--'.$userid);
			$data['res']='0';
			$data['msg']='Error in moving the image, try later.';
			echo json_encode($data);
			return;
			}	
			//correctImageOrientation("uploads/" . $new_name);
			$new_name = IMGPATH.'flexi_images/'.$new_name;
			}
			else
			{
			   if(!move_uploaded_file($_FILES["file"]["tmp_name"], "flexi/" . $new_name)){
			   $data['res']='0';
			   $data['msg']='Error in moving the image, try later.';
			   echo json_encode($data);
			   return;
			}	
			//correctImageOrientation("uploads/" . $new_name);
			   $new_name =IMGUR2.$new_name;
			}
			
				
		//} // Go ahead if image is optional or image uploaded successfully
		
        
			
			$query=$this->db->query("update `FlexiShift_master` set description=?, `location_out`=location ,`latit_out`=latit,`longi_out`=longi, `time_out`=time,`checkout_img`='',skipped=1 where EmployeeId=? and time_out='00:00:00'",array('Visit out not punched',$userid));
			
			
			$sql="INSERT INTO `FlexiShift_master`(`FakeLocationStatusTimeIn`,`EmployeeId`, `location`, `latit`, `longi`, `time`, `date`, `client_name`, `ClientId`, `OrganizationId`, `checkin_img`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
			$query=$this->db->query($sql,array($FakeLocationStatus,$userid,$addr,$latit,$longi,$time,$date,$client_name,$cid,$orgid,$new_name));
			if($query>0){
				$data['res']='1';
				$data['msg']=' marked successfully.';
			}else{
				$data['res']='0';
				$data['msg']='Unable to mark Attendance, try later.';
			}
			echo json_encode($data);
		
	}
	public function saveFlexiOut(){
		$visit_id  = isset($_REQUEST['visit_id']) ? $_REQUEST['visit_id'] : 0;
		//$remark  = isset($_REQUEST['remark']) ? $_REQUEST['remark'] : 0;
        $latit   = isset($_REQUEST['latit']) ? $_REQUEST['latit'] : '0.0';
        $longi   = isset($_REQUEST['longi']) ? $_REQUEST['longi'] : '0.0';
        $addr   = isset($_REQUEST['addr']) ? $_REQUEST['addr'] : '0.0';
        $orgid   = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : '0.0';
        $empid   = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : '0.0';
        $FakeLocationStatus   = isset($_REQUEST['FakeLocationStatus']) ? $_REQUEST['FakeLocationStatus'] : 0;
       
		//$zone    = getTimeZone($orgid);
		$zone    = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
		$stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $time   = date("H:i")=="00:00"?"23:59":date("H:i");
		$data=array();
		$data['msg']='Mark visit out under process';
		$data['res']=0;
		$new_name   = "https://ubitech.ubihrm.com/public/avatars/male.png";
		$visitImage=0;
		//$visitImage=getVisitImageStatus($orgid);
		//if($visitImage){ // true, image must be uploaded. false, optional image
			$new_name   = $empid. '_' .date('dmY_His') . ".jpg";
			if(LOCATION=='online') 
			{
			 $tmpfile = $_FILES['file']['tmp_name'];
			 $result_save= S3::putObject(S3::inputFile($tmpfile), 'ubiattendanceimages', 'flexi_images/'.$new_name, S3::ACL_PUBLIC_READ);	
			if (!$result_save){
			$data['res']='0';
			$data['msg']='Error in moving the image, try later.';
			echo json_encode($data);
			return;
			}	
			//correctImageOrientation("uploads/" . $new_name);
			$new_name = IMGPATH.'flexi_images/'.$new_name;
			}
			else
			{
			   if (!move_uploaded_file($_FILES["file"]["tmp_name"], "flexi/" . $new_name)){
			   $data['res']='0';
			   $data['msg']='Error in moving the image, try later.';
			   echo json_encode($data);
			   return;
			}	
			//correctImageOrientation("uploads/" . $new_name);
			   $new_name =IMGUR2.$new_name;
			}	
	
        
			
			
			$query=$this->db->query("update `FlexiShift_master` set `FakeLocationStatusTimeOut`=$FakeLocationStatus, `location_out`=? ,`latit_out`=?,`longi_out`=?, `time_out`=?,`timeout_date`=?,`checkout_img`=? where Id=?",array($addr,$latit,$longi,$time,$date,$new_name,$visit_id));
			if($query>0){
				$data['res']='1';
				$data['msg']='Attendance marked successfully.';
			}
			else
			{
				$data['res']='0';
				$data['msg']='Unable to mark Attendance, try later.';
			}
		
		echo json_encode($data);
	}
	

	 public function getFlexiInfo()
    { 
		$uid=isset($_REQUEST['uid'])?$_REQUEST['uid']:0;
		$orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:0;
		//$zone=getTimeZone($orgid);
		$zone    = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
		date_default_timezone_set($zone);	
		//$today=isset($_REQUEST['date'])?$_REQUEST['date']:date('Y-m-d');
		$today=date('Y-m-d');
		$lastdate ="";
		$status = GetPlanStatus($orgid);
		if($status==1)
		$lastdate = date('Y-m-d',strtotime("-30 days"));
	  else
		  $lastdate = date('Y-m-d',strtotime("-7 days"));
		//$lastsevenday = date('Y-m-d', strtotime('-7 days'));
		//if($uid!=0){
			$query = $this->db->query("SELECT Id,EmployeeId,`location`,location_out,`time`,`time_out`,`date`,`timeout_date`,checkin_img,checkout_img, `client_name`, `description`,`latit`, `longi`, `latit_out`, `longi_out` FROM `FlexiShift_master` WHERE EmployeeId=? and date  between ? AND ? order by date ",array($uid , $lastdate , $today ));	
		//}else{
			/* $today=isset($_REQUEST['date'])?$_REQUEST['date']:date('Y-m-d');
			$today=date('Y-m-d',strtotime($today));
			$query = $this->db->query("SELECT Id, EmployeeId,`location`,location_out,`time`,`time_out`,checkin_img,checkout_img, `client_name`, `description`,`latit`, `longi`, `latit_out`, `longi_out` FROM `FlexiShift_master` WHERE OrganizationId=? and date=? order by EmployeeId ",array($orgid,$today)); */	
		//}
		$res=array();
		foreach ($query->result() as $row){
			$data=array();
			$data['Id']=$row->Id;
			$data['emp']=getEmpName($row->EmployeeId);
			$data['loc_in']=$row->location;
			$data['loc_out']=$row->location_out;
			$data['time_in']=date('H:i',strtotime($row->time));
			$data['time_out']=date('H:i',strtotime($row->time_out));
			$data['latit']=$row->latit;
			$data['longi']=$row->longi;
			$data['longi_out']=$row->longi_out;
			$data['latit_in']=$row->latit_out;
			$data['client']=$row->client_name;
			if($row->date!='0000-00-00'){
			$data['date']=date("d-m-Y", strtotime($row->date));
			}
			else{
				$data['date']='-';
			}
			if($row->timeout_date!='0000-00-00'){
			$data['timeout_date']=date("d-m-Y", strtotime($row->timeout_date));
			}else{
				$data['timeout_date']='-';
			}
			$data['desc']=$row->description;
			$data['checkin_img']=$row->checkin_img;
			$data['checkout_img']=$row->checkout_img;
			$res[]=$data;
		}
		echo json_encode($res);
		
		/*
        $uid   = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid = isset($_REQUEST['refno']) ? $_REQUEST['refno'] : 0;
        $zone  = getTimeZone($orgid);
        date_default_timezone_set($zone);
        $today      = date('Y-m-d');
        $query      = $this->db->query("SELECT Id FROM `checkin_master` WHERE `EmployeeId`=? and `OrganizationId`=? and `time_out`='00:00:00' and date=? order by id desc limit 1 ", array(
            $uid,
            $orgid,
            $today
        ));
        $data       = array();
        $data['id'] = 0;
        if ($row = $query->result())
            $data['id'] = $row[0]->Id;
        echo json_encode($data);
		*/
    }
	
	
	public function checkTimeOff()
	 {
        $uid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
        $data       = array();
		$sts=0;
		$timeid = "0";
		$query = $this->db->query("SELECT MAX(id) as timeid FROM `Timeoff` WHERE `EmployeeId`=$uid");
		 if ($row = $query->result())
            $timeid = $row[0]->timeid;
		
		if($timeid!=''){
		$query1 = $this->db->query("SELECT *  FROM `Timeoff` WHERE `id`=$timeid ");
        $data['id'] = 0;
		$data['sts']= 1;
		 if ($row = $query1->result())
		 {
			if($row[0]->TimeTo=='00:00:00'){
				$data['id']=$row[0]->Id;
				$data['sts']=2;
			}
		}
		
		}
	   echo json_encode($data);
      //  echo json_encode($query1->result());
      }
	
	
	
	public function getAttendanceesFlexi()
	 {
        $uid = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
        
        $data       = array();
		$sts=0;
		$query = $this->db->query("SELECT MAX(id) as fid FROM `FlexiShift_master` WHERE `EmployeeId`=$uid ");
		 if ($row = $query->result())
            $fid = $row[0]->fid;
		
		if($fid!=''){
		$query1 = $this->db->query("SELECT *  FROM `FlexiShift_master` WHERE `id`=$fid ");
		
        $data['id'] = 0;
		 if ($row = $query1->result())
		 {
            $data['id'] = $row[0]->id;
		    $timein = $row[0]->time;
		    $time_out = $row[0]->time_out;
			if($timein=='00:00:00'){
				$data['id']=$row[0]->id;
				$data['sts']=1;
			}
			
			if($time_out=='00:00:00'){
				$data['id']=$row[0]->id;
				$data['sts']=2;
			}

			
		}
		
		}
	   echo json_encode($data);
      //  echo json_encode($query1->result());
      }
	  
	  
	  
	   public function getFlexiInfoReport()
       { 
	    $seid=isset($_REQUEST['seid'])?$_REQUEST['seid']:0;
		$uid=isset($_REQUEST['uid'])?$_REQUEST['uid']:0;
		$orgid=isset($_REQUEST['orgid'])?$_REQUEST['orgid']:0;
		if($uid!=0)
        	$zone  = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
		date_default_timezone_set($zone);	
		$today=isset($_REQUEST['date'])?$_REQUEST['date']:date('Y-m-d');
		/*if($seid==0){
			$seid=$uid;
		}
		else{
			$seid=$seid;
		}*/
		$today=date('Y-m-d',strtotime($today));
		if($seid==0){
			$query = $this->db->query("SELECT Id,EmployeeId,`location`,location_out,`time`,`time_out`,`date`,`timeout_date`,checkin_img,checkout_img, `client_name`, `description`,`latit`, `longi`, `latit_out`, `longi_out` FROM `FlexiShift_master` WHERE  date=? AND OrganizationId = ? AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) order by EmployeeId ",array($today,$orgid));	
		}else{
			
			
			$query = $this->db->query("SELECT Id,EmployeeId,`location`,location_out,`time`,`time_out`,`date`,`timeout_date`,checkin_img,checkout_img, `client_name`, `description`,`latit`, `longi`, `latit_out`, `longi_out` FROM `FlexiShift_master` WHERE EmployeeId=? and date=? AND  EmployeeId in (SELECT Id from EmployeeMaster where OrganizationId = $orgid AND Is_Delete = 0) order by EmployeeId ",array($seid,$today));
		}
		$res=array();
		foreach ($query->result() as $row)
		   {
			$data=array();
			$data['Id']=$row->Id;
			$data['emp']=getEmpName($row->EmployeeId);
			$data['loc_in']=$row->location;
			$data['loc_out']=$row->location_out;
			$data['time_in']=date('H:i',strtotime($row->time));
			$data['time_out']=date('H:i',strtotime($row->time_out));
			$data['latit']=$row->latit;
			$data['longi']=$row->longi;
			$data['longi_out']=$row->longi_out;
			$data['latit_in']=$row->latit_out;
			$data['client']=$row->client_name;
			if($row->date!='0000-00-00'){
			$data['date']=date("d-m-Y", strtotime($row->date));
			}
			else{
				$data['date']='-';
			}
			if($row->timeout_date!='0000-00-00'){
			$data['timeout_date']=date("d-m-Y", strtotime($row->timeout_date));
			}else{
				$data['timeout_date']='-';
			}
			$data['desc']=$row->description;
			$data['checkin_img']=$row->checkin_img;
			$data['checkout_img']=$row->checkout_img;
			$res[]=$data;
		}
		echo json_encode($res);
    }
	
	public function getOutsidegeoReport()
	 {
		$result = array();
		$seid=isset($_REQUEST['seid'])?$_REQUEST['seid']:0;
		$uid=isset($_REQUEST['uid'])?$_REQUEST['uid']:0;
		$orgid= isset($_REQUEST['orgid'])?$_REQUEST['orgid']:0;
		if($uid!=0)
        	$zone  = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
        else
        	$zone  = getTimeZone($orgid);
		date_default_timezone_set($zone);	
		$date=isset($_REQUEST['date'])?$_REQUEST['date']:date('Y-m-d');
		$date=date('Y-m-d',strtotime($date));
		$time = date("H:i:00");	
		$q = "";
		if($seid!='0' && $seid!='')	
			$q = " AND A.EmployeeId = $seid ";
			
			$list['admin']=getAdminName($orgid);
			$list['email']=getAdminEmail($orgid);
			$q2="SELECT A.Id, A.EmployeeId, E.FirstName, E.LastName, A.AttendanceDate as date, A.AttendanceStatus, A.TimeIn, A.Device,A.TimeOut, A.ShiftId, A.Overtime,A.EntryImage, A.ExitImage,A.latit_in,A.longi_in,A.longi_out,A.latit_out, A.checkInLoc, A.CheckOutLoc,A.areaId, G.Lat_Long, G.Radius FROM AttendanceMaster A, Geo_Settings G, EmployeeMaster E WHERE A.OrganizationId=".$orgid." and G.OrganizationId = ".$orgid." and E.OrganizationId = ".$orgid." and A.AttendanceDate='".$date."'  and A.TimeIn!='00:00' and A.areaId!=0 and G.Id=A.areaId and E.Id= A.EmployeeId AND E.Is_Delete = 0 $q order by A.AttendanceDate Desc";
					$query = $this->db->query($q2);
					$res=array();
					foreach($query->result() as $row){
						$res1=array();
						$res1['id'] = $row->Id;
						$res1['empname'] = $row->FirstName." ".$row->LastName;	
						//print_r($res1['Name']);
						$res1['timein']=substr($row->TimeIn,0,-3);
						$res1['timeout']=substr($row->TimeOut,0,-3);
						$res1['locationin']=$row->checkInLoc;
						if($res1['locationin']=="")
						$res1['locationin'] = $res1['latin']=$row->latit_in. " , ".$res1['lonin']=$row->longi_in;
						$res1['locationout']=$row->CheckOutLoc;
						if($res1['locationout']=="" AND  $row->latit_out != '0.0' AND $row->longi_out != '0.0')
						 $res1['locationout']  =  $row->latit_out. ",".$row->longi_out;	
						$res1['latin']=$row->latit_in;
						$res1['lonin']=$row->longi_in;
						$res1['latout']=$row->latit_out;
						$res1['lonout']=$row->longi_out;
						$res1['attdate']=$row->date;
						$res1['instatus']= "";
						$res1['outstatus'] = "";
						$res1['incolor']= '1';
						$res1['outcolor']='1';
						
						$lat_lang = $row->Lat_Long;
						$radius = $row->Radius;
						$arr1 = explode(",",$lat_lang);
						if(count($arr1)>1)
						{
								$a=floatval($arr1[0]);
								$b=floatval($arr1[1]);
								$d1 = $this->distance($a,$b, $row->latit_in, $row->longi_in, "K");
								$d2 = $this->distance($a,$b, $row->latit_out, $row->longi_out, "K");
							if($row->latit_in!='0.0' && $row->latit_in!='0')
							{
								if($d1 <= $radius){
									$res1['instatus'] = '';
								}else{
									$res1['instatus'] ='Outside the Location';
									$res1['incolor'] ='0';
									
								}
							}
							if($row->latit_out != '0.0' && $row->latit_out != '0'){
								if($d2 <= $radius){
									$res1['outstatus'] = '';
								}else{
									$res1['outstatus'] ='Outside the Location';
									$res1['outcolor'] ='0';
								}
							}
						}
						if($res1['outstatus'] != '' || $res1['instatus']!='')
						{
							if($res1['outstatus']=='' && $res1['timeout']!='00:00')
							{
								$res1['locationout']== $row->Device;
								$res1['outstatus']=' Within the location';
								$res1['outcolor'] ='1';
							}
							if($res1['instatus']=='')
							{
								$res1['instatus']=' Within the location';
								$res1['incolor'] ='1';
							}
								
							$res[] = $res1;
						}
					}
				echo  json_encode($res);
	 }
	 public function distance($lat1, $lon1, $lat2, $lon2, $unit) 
    {
	  $theta = $lon1 - $lon2;
	  $dist = sin(deg2rad((float)$lat1)) * sin(deg2rad((float) $lat2)) +  cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * cos(deg2rad((float) $theta));
	  $dist = acos($dist);
	  $dist = rad2deg($dist);
	  $miles = $dist * 60 * 1.1515;
	  $unit = strtoupper($unit);
	  if ($unit == "K") 
	  {
		return ($miles * 1.609344);
	  } 
	  else if ($unit == "N") 
	  {
		return ($miles * 0.8684);
	  } else 
			{
			return $miles;
		  }
    }	
    
      public function addHoliday()
    {
        
        $orgid = isset($_REQUEST['org_id']) ? $_REQUEST['org_id'] : 0;
        $empid   = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
        $name   = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
        $from   = isset($_REQUEST['from']) ? $_REQUEST['from'] : 0;
        $to   = isset($_REQUEST['to']) ? $_REQUEST['to'] : 0;
        $description   = isset($_REQUEST['description']) ? $_REQUEST['description'] : 0;
        $date  = date('Y-m-d');
		$query=$this->db->query("Select Id from HolidayMaster where Name=? and DateFrom=? and OrganizationId=?",array($name,$from,$orgid));
		$res = $this->db->affected_rows();
		if($res>0)
		{
			echo -1; // if holiday already exist
			return false;
		}
        $query = $this->db->query("INSERT INTO `HolidayMaster`(`Name`, `Description`, `DateFrom`, `DateTo`, `DivisionId`, `OrganizationId`, `CreatedDate`, `CreatedById`, `LastModifiedDate`, `LastModifiedById`, `FiscalId`) VALUES (?,?,?,?,'0',?,?,?,?,?,1)", array(
            $name,
            $description,
            $from,
            $to,
            $orgid,
            $date,
            $empid,
            $date,
            $empid
        ));
        $res = $this->db->affected_rows();
        echo $res;
    }
         public function getAllHoliday($orgid)
    {
        $query = $this->db->query("SELECT `Id`, `Name`, `Description`, DATE(DateFrom) AS fromDate, `DateTo`, DATEDIFF(DATE(DateTo),DATE(DateFrom)) + 1  AS DiffDate FROM `HolidayMaster` WHERE OrganizationId=?  order by fromDate DESC", array($orgid ));
		$res = array();
		foreach($query->result() as $row)
		{
			$data['Id'] =  $row->Id;
			$data['Name'] =  $row->Name;
			$data['Description'] =  $row->Description;
			$data['fromDate'] =   date("d/m/Y", strtotime($row->fromDate));
			$data['DiffDate'] =  $row->DiffDate;
			$data['DateTo'] =  date("d/m/Y", strtotime($row->DateTo));
			$res[] = $data;
		}
        echo json_encode($res);
    }
	function checkNextWorkingDayCode(){
		//$date=$_REQUEST['date'];
		$shift_id=$_REQUEST['shift'];
		echo nextWorkingDayAfterToday($shift_id);
	}
	
	 function syncAshTechData()
	{
		//$date=$_REQUEST['date'];
       // echo $_REQUEST['data'];
        $data=$_REQUEST['data'];
       // print_r($data);

        $data=stripslashes($data);
        //echo $data;die;
        $decodedText = html_entity_decode($data);
        $decodedText=stripslashes($decodedText);
        // print_r (json_decode($decodedText));die;
        $data=json_decode($decodedText,true);
		
		
		

        $query = $this->db->query("INSERT INTO `AshtechLogs`(`Srno`, `EmployeeCode`, `TicketNo`, `EntryDate`, `InOutFlag`, `EntryTime`, `TrfFlag`, `UpdateUID`, `Location`, `ErrorMsg`,`OrganizationId`) VALUES ('".$data['srno']."','".$data['EmpCode']."','".$data['TicketNo']."','".$data['EntryDate']."','".$data['InOutFlag']."','".$data['EntryTime']."','".$data['TrfFlag']."','".$data['UpdateUID']."','".$data['Location']."','',36566)");
        if(isset($_REQUEST["data"]))
        Trace($_REQUEST['data']);
	
		$TicketNo=$data['EmpCode'];
		$dontUpdateTimeOut=false;
		
		$Srno=$data['srno'];
        $EmployeeId;
		$AttendanceDate='0000-00-00';
		$AttendanceStatus=1;
		$TimeIn='';
		$TimeOut='';
		$ShiftId=0;
		$Dept_id=0;
		$Desg_id=0;
		$areaId=0;
		$OrganizationId=36566;
		$CreatedDate='0000-00-00';
		$CreatedById=0;
		$LastModifiedDate='0000-00-00';
		$LastModifiedById=0;
		$OwnerId=0;
		$Overtime=0;
		$device="Biometric";
		$TimeinIp='';
		$TimeoutIp='';
		$EntryImage='https://ubitech.ubihrm.com/public/avatars/male.png';
		$ExitImage='https://ubitech.ubihrm.com/public/avatars/male.png';
		$checkInLoc='';
		$CheckOutLoc='';
		
		$timeindate='0000-00-00';
		$timeoutdate='0000-00-00';
	
            
		$latit_in='18.9987679';
		$longi_in='72.8235059';
		$latit_out='18.9987679';
		$longi_out='72.8235059';
		
		$Is_Delete=0;
		
		
		$Platform="Biometric";
		/*
		$empData=getEmpDataFromTicketNo($TicketNo,$OrganizationId);
		//print_r($empData);die;
		if($empData!="Not Found")
		{
			$EmployeeId=$empData->EmployeeId;
			$ShiftId=$empData->ShiftId;
			$Dept_id=$empData->DepartmentId;
			
			$Desg_id=$empData->DesignationId;
		}
		*/
		  ///-----------Iterating every coming record--------------------
            $sql='';
            $offlineTableRecordId=isset($data["Srno"])?$data["Srno"]:0;
            $Time=isset($data["EntryTime"])?Date('G:i:s',strtotime($data['EntryTime'])):'';
            
            
            //$action=$data[$i]["Action"];
            $FakeLocationStatus=0;
            $FakeLocationStatusTimeIn=0;
            $FakeLocationStatusTimeOut=0;
           // echo 'Action :'.$action.'  Time :'.$time;
            //$pictureBase64=$data[$i]["PictureBase64"];
            $Latitude='-';
            $Longitude='-';
            $Address='-';
            $FakeTimeStatus=0;
            
            
           // $EmployeeId=$data[$i]["UserId"];
            $AttendanceDate= Date('Y-m-d',strtotime($data['EntryDate'])); 
			$Date=$AttendanceDate;
            $AttendanceStatus=1;
            
            $LastModifiedById=0;
            $TimeIn='';
            $TimeCol='';
            //$device='mobile offline';
            $EmployeeRecord=getEmpDataFromTicketNo($data['EmpCode'],$OrganizationId);
            $timeindate='0000-00-00';
            $timeoutdate='0000-00-00';
            //$statusArray[$i][$offlineTableRecordId]='Success';
            
			$EmployeeId=0;
			$HourlyRateId=0;
            $ShiftType=0;
            if($EmployeeRecord!=false){
				$EmployeeId=$EmployeeRecord->Id;
                $ShiftId=$EmployeeRecord->Shift;
                $Dept_id=$EmployeeRecord->Department;
                $Desg_id=$EmployeeRecord->Designation;
                $areaId=$EmployeeRecord->area_assigned;
                $HourlyRateId=$EmployeeRecord->hourly_rate;
                $OwnerId=$EmployeeRecord->OwnerId;
               // echo 'Employee Record like desg shift etc found';
                
            }
            else{
                //$statusArray[$i][$offlineTableRecordId]='Employee Id Not Found';
               // echo 'error while finding shift ';
            
            }

			$CreatedById=$EmployeeId;
            
            $new_name="";
            $EntryImage='';
            $ExitImage='';
            $FakeTimeInTimeStatus=0;
            $FakeTimeOutTimeStatus=0;
            $attendanceAlreadyMarkedRecord=checkIfAttendanceAlreadyMarked($OrganizationId,$EmployeeId,$AttendanceDate,0);
			$action=0;
			$idToUpdate=0;
			$attendanceDevice='';
			$timeInTime='';
			if($attendanceAlreadyMarkedRecord!=false){
				
				
				
				
                $attendanceDevice=$attendanceAlreadyMarkedRecord->device;
                $idToUpdate=$attendanceAlreadyMarkedRecord->Id;
                //if($attendanceDevice!='Absentee Cron')
				if(true)
                {
                    $action=1;
                    $idToUpdate=$attendanceAlreadyMarkedRecord->Id;
                    $timeInTime=$attendanceAlreadyMarkedRecord->TimeIn;
                    $timeOutTime=$attendanceAlreadyMarkedRecord->TimeOut;
					if(date("i",strtotime("12/12/2019 ".$timeInTime)-strtotime("12/12/2019 ".$Time))<5)
						$dontUpdateTimeOut=true;
					
					
                    if(strtotime($Time)<strtotime($timeInTime)){
                        $Time=$timeInTime;
                        if($timeOutTime!='00:00:00'){
                            $Time=$timeOutTime;
                        }
                    }
                }
				
				
			}
			
            if($action==0){// Time In is synced
                $new_name="";
                $milliseconds = round(microtime(true) * 100000);
                $new_name   = $EmployeeId . '_' . date('dmY_His') .$milliseconds. ".jpg";
                $TimeCol="TimeIn";
                $TimeIn=$Time;
                $TimeOut='00:00:00';
                $EntryImage="https://ubitech.ubihrm.com/public/avatars/male.png";
                $ExitImage='';
                $checkInLoc=$Address;
                $checkOutLoc='';
                $FakeLocationStatusTimeIn=0;
                $latit_in=$Latitude;
                $longi_in=$Longitude;
                $latit_out='0.0';
                $longi_out='0.0';
                $timeindate=$AttendanceDate;
                $timeoutdate='0000-00-00';
                $FakeTimeInTimeStatus=0;
                
            }
            else if($action==1){// Time Out is synced
                $new_name="";
                $milliseconds = round(microtime(true) * 100000);
                $new_name   = $EmployeeId . '_' . date('dmY_His') .$milliseconds. ".jpg";
                
                $TimeCol="TimeOut";
                $TimeOut=$Time;
                //$TimeIn='00:00:00';
                $ExitImage="https://ubitech.ubihrm.com/public/avatars/male.png";
                //$EntryImage='';
                $checkOutLoc=$Address;
                
                
                $latit_out=$Latitude;
                $longi_out=$Longitude;
                $timeoutdate=$AttendanceDate;
               
                $FakeLocationStatusTimeOut=0;
                $FakeTimeOutTimeStatus=0;
				
            }
            else{
                //$statusArray[$i][$offlineTableRecordId]='Wrong Action Synced';//Wrong data synced
               // echo 'Wrong action';
            }

            

           //echo "EntryImage: $EntryImage  ExitImage: $ExitImage";


            /*----------------------------- Shift Calculation---------------------------------------*/
            
            $time = $Time=="00:00:00"?"23:59:00":$Time;
            /*
			$shiftId=$ShiftId;
            $stype = 0;
            $sql1  = "SELECT TIMEDIFF(`TimeIn`,`TimeOut`) AS stype,	shifttype,`TimeIn`,`TimeOut` FROM ShiftMaster where id=" . $shiftId;
			
            try {
                $result1 = $this->db->query($sql1);
                if ($row1 = $result1->row()) {
                    $stype = $row1->stype;
					$sti=$row1->TimeIn;
					$sto=$row1->TimeOut;
                    $shifttype=$row1->shifttype;
                    $ShiftType=$shifttype;
                    //echo $ShiftId;;
                }
            }
            catch (Exception $e) {
                $statusArray[$i][$offlineTableRecordId]='Error finding shift information';
            }
            if($shifttype==2 && $action==0){ // multi date shift case
                //echo "inside shift type check";
				if(strtotime($time)<strtotime($sto)){ // time in should mark in last day date
					try{
                  //      echo "changing time in date to previous";
						$ldate   = date('Y-m-d', strtotime('-1 day', strtotime($AttendanceDate)));
                        $AttendanceDate=$ldate;	
                    //    echo $AttendanceDate;
					}catch(Exception $e){
						$statusArray[$i][$offlineTableRecordId]='Error calculating previous day in multi date shift';
					}
				}
				//else  time in should mark in current day's date
            }
            else if($shifttype==2 && $action==1){
                if($time>$sti){ // time in should mark in last day date
					try{
                        //echo "changing time out date to previous";
						$ldate   = date('Y-m-d', strtotime('-1 day', strtotime($AttendanceDate)));
                        $AttendanceDate=$ldate;	
                       // echo $AttendanceDate;
					}catch(Exception $e){
						$statusArray[$i][$offlineTableRecordId]='Error calculating previous day in multi date shift';
					}
				}
            }

			*/
            

                

            
            $stamp=Date('Y-m-d H:m:s');


            
            $insertSql  = "INSERT INTO `AttendanceMaster`(`FakeTimeInTimeStatus`,`FakeTimeOutTimeStatus`,`FakeLocationStatusTimeIn`,`FakeLocationStatusTimeOut`, `EmployeeId`, `AttendanceDate`, `AttendanceStatus`, `TimeIn`, `TimeOut`, `ShiftId`, `Dept_id`, `Desg_id`, `areaId`, `OrganizationId`,  `CreatedById`, `OwnerId`,  `device`, `EntryImage`, `ExitImage`, `checkInLoc`, `CheckOutLoc`, `timeindate`, `timeoutdate`, `latit_in`, `longi_in`, `latit_out`, `longi_out`, `HourlyRateId`,LastModifiedDate) VALUES ($FakeTimeInTimeStatus,$FakeTimeOutTimeStatus,$FakeLocationStatusTimeIn,$FakeLocationStatusTimeOut,'$EmployeeId', '$AttendanceDate', '$AttendanceStatus', '$TimeIn', '$TimeIn', '$ShiftId', '$Dept_id', '$Desg_id', '$areaId', '$OrganizationId',  '$CreatedById', '$OwnerId',  '$device', '$EntryImage', '$ExitImage', '$checkInLoc', '$checkOutLoc', '$timeindate', '$timeoutdate', '$latit_in', '$longi_in', '$latit_out', '$longi_out', '$HourlyRateId','$stamp')";
			
            

            
           // $updateTimeOutSql="UPDATE `AttendanceMaster` SET `FakeLocationStatusTimeIn`=$FakeLocationStatusTimeIn,`FakeLocationStatusTimeOut`=$FakeLocationStatusTimeOut, `ExitImage`='" . $ExitImage . "',CheckOutLoc='$Address',latit_out='$Latitude',longi_out='$Longitude', TimeOut='$TimeOut', LastModifiedDate='$stamp' ,overtime = subtime('$timeSpent','$shiftHours'),timeoutdate='$date";
            
            
		
		//	echo $date;
		//	return false;
			
            //////----------------/gettign shift info
            //Trace($act.' AID'.$aid.'UserId'.$userid);
            
           
                
            $updateSQL = "UPDATE `AttendanceMaster` SET `ExitImage`='" . $ExitImage . "',CheckOutLoc='$Address',latit_out='$Latitude',longi_out='$Longitude', TimeOut='$Time', LastModifiedDate='$stamp',overtime =(SELECT subtime(subtime('$Time',timein),
                (select subtime(timeout,timein) from ShiftMaster where id=$ShiftId)) as overTime ),timeoutdate='$Date'
                WHERE id=$idToUpdate"; 
            if($EmployeeId!=0){
				if($action==0){
                    $this->db->query($insertSql);
			if($idToUpdate!=0)
                    {
					
                        $this->db->query("delete from AttendanceMaster where Id=".$idToUpdate);
                        //$this->db->query($insertSql);

                    }
                }				
				else if($action==1){
					if(!$dontUpdateTimeOut)
					$this->db->query($updateSQL);
				}
			
			}
			
        



		echo $Srno;
    }
	
	public function calculateReferralDiscount(){
		//echo "calculateReferralDiscount";
		$orgId=0;
		$orgId=$_REQUEST['orgId'];
		
		$referrerDiscountRupees=0;
		$referrerDiscountPercentage=0;
		$referrerDiscountDollars=0;
		
		$referrenceDiscountRupees=0;
		$referrenceDiscountPercentage=0;
		$referrenceDiscountDollars=0;
		
		$zone=getTimeZone($orgId);
        date_default_timezone_set($zone);
		$paymentDate = date("Y-m-d");
		
		$referrerDiscountRows=$this->db->query("select * from Referrals where ReferringOrg = ".$orgId."  and ReferrerGivenDiscount=0 and ReferrerDiscountValidUpTo<='$paymentDate'");
		$referrenceDiscountRows=$this->db->query("select * from Referrals where ReferrencedOrg = ".$orgId." and ReferrrenceGivenDiscount=0 and ValidFrom>='$paymentDate' and ValidTo<='$paymentDate'");
		
		if ($referrerDiscountRows->num_rows() > 0)
		{
		   foreach ($referrerDiscountRows->result() as $row)
		   {
			  if($row->DiscountType==0){
				 // echo $row->DiscountForReferrer."<br>";
				$referrerDiscountRupees+=$row->DiscountForReferrer;
			  }
			  else if($row->DiscountType==1){
				 // echo $row->DiscountForReferrer."<br>";
				$referrerDiscountDollars+=$row->DiscountForReferrer;
			  }
			  else if($row->DiscountType==2){
				 // echo $row->DiscountForReferrer."<br>";
				$referrerDiscountPercentage+=$row->DiscountForReferrer;
			  }
		   }
		}
		
		
		if ($referrenceDiscountRows->num_rows() > 0)
		{
		   foreach ($referrenceDiscountRows->result() as $row)
		   {
			  if($row->DiscountType==0){
				$referrenceDiscountRupees+=$row->DiscountForReferrence;
			  }
			  else if($row->DiscountType==1){
				$referrenceDiscountDollars+=$row->DiscountForReferrence;
			  }
			  else if($row->DiscountType==2){
				$referrenceDiscountPercentage+=$row->DiscountForReferrence;
			  }
		   }
		}
		
		
		$response=Array("referrerDiscountRupee"=>0,"referrerDiscountDollar"=>0,"referrerDiscountPercent"=>0,"referrenceDiscountRupee"=>0,"referrenceDiscountDollar"=>0,"referrenceDiscountPercent"=>0);
		
		$response["referrerDiscountRupee"]=$referrerDiscountRupees;
		$response["referrerDiscountDollar"]=$referrerDiscountDollars;
		$response["referrerDiscountPercent"]=$referrerDiscountPercentage;
		$response["referrenceDiscountRupee"]=$referrenceDiscountRupees;
		$response["referrenceDiscountDollar"]=$referrenceDiscountDollars;
		$response["referrenceDiscountPercent"]=$referrenceDiscountPercentage;
		
		echo json_encode($response);
		
		
		
	
	}
	public function updateReferralDiscountStatus(){
		//echo "updateReferralDiscountStatus";
		
		$orgId=0;
		$orgId=isset($_REQUEST['orgId'])?$_REQUEST['orgId']:0;
		$amountPaid=isset($_REQUEST['amountPaid'])?$_REQUEST['amountPaid']:0;
		
		
		$query = $this->db->query("SELECT *,(select name from Organization where Id=$orgId) as OrganizationName,(select email from Organization where Id=ReferringOrg) as ReferringOrganizationEmail FROM Referrals WHERE ReferrencedOrg=$orgId");
        $result=$query->result();
		$email="";
		$subject="";
		$message="";
		$headers="";
		$discount=0;
		if($orgId!=0&&$amountPaid!=0){
			 for($i=0;$i<count($result);$i++){
				$discount=$amountPaid*$result[$i]->DiscountForReferrer*0.01;
				
				$email=$result[$i]->ReferringOrganizationEmail;
				$subject="You got referral discount on ubiAttendance";
				$message="Bingo! ".$result[$i]->OrganizationName." has paid for ubiAttendance App. You have earned ".$discount."
				You can redeem it until ".	$result[$i]->ReferrerDiscountValidUpTo.". ";

			//sendEmail_new($email, $subject, $message, $headers);

			}
		}
       
	
        
		
		$this->db->query("update Referrals set ReferrerGivenDiscount=1 where ReferringOrg=".$orgId);
		$this->db->query("update Referrals set ReferrrenceGivenDiscount=1 where ReferrencedOrg=".$orgId);
		
		echo $orgId;
		
	
	}
	public function updatePushNotificationStatusForEmployee(){
		//echo "updateReferralDiscountStatus";
		
		
		$employeeId=isset($_REQUEST['employeeId'])?$_REQUEST['employeeId']:0;
		$action=isset($_REQUEST['action'])?$_REQUEST['action']:''; // TimeIn for time in TimeOut for time out
		$value=isset($_REQUEST['value'])?$_REQUEST['value']:''; // TimeIn for time in TimeOut for time out
		
		$column=($action=='TimeIn')?'InPushNotificationStatus':'OutPushNotificationStatus';
		echo "update EmployeeMaster set $column=$value where Id=".$employeeId;
		$query = $this->db->query("update EmployeeMaster set $column=$value where Id=".$employeeId);
	}
	public function getEmployeeListForLT(){
		$date=isset($_REQUEST['date'])?$_REQUEST['date']:date('Y-m-d');
		$orgId=isset($_REQUEST['orgId'])?$_REQUEST['orgId']:0;
		//echo $orgId.$date."<br>";
		//echo "Select distinct(Id) as I, FirstName, LastName,(select checkin_img from checkin_master where EmployeeId=I and date=$date Order by id desc limit 1) as in_image, (select checkout_img from checkin_master where EmployeeId=I and date=$date Order by id desc limit 1) as out_image, (select client_name from checkin_master where EmployeeId=I and date=$date Order by id desc limit 1) as client from EmployeeMaster where OrganizationId=$orgId";
		//echo "<br>";
		$query = $this->db->query("Select distinct(Id) as I, FirstName,  LastName,(select checkin_img from checkin_master where EmployeeId=I and date='$date' Order by id desc limit 1) as in_image,(select time from checkin_master where EmployeeId=I and date='$date' Order by id desc limit 1) as in_time, (select time_out from checkin_master where EmployeeId=I and date='$date' Order by id desc limit 1) as out_time, (select checkout_img from checkin_master where EmployeeId=I and date='$date' Order by id desc limit 1) as out_image, (select client_name from checkin_master where EmployeeId=I and date='$date' Order by id desc limit 1) as client from EmployeeMaster where OrganizationId=$orgId and archive=1 and livelocationtrack=1");
		$res = array();
		
		foreach($query->result() as $row)
		{ 
			$data['empId'] =  $row->I;
			$data['fName'] =  $row->FirstName;
			$data['lName'] =  $row->LastName;
			$data['inImage'] =  $row->in_image;
			$data['outImage'] =  $row->out_image;
			$data['client'] =  $row->client;
			$data['in_time'] =  $row->in_time;
			$data['out_time'] =  $row->out_time;
			$res[] = $data;
		}
        echo json_encode($res);
		
	}
	public function storedeviceinfo()
	{
       $empid = isset($_REQUEST['empid'])?$_REQUEST['empid']:"";
        $deviceid = isset($_REQUEST['deviceid'])?$_REQUEST['deviceid']:"";
         $devicename = isset($_REQUEST['devicename'])?$_REQUEST['devicename']:"";
		//$device='Suspicious Device';
         //var_dump($absent);

         $query=$this->db->query("UPDATE EmployeeMaster SET DeviceName = '$devicename',DeviceId='$deviceid' where Id=? ",array($empid)); 
         //var_dump($this->db->last_query()); 

              $res= $this->db->affected_rows();	
              if($res){
              $data['status'] = 'Device saved successfully';
          }else{
          	$data['status'] = 'Unable to save device';
          }

			  $this->db->close();
			  echo json_encode($data, JSON_NUMERIC_CHECK);
	}
                                           
    public function getClientList()
    {
       $orgid = isset($_REQUEST['orgdir']) ? $_REQUEST['orgdir'] : '';
       $startwith = isset($_REQUEST['startwith']) ? $_REQUEST['startwith'] : '';
        $query = $this->db->query("SELECT Company as Name,Id FROM `ClientMaster` WHERE  `OrganizationId`=" . $orgid . " and Company like '%$startwith%' and status in (1, 2) order by Name");
       echo json_encode($query->result());
    }
	
	function saveGeolocation_auto($empid , $orgid)
	{
	  //return false;
	  $query = $this->db->query("SELECT id, FirstName,EmployeeCode from EmployeeMaster where id = $empid AND (area_assigned = 0 OR area_assigned = '' ) " );	
	  if($this->db->affected_rows()>0)
	  if($row = $query->row())
	  {  
       
      $name = $row->FirstName."_Home_".$row->EmployeeCode;
	  $query = $this->db->query("select id from Geo_Settings where Name = '$name' ");
	     if($this->db->affected_rows()>0)
		 {
			 return false;
		 }
	  
	  $query = $this->db->query("SELECT `checkInLoc`, `CheckOutLoc`, `latit_in`, `longi_in`, `latit_out`, `longi_out`, AttendanceDate , OrganizationId FROM `AttendanceMaster` WHERE checkInLoc !='' and (latit_in != '0.0' OR latit_in != '')  and (longi_in != '0.0' OR longi_in != '') AND AttendanceStatus = 1 and EmployeeId = $empid ORDER by AttendanceDate ASC limit 1");
      
	  if($row = $query->row())
	   {
		  $location =  $row->checkInLoc;
		  $latlong = $row->latit_in .' , '.$row->longi_in;
		  $radius = '0.1';
		  $zone=getTimeZone($orgid);
         date_default_timezone_set($zone);
		 $query=$this->db->query("INSERT INTO  Geo_Settings (`OrganizationId`,`Lat_Long`,`Location`,`Radius`,`Name`) VALUES (?,?,?,?,?)",array($orgid,$latlong,$location,$radius,$name));
			  $res= $this->db->affected_rows();
			  $areaid =  $this->db->insert_id();
			  $query = $this->db->query("UPDATE EmployeeMaster Set area_assigned = $areaid WHERE Id = $empid ");  
				
				 
	   }
	  }	  
	
	}
	public function Every7DaysCOVID19data() {
         $orgid            = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : "0";
		 $empid            = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : "0";
		 $hypertension            = isset($_REQUEST['hypertension']) ? $_REQUEST['hypertension'] : "0";
		 $cardio            = isset($_REQUEST['cardio']) ? $_REQUEST['cardio'] : "0";
		 $diabetes            = isset($_REQUEST['diabetes']) ? $_REQUEST['diabetes'] : "0";
		 $lungdiseases            = isset($_REQUEST['lungdiseases']) ? $_REQUEST['lungdiseases'] : "0";
		 $travelled            = isset($_REQUEST['travelled']) ? $_REQUEST['travelled'] : "0";
		 $gathering            = isset($_REQUEST['gathering']) ? $_REQUEST['gathering'] : "0";
		 $contact            = isset($_REQUEST['contact']) ? $_REQUEST['contact'] : "0";
		 $quarantined            = isset($_REQUEST['quarantined']) ? $_REQUEST['quarantined'] : "0";
		 $livingstatus            = isset($_REQUEST['livingstatus']) ? $_REQUEST['livingstatus'] : "0";
		 $renal            = isset($_REQUEST['renal']) ? $_REQUEST['renal'] : "0";
		 $liver            = isset($_REQUEST['liver']) ? $_REQUEST['liver'] : "0";
		 $noneofabove            = isset($_REQUEST['noneofabove']) ? $_REQUEST['noneofabove'] : "0";
		 $risk            = isset($_REQUEST['risk']) ? $_REQUEST['risk'] : "";
		 
		 
		 $contact_person_name=getEmpName($empid);
		 $data = array();
		 $zone    = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $date1  = date('Y-m-d', strtotime($date. ' + 7 days')); 

		
		$query = $this->db->query("INSERT INTO  Covid19Every7DaysTest (`OrganizationId`,`EmployeeId`,`EmployeeName`,`TravelledinLast14Days`,`GatheringinLast14Days`,`ContacttoCovid`,`QaurantinedforSuspected`,`LivingStatus`,`Hypertension`,`Diabetes`,`LungDisease`,`CardiovascularDisease`,`RenalDisease`,`LiverDisease`,`NoneoftheAboveDisease`,`Date`,`NextDate`,`Risk`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",array($orgid,$empid,$contact_person_name,$travelled,$gathering,$contact,$quarantined,$livingstatus,$hypertension,$diabetes,$lungdiseases,$cardio,$renal,$liver,$noneofabove,$date,$date1,$risk));
		if($this->db->affected_rows() > 0){
			$data['status'] = 'true';
		}
		else{
			$data['status'] = 'false';
		}
		echo json_encode($data);
		
	}
	public function EveryDayCOVID19data(){
		$orgid            = isset($_REQUEST['orgid']) ? $_REQUEST['orgid'] : "0";
		 $empid            = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : "0";
		 $contactwithperson            = isset($_REQUEST['contactwithperson']) ? $_REQUEST['contactwithperson'] : "0";
		 $covidsymptoms            = isset($_REQUEST['covidsymptoms']) ? $_REQUEST['covidsymptoms'] : "0";
		 
		
		 $contact_person_name=getEmpName($empid);
		 $data = array();
		 $zone    = getEmpTimeZone($empid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $query = $this->db->query("SELECT * FROM `Covid19Every7DaysTest` WHERE EmployeeId = $empid and OrganizationId = $orgid ORDER BY Date DESC");
		
		if($this->db->affected_rows()>0){
			if($row=$query->result()){
			$risk=$row[0]->Risk;	
		  }
		}

		
		$query = $this->db->query("INSERT INTO  Covid19EveryDayTest (`OrganizationId`,`EmployeeId`,`EmployeeName`,`ContactWithCovid`,`CovidSymptoms`,`Date`) VALUES (?,?,?,?,?,?)",array($orgid,$empid,$contact_person_name,$contactwithperson,$covidsymptoms,$date));
		if($this->db->affected_rows() > 0){
			$data['status'] = 'true';
			if($contactwithperson=='1' || $covidsymptoms=='1' || $risk=='High')
				$data['covidstatus']='1';
			else $data['covidstatus']='2';
		}
		else{
			$data['status'] = 'false';
		}
		echo json_encode($data);
	}
	public function applyLeave(){
      
        $uid    = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $reason = isset($_REQUEST['reason']) ? $_REQUEST['reason'] : '';
        $orgid  = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $fromdate  = isset($_REQUEST['fromdate']) ? $_REQUEST['fromdate'] : 0;
        $todate  = isset($_REQUEST['todate']) ? $_REQUEST['todate'] : 0;
       
		$halfdaysts = isset($_REQUEST['halfdaysts']) ? $_REQUEST['halfdaysts'] : 0;
        $halfdaysts1 = isset($_REQUEST['halfdaysts1']) ? $_REQUEST['halfdaysts1'] : 0;
     
        $device ='1';
        
		$zone    = getEmpTimeZone($uid,$orgid); // to set the timezone by employee country.
        date_default_timezone_set($zone);
        $stamp  = date("Y-m-d H:i:s");
        $date   = date("Y-m-d");
        $pastdate   = date('Y/m/d',strtotime("-1 days"));
        $today   = date("Y-m-d");
        $time   = date("H:i")=="00:00"?"00:01":date("H:i");  
        $data           = array();
        $data['status'] = 'false';
       


       $start = strtotime($fromdate);
       $end = strtotime($todate);

       $days_between = ceil(abs($end - $start) / 86400);
       // echo $days_between;
       // exit();
       
        $query= $this->db->query("select (max(Id)+1)as MaxId from AppliedLeave");
        
            foreach ($query->result() as $row) {
        	       $maxId=$row->MaxId;
                }

                if($maxId==NULL){
                	$maxId=1;
                }

        for ($j=0; $j <=$days_between ; $j++) {   

        	$date12=date_create($fromdate);
            date_add($date12,date_interval_create_from_date_string($j." days"));
            $date22= date_format($date12,"Y-m-d");
            //exit();

	

		
        $query1 = $this->db->query("SELECT * from AppliedLeave Where EmployeeId=$uid and (ApprovalStatus=1 OR ApprovalStatus=2) and Date='$date22'");
        $check = $this->db->affected_rows($query1);

        if ($check > 0){
        	$data['status']='false1';
        	echo json_encode($data);
        	return;

        }
	
}        
        

        // echo $maxId;
        // exit();

        for ($i=0; $i <=$days_between ; $i++) {   

        	$date1=date_create($fromdate);
            date_add($date1,date_interval_create_from_date_string($i." days"));
            $date2= date_format($date1,"Y-m-d");
            //exit();

	

		
        $query = $this->db->query("INSERT INTO `AppliedLeave`(`EmployeeId`, `Date`, `Reason`,`ApprovalStatus`,`CreatedDate`,`OrganizationId`,`LeaveId`) VALUES (?,?,?,?,?,?,?)",
		array(
            $uid,
            $date2,
            $reason,
            1,
            $stamp,
            $orgid,
            $maxId
            
        ));
	
}
        
       
		
        if ($this->db->affected_rows())
            $data['status'] = 'true';
        
        echo json_encode($data);
        
    }
    public function getListofLeave(){

    	$empid    = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid  = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
		

		$query = $this->db->query("SELECT Id,(select FirstName from EmployeeMaster where Id=$empid)as FirstName,(select LastName from EmployeeMaster where Id=$empid)as LastName,LeaveId,Reason,Date,ApprovalStatus,Remarks,count(LeaveId) as NoofLeaves,CreatedDate as AppliedDate from AppliedLeave WHERE EmployeeId=$empid and OrganizationId=$orgid Group By LeaveId Desc");
	
		$res=array();
		foreach($query->result() as $row){
			$data=array();
			$data['Id']=$row->Id;
			$FirstName=trim($row->FirstName);
			$FirstName=preg_replace('/\s\s+/', ' ',$FirstName);
			$LastName=trim($row->LastName);
			$LastName=preg_replace('/\s\s+/', ' ',$LastName);
			$data['name'] = ucwords(strtolower($FirstName." ".$LastName));
			//$data['name'] = trim($row->Name);
			$data['LeaveId']= $row->LeaveId;
			$data['Reason']=$row->Reason;
			$datetemp=date_create($row->Date);
			$data['Date']=date_format($datetemp,"d-M");
			$data['ApprovalStatus']= $row->ApprovalStatus;
			$data['Remarks']=$row->Remarks;
			$appdate= date_create($row->AppliedDate);
			$data['AppliedDate']= date_format($appdate,"d-M-Y");
			$data['NoofLeaves']= $row->NoofLeaves;
			$NoofLeaves= $data['NoofLeaves']-1;
			$date1=date_create($data['Date']);
            date_add($date1,date_interval_create_from_date_string($NoofLeaves." days"));
            $date2= date_format($date1,"d-M");
            $data['ToDate']= $date2;
			$res[]=$data;
			
		}
		echo json_encode($res);


    }
    public function getListofLeaveAll(){

    
        $orgid  = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;

        // $i=0;

        // $query1= $this->db->query("select LeaveId, count(LeaveId) as NoofLeaves from AppliedLeave where OrganizationId=$orgid Group By LeaveId");
        // $res1=array();
        // foreach ($query1->result() as $row) {

        // 	$data1=array();
        // 	$i++;
        // 	$data1[$i]['LeaveId']=$row->LeaveId;
        // 	$data1[$i]['NoofLeaves']=$row->NoofLeaves;
        // 	$res1[]=$data1;

        // }
        // echo $i;
        // echo json_encode($res1);
        // exit();


		

		$query = $this->db->query("SELECT Id,(select FirstName from EmployeeMaster where Id=AppliedLeave.EmployeeId)as FirstName,(select LastName from EmployeeMaster where Id=AppliedLeave.EmployeeId)as LastName,LeaveId,Reason,Date,ApprovalStatus,Remarks,count(LeaveId) as NoofLeaves,EmployeeId,CreatedDate as AppliedDate from AppliedLeave WHERE  OrganizationId=$orgid Group By LeaveId Desc");
	
		$res=array();
		foreach($query->result() as $row){
			$data=array();
			$data['Id']=$row->Id;
			$FirstName=trim($row->FirstName);
			$FirstName=preg_replace('/\s\s+/', ' ',$FirstName);
			$LastName=trim($row->LastName);
			$LastName=preg_replace('/\s\s+/', ' ',$LastName);
			$data['name'] = ucwords(strtolower($FirstName." ".$LastName));
			//$data['name'] = trim($row->Name);
			$data['LeaveId']= $row->LeaveId;
			$data['Reason']=$row->Reason;
			$datetemp=date_create($row->Date);
			$data['Date']=date_format($datetemp,"d-M");
			$data['ApprovalStatus']= $row->ApprovalStatus;
			$data['Remarks']=$row->Remarks;
			$appdate= date_create($row->AppliedDate);
			$data['AppliedDate']= date_format($appdate,"d-M-Y");
			$data['NoofLeaves']= $row->NoofLeaves;
			$NoofLeaves= $data['NoofLeaves']-1;
			$date1=date_create($row->Date);
            date_add($date1,date_interval_create_from_date_string($NoofLeaves." days"));
            $date2= date_format($date1,"d-M");
            $data['ToDate']= $date2;
            $data['EmployeeId']= $row->EmployeeId;
			$res[]=$data;
			
		}
		echo json_encode($res);


    }
    public function getListofLeaveEmployee(){

    	$empid    = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : 0;
        $orgid  = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
        $LeaveId  = isset($_REQUEST['LeaveId']) ? $_REQUEST['LeaveId'] : 0;
		

		$query = $this->db->query("SELECT Id,(select FirstName from EmployeeMaster where Id=$empid)as FirstName,(select LastName from EmployeeMaster where Id=$empid)as LastName,LeaveId,Reason,Date,ApprovalStatus,Remarks,CreatedDate as AppliedDate from AppliedLeave WHERE LeaveId=$LeaveId and OrganizationId=$orgid");
	
		$res=array();
		foreach($query->result() as $row){
			$data=array();
			$data['Id']=$row->Id;
			$FirstName=trim($row->FirstName);
			$FirstName=preg_replace('/\s\s+/', ' ',$FirstName);
			$LastName=trim($row->LastName);
			$LastName=preg_replace('/\s\s+/', ' ',$LastName);
			$data['name'] = ucwords(strtolower($FirstName." ".$LastName));
			//$data['name'] = trim($row->Name);
			$data['LeaveId']= $row->LeaveId;
			$data['Reason']=$row->Reason;
			$datetemp=date_create($row->Date);
			$data['Date']=date_format($datetemp,"d-M");
			$data['ApprovalStatus']= $row->ApprovalStatus;
			$data['Remarks']=$row->Remarks;
			$appdate= date_create($row->AppliedDate);
			$data['AppliedDate']= date_format($appdate,"d-M-Y");
			$res[]=$data;
			
		}
		echo json_encode($res);


    }
    public function withdrawLeave()
    {

    	$orgid  = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
    	$LeaveId  = isset($_REQUEST['LeaveId']) ? $_REQUEST['LeaveId'] : 0;
    	$empid  = isset($_REQUEST['empid']) ? $_REQUEST['empid'] : 0;
    	$ApprovalStatus  = '4'; 

    	 $query=$this->db->query("UPDATE AppliedLeave SET ApprovalStatus = 4 where LeaveId=? and OrganizationId=? ",array($LeaveId,$orgid)); 
         //var_dump($this->db->last_query()); 

              $res= $this->db->affected_rows();	
              if($res){
              $data['status'] = 'true';
          }else{
          	$data['status'] = 'false';
          }

    	// $query= $this->db->query("UPDATE AppliedLeave set ApprovalStatus=4 WHERE LeaveId='$LeaveId' and EmployeeId='$empid' and OrganizationId='$orgid'");
    	// $data= array();
    	// if($this->db->affected_rows() > 0){
    	// 	$data['res']='true';
    	// }

    	$this->db->close();
    	echo json_encode($data,JSON_NUMERIC_CHECK);




    }

    public function Approveleave()
    {

    	$orgid  = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
    	$LeaveId  = isset($_REQUEST['LeaveId']) ? $_REQUEST['LeaveId'] : 0;
    	$comment  = isset($_REQUEST['comment']) ? $_REQUEST['comment'] : '';
    	$ApprovalStatus  = isset($_REQUEST['sts']) ? $_REQUEST['sts'] : 0;

    	 $query=$this->db->query("UPDATE AppliedLeave SET ApprovalStatus = ?, Remarks=? where LeaveId=? and OrganizationId=? ",array($ApprovalStatus,$comment,$LeaveId,$orgid)); 
         //var_dump($this->db->last_query()); 

              $res= $this->db->affected_rows();	
              if($res){
              $data['status'] = 'true';
          }else{
          	$data['status'] = 'false';
          }

    	// $query= $this->db->query("UPDATE AppliedLeave set ApprovalStatus=4 WHERE LeaveId='$LeaveId' and EmployeeId='$empid' and OrganizationId='$orgid'");
    	// $data= array();
    	// if($this->db->affected_rows() > 0){
    	// 	$data['res']='true';
    	// }

    	$this->db->close();
    	echo json_encode($data,JSON_NUMERIC_CHECK);

    }

    public function ApproveleaveEmp()
    {

    	$orgid  = isset($_REQUEST['refid']) ? $_REQUEST['refid'] : 0;
    	$LeaveId  = isset($_REQUEST['LeaveId']) ? $_REQUEST['LeaveId'] : 0;
    	$comment  = isset($_REQUEST['comment']) ? $_REQUEST['comment'] : '';
    	$ApprovalStatus  = isset($_REQUEST['sts']) ? $_REQUEST['sts'] : 0;
    	$Ids  = isset($_REQUEST['Ids']) ? $_REQUEST['Ids'] : 0;

    	 $query=$this->db->query("UPDATE AppliedLeave SET ApprovalStatus = ?, Remarks=? where LeaveId=? and Id in(?) ",array($ApprovalStatus,$comment,$LeaveId,$Ids)); 
         //var_dump($this->db->last_query()); 

              $res= $this->db->affected_rows();	
              if($res){
              $data['status'] = 'true';
          }else{
          	$data['status'] = 'false';
          }

    	// $query= $this->db->query("UPDATE AppliedLeave set ApprovalStatus=4 WHERE LeaveId='$LeaveId' and EmployeeId='$empid' and OrganizationId='$orgid'");
    	// $data= array();
    	// if($this->db->affected_rows() > 0){
    	// 	$data['res']='true';
    	// }

    	$this->db->close();
    	echo json_encode($data,JSON_NUMERIC_CHECK);

    }

    public function myfunction(){
        echo "hello world";
        echo "Hi";
    }
                                           
	
	
	
}
