<?php include('config.php'); 
// ALTER TABLE `email_list_subscribers` ADD UNIQUE( `listid`, `emailaddress`);
// INSERT DELAYED IGNORE INTO `emailmarketer`.`email_list_subscribers` (`subscriberid`, `listid`, `emailaddress`, `domainname`, `format`, `confirmed`, `confirmcode`, `requestdate`, `requestip`, `confirmdate`, `confirmip`, `subscribedate`, `bounced`, `unsubscribed`, `unsubscribeconfirmed`, `formid`) VALUES (NULL, '15', 'priyankasaraf.777@gmail.com', '@gmail.com', 'h', '1', NULL, '0', NULL, '1430469201', NULL, '1430469201', '0', '0', '0', '0');

function randString($length, $charset='abcdefghijklmnopqrstuvwxyz0123456789')
{
    $str = '';
    $count = strlen($charset);
    while ($length--) {
        $str .= $charset[mt_rand(0, $count-1)];
    }
    return $str;
}

function alert_msg($msg,$type)
{
    $html = '';
    if($type == 'Success')
    {
        $html = '<div class="alert alert-success alert-dismissable">
                    <i class="fa fa-check"></i>
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <b>'.$type.' : </b> '.$msg.'
                </div>';
    }
    else if($type == 'Error')
    {
        $html = '<div class="alert alert-danger alert-dismissable">
                    <i class="fa fa-close"></i>
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <b>'.$type.' : </b> '.$msg.'
                </div>';
    }
    else if($type == 'Warning')
    {
        $html = '<div class="alert alert-warning alert-dismissable">
                    <i class="fa fa-warning"></i>
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <b>'.$type.' : </b> '.$msg.'
                </div>';
    }
    else if($type == 'Info')
    {
        $html = '<div class="alert alert-info alert-dismissable">
                    <i class="fa fa-info-circle"></i>
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <b>'.$type.' : </b> '.$msg.'
                </div>';
    }
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
    return $html;
}  
if((isset($_POST['get_alert_div'])) && ($_POST['get_alert_div'] == 'get_alert_div'))
{
    echo alert_msg($_POST['msg'],$_POST['type']);
    exit;
}  
if((isset($_POST['create_list'])) && ($_POST['create_list'] == 'create_list'))
{
    $sel_user = mysql_query("SELECT * FROM `".$prefix_table."users` WHERE `userid` = '".$_POST['user_id']."'");
    $row_user = mysql_fetch_assoc($sel_user); 
    if($row_user == '')
    {
        $_SESSION['msg_type'] = 'Error';
        $_SESSION['msg'] = "Select User First";
        echo '<script>window.location.href="importer.php"</script>';
        exit;
    }
    $inst_list = "INSERT INTO `".$prefix_table."lists` SET `name` = '".$_POST['list_name']."', `ownername` = '".$_POST['owner_name']."', `owneremail` = '".$_POST['owner_email']."', `bounceemail` = '".$_POST['BounceEmail']."', `replytoemail` = '".$_POST['ReplyToEmail']."', `bounceusername` = '',`bounceserver`= '',`bouncepassword`='',`imapaccount`= '0',`subscribecount`='0',`unsubscribecount`='0',`bouncecount`='0',`processbounce`='0',`agreedelete`='1',`agreedeleteall`='0',`visiblefields`='emailaddress,subscribedate,format,status,confirmed',`format` = 'b', `notifyowner` = '1', `createdate` = '".strtotime('now')."', `ownerid` = '".$_POST['user_id']."'"; 
    
    $inst_list_exe = mysql_query($inst_list);
    if($inst_list_exe > 0) {
        $insertedListId = mysql_insert_id();
        $selfieldids = "SELECT `fieldid` AS `fields`  FROM `".$prefix_table."customfields`";
        $val = "";
        $selfieldids_exe = mysql_query($selfieldids);
        if ( $selfieldids_exe > 0 ) {
            while( $fieldids = mysql_fetch_assoc($selfieldids_exe) ) {
                $val .=  '("'.$fieldids['fields'].'" , "'.$insertedListId.'" ),';
            }
            $val = substr_replace( $val, "", -1 );
        } 
        $instCustomFieldList = mysql_query("INSERT INTO `".$prefix_table."customfield_lists` ( `fieldid` , `listid` ) VALUES ".$val); 
    }
	mysql_close($config);
    if($instCustomFieldList > 0)
    {
        $_SESSION['msg_type'] = 'Success';
        $_SESSION['msg'] = "Contect List Created Successfully";
        echo '<script>window.location.href="importer.php?idUser='.$_POST['user_id'].'"</script>';
        exit;
    }
    else
    {
        $_SESSION['msg_type'] = 'Error';
        $_SESSION['msg'] = "Unable To Create Contect List";
        echo '<script>window.location.href="importer.php"</script>';
        exit;
    }
}

