<?php include 'config.php';?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Email Marketer</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
</head>

<body>
    <div class="container">
        <div class="row main-row">
            <div class="panel panel-primary">
                <div class="panel-heading text-center">
                    <h3>
                        SMTPMailers Email Marketer Addon
                    </h3>
                </div>
                <div class="panel-body">
                    <a  href="../../index.php" class="btn btn-default"><img src="css/BackIcon.png" width="25" height="25"> Go To Home</a>
                    <a  href="suppression.php" class="btn btn-default pull-right"> Go To Suppression</a>
                    <div class="text-center">
                        <div class="row m-b-20">
                            <div class="col-md-3"></div>
                            <div class="col-md-2">
                                <label class="control-label m-t-10">Select User For</label>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control userselect">
                                    <option value="0">Select User</option>
                                    <?php

$sel = mysql_query("SELECT * FROM `" . $prefix_table . "users`");
while ($user_row = mysql_fetch_assoc($sel)) {
	if ((isset($_GET['idUser'])) && ($_GET['idUser'] != '')) {
		$selected = '';
		if ($user_row['userid'] == $_GET['idUser']) {
			$selected = 'selected = "selected"';
		}
	}
	echo $op = "<option value='" . $user_row['userid'] . "' " . $selected . ">" . $user_row['username'] . "</option>";
}

?>
                                </select>
                            </div>
                        </div>
                    </div>
                <div id="alertmsg"></div>
            <div class="panel-body whole-body <?php if ((isset($_GET['idUser'])) && ($_GET['idUser'] != '')) {} else {echo 'blind';}
?>">
                <div class="panel panel-default create_list_head">
                    <div class="panel-heading">
                        Create list
                    </div>
                </div>
                <div class="panel-body create_list_body blind">
                    <form action="action.php" method="POST" data-toggle="validator" novalidate >
                        <input type="hidden" name="user_id" value="<?php if ((isset($_GET['idUser'])) && ($_GET['idUser'] != '')) {echo $_GET['idUser'];}
?>">
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label class="m-t-10">List Name : </label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" name="list_name" value="" class="form-control" required>
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label class="m-t-10">Owner Name : </label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" name="owner_name" value="" class="form-control" required>
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label class="m-t-10">Owner Email : </label>
                            </div>
                            <div class="col-md-9">
                                <input type="email" name="owner_email" value="" class="form-control" required>
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-3">
                                <label class="m-t-10">List Reply-To Email : </label>
                            </div>
                            <div class="col-md-9">
                                <input type="email" name="ReplyToEmail" value="" class="form-control" required>
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label class="m-t-10">List Bounce Email : </label>
                            </div>
                            <div class="col-md-9">
                                <input type="email" name="BounceEmail" value="" class="form-control" required>
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-primary" name="create_list" value="create_list">Create List</button>
                        <button type="reset" class="btn btn-danger pull-right">Reset</button>
                    </form>
                <hr>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Import Contacts From File
                    </div>
                </div>
                <div class="panel-body">
                    <form action="action.php" method="POST" enctype="multipart/form-data" data-toggle="validator" novalidate>
                        <input type="hidden" name="userId" value="<?php if ((isset($_GET['idUser'])) && ($_GET['idUser'] != '')) {echo $_GET['idUser'];}
?>">
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label class="m-t-10">Select Contact List : </label>
                            </div>
                            <div class="col-md-9">
                                <select name="con_list_sel" id="con_list_sel" class="form-control" placeholder="Select one list" required>
                                    <option value=""></option>
                                    <?php
$sel = "SELECT * FROM `" . $prefix_table . "lists`";
$sel_exe = mysql_query($sel);
if ($sel_exe > 0) {
	$option = '';
	while ($rwo = mysql_fetch_assoc($sel_exe)) {
		echo $option = '<option value="' . $rwo['listid'] . '">' . $rwo['name'] . '</option>';
	}
}
?>
                                </select>
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <hr>
                        <div class="selContectDiv blind">
                            <div class="form-group row">
                                <div class="col-md-3">
                                    <label class="m-t-10">Select Contact List : </label>
                                </div>
                                <div class="col-md-9">
                                    <select name="field_seperator" id="field_seperator" class="form-control" placeholder="Select one list">
                                        <option value=",">Comma(,)</option>
                                        <option value=";">Semi-colon(;)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-md-3">
                                    <label class="m-t-10">Browse Only '.csv' File : </label>
                                </div>
                                <div class="col-md-9">
                                    <input type="FILE" name="contect_file" accept=".csv" >
                                </div>
                            </div>
                            <hr>
                        </div>
                        <button type="submit" class="btn btn-primary" name="importFile" value="importFile">Upload List</button>
                        <button type="reset" class="btn btn-danger pull-right">Reset</button>
                    </form>
                </div>
            </div>
                </div>

            </div>

        </div>
    </div>
    <script>
    $(document).ready(function(){
        var session_msg = "<?php if ((isset($_SESSION['msg'])) && ($_SESSION['msg'] != '')) {echo $_SESSION['msg'];} else {echo '';}
?>";
        var msg_type = "<?php if ((isset($_SESSION['msg_type'])) && ($_SESSION['msg_type'] != '')) {echo $_SESSION['msg_type'];} else {echo '';}
?>";
        if(session_msg != '' && msg_type != '' )
        {
            $.ajax({
                type: "POST",
                url: 'action.php',
                data: {
                    get_alert_div : 'get_alert_div',
                    msg : session_msg,
                    type : msg_type
                },
                success : function(data){
                    if(data != '')
                    {
                        $('#alertmsg').html(data).hide().slideDown('slow');
                        setTimeout(function(){
                            $('#alertmsg').slideUp('slow');
                        },5000);
                    }
                }
            });
        }

        $('#con_list_sel').on('change',function(){
            if($(this).val() != '')
            {
                $('#field_seperator').parents('div.selContectDiv').slideDown('slow');
            }
            else
            {
                $('#field_seperator').parents('div.selContectDiv').slideUp('slow');
            }
        });


        $('.create_list_head').on('click', function(){
            $('.create_list_body').slideToggle('slow');
        });

        $('.userselect').on('change', function(){
            if($(this).val() != '0')
            {
                $('.whole-body').slideDown('slow');
                $('#hr-div').fadeOut(300);
                $('input[name="user_id"]').attr('value', $(this).val());
                $('input[name="userId"]').attr('value', $(this).val());
            }
            else
            {
                $('.whole-body').slideUp('slow');
                $('#hr-div').fadeIn(300);
                $('input[name="user_id"]').attr('value', '');
                $('input[name="userId"]').attr('value', '');
            }
        });
    });
    </script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/custom.js"></script>
    <script src="js/validator.min.js"></script>
</body>

</html>
<?php
mysql_close($config);
?>

