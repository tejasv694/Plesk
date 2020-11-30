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
	                    SMTPMailers Email Suppression Addon
	                </h3>
	                </div>
	                <div class="panel-body">
	                	 <a  href="../../index.php" class="btn btn-default"><img src="css/BackIcon.png" width="25" height="25"> Go To Home</a>
	                	 <a  href="importer.php" class="btn btn-default pull-right"> Go To Importer</a>
	                	<div class="row m-t-100">
							<div class="col-md-12">
								<div class="btn alert alert-info text-center" id="suppression-btn" style="width:100%" role="alert">
							      <strong>Start Suppression!!!</strong>
							    </div>
							</div>
						</div>
						<div class="row m-t-10 loading" style="display:none">
							<div class="col-md-12">
								<div class="col-md-3"></div>
								<div class="col-md-6 text-center">
									<img src="css/ajax-loader.gif">
								</div>
								<div class="col-md-3"></div>
							</div>
						</div>
						<div class="row m-t-10 result" style="display:none">
							<div class="col-md-12">
								<div class="col-md-3"></div>
								<div class="col-md-6">
									<div class="well">
										<div class="row">
											<div class="col-md-6" style="margin-left:70px;">Number Of Email Suppresed :</div>
											<div class="col-md-3">
												<span class="badge"></span>
											</div>
										</div>
									</div>
								</div>
								<div class="col-md-3"></div>
							</div>
						</div>
	                </div>
	            </div>
			</div>

		</div>
	</body>
	<script>
	$(document).ready(function(){
		$('#suppression-btn').on('click', function(){
			for (var i = 100; i >= 10; i--) {
				$(this).parents('div.row').css('margin-top',i);
			};
			if($('.result').is(':visible'))
			{
				$('.result').css('display', 'none');
			}
			$('.loading').show();
			$.post("action.php", {suppression: 'suppression'} , function(data){
				$('.badge').text(data).promise().done(function(){
					$('.loading').hide();
					$(this).parents('div.row').fadeIn('slow');
				});
			});
		});
	});
	</script>

</html>
<?php
mysql_close($config);
?>