if((isset($_POST['importFile'])) && ($_POST['importFile'] != ''))
{
    if(isset($_FILES['contect_file']))
    {
        if($_FILES['contect_file']['error'] == 0)
        {
            move_uploaded_file($_FILES['contect_file']['tmp_name'], 'temp/'.$_FILES['contect_file']['name']);
        }
        else
        {
            $_SESSION['msg_type'] = 'Error';
            $_SESSION['msg'] = "Unable To Upload File!!!.. Try Again";
            echo '<script>window.location.href="importer.php"</script>';
            exit;
        }
    }
    $datedata = strtotime("now");
    $j = $k = 0;
    //$insert_count = 0;
    $rs1 = $bad = 0;
    $comma = '';
    $valid_email = array();
    $rs = 399;
    $totals = 0;
	$subs_inst_val_arr = array();
    $subs_sql = "INSERT IGNORE INTO`".$prefix_table."list_subscribers` ( `listid`,`format`,`confirmed`,`confirmcode`,`confirmdate`,`subscribedate`,`emailaddress`,`domainname`,`requestdate`) values";
    if(($handle1 = fopen("temp/".$_FILES['contect_file']['name'], "r")) !== FALSE) 
    {
        while(($data = fgetcsv($handle1, 1024000, $_POST['field_seperator'])) !== FALSE) 
        {

           
			$domain = array();
			$email = trim($data[0]);
			$regex = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/"; 
			if(preg_match( $regex, $email ))
			{
				$comma = '';
				if($j > 0)
				{
					$comma = " ,";
				}
				$domain = explode('@', $data[0]); 
				$subs_sql1 .= $comma." ('".$_POST['con_list_sel']."','h','1','".randString(32)."','".$datedata."','".$datedata."','".$email."','@".$domain[1]."', '".$datedata."')";
				if($k >= $rs)
				{
					//$subs_inst_val_arr[] = $subs_sql.$subs_sql1;
					$queries = $subs_sql.$subs_sql1; 
					$inst_sub_list = mysql_query($queries);
                    $total_aff = 0;
					$total_aff = mysql_affected_rows();
                    if($total_aff != -1)
                    {
                        $totals += $total_aff;
                    }
					$subs_sql1 = '';
					$j = -1;
					$rs = $rs+400;
				}
				$j ++;
				$k ++;
            }
            else
            {
                $bad ++;
            }
            
            
           // $i++;
		   
        } 
        //echo $totals;
        //echo $subs_sql.$subs_sql1; die;
        if($subs_sql1 != '')
        {
            $inst_sub_list = mysql_query($subs_sql.$subs_sql1);
			$total_aff = mysql_affected_rows();
            if($total_aff != -1)
            {
    			$totals += $total_aff;
            }
        }
       /* $co = mysql_query("SELECT count(*) as subsCount FROM `".$prefix_table."list_subscribers` WHERE `listid` =  '".$_POST['con_list_sel']."'"); 
        $co_row = mysql_fetch_assoc($co);*/

        if(mysql_affected_rows() > 0)
        {
           $subcount_sql = "update `".$prefix_table."lists` set `subscribecount` = `subscribecount` + $totals where `listid` = '".$_POST['con_list_sel']."'"; 
            mysql_query($subcount_sql); 
        }

		
		mysql_close($config);
        fclose($handle);
        unlink('temp/'.$_FILES['contect_file']['name']);
        if($inst_sub_list > 0)
        {
            $_SESSION['msg_type'] = 'Success';
            $_SESSION['msg'] = $k."Records Inserted ,".$bad.' Bad Records Found';
            echo '<script>window.location.href="importer.php"</script>';
            exit;
        }
        else
        {
            $_SESSION['msg_type'] = 'Error';
            $_SESSION['msg'] = "Unable To Import File";
            echo '<script>window.location.href="importer.php"</script>';
            exit;
        }
    }
    else
    {
        $_SESSION['msg_type'] = 'Warning';
        $_SESSION['msg'] = "Give Permissions TO <b>'alphajet'</b> Folder ";
        echo '<script>window.location.href="importer.php"</script>';
        exit;
    }
}

if((isset($_POST['suppression'])) && ($_POST['suppression'] == 'suppression'))
{
    $i = 0;
    $total = 0;
    $list_fetch_query = "SELECT * from `".SENDSTUDIO_TABLEPREFIX."lists` ";
    $exe_list = mysql_query($list_fetch_query);
    while($fetch_list = mysql_fetch_assoc($exe_list)){
        $i = 0;
        $query1 = "update ".SENDSTUDIO_TABLEPREFIX."list_subscribers sub inner join ".SENDSTUDIO_TABLEPREFIX."banned_emails ban on sub.domainname = ban.emailaddress set sub.`bounced`='1' where ban.list = 'g' AND sub.`bounced`='0' AND sub.listid = '".$fetch_list['listid']."'"; 
        $query1_exe = mysql_query($query1);
        if($query1_exe > 0)
        {
            $i = mysql_affected_rows();
        }
        
        $query2 = "update ".SENDSTUDIO_TABLEPREFIX."list_subscribers sub inner join ".SENDSTUDIO_TABLEPREFIX."banned_emails ban on sub.domainname = ban.emailaddress set sub.`bounced`='1' where ban.list = '".$fetch_list['listid']."' AND sub.`bounced`='0' AND sub.listid = '".$fetch_list['listid']."'";
        $query2_exe = mysql_query($query2);
        if($query2_exe > 0)
        {
            $i += mysql_affected_rows();
        }
        
        $query3 = "update ".SENDSTUDIO_TABLEPREFIX."list_subscribers sub inner join ".SENDSTUDIO_TABLEPREFIX."banned_emails ban on sub.emailaddress = ban.emailaddress set sub.`bounced`='1' where ban.list = 'g' AND sub.`bounced`='0' AND sub.listid = '".$fetch_list['listid']."'";
        $query3_exe = mysql_query($query3);
        if($query3_exe > 0)
        {
            $i += mysql_affected_rows();
        }
        
        $query4 = "update ".SENDSTUDIO_TABLEPREFIX."list_subscribers sub inner join ".SENDSTUDIO_TABLEPREFIX."banned_emails ban on sub.emailaddress = ban.emailaddress set sub.`bounced`='1' where ban.list = '".$fetch_list['listid']."' AND sub.`bounced`='0' AND sub.listid = '".$fetch_list['listid']."'";
        $query4_exe = mysql_query($query4);
        if($query4_exe > 0)
        {
            $i += mysql_affected_rows();
        }
        $update_list_bounce = mysql_query("UPDATE `".SENDSTUDIO_TABLEPREFIX."lists` SET bouncecount = '".$i."' WHERE listid = '".$fetch_list['listid']."'");
        $total = $total+$i;
        
    }
    mysql_close($config);
    echo $total;
    exit;
}
?>
