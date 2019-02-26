<html>
	<head></head>
	<style>
		.container{
			height: 500px;
			width: 800;
			background: black;
			margin: 0px auto;
		}

		hr{
			width: 50%;
		}

	</style>
	<body style="background: white; color: white; font-size: 13px;">
		<div class="container">
			<div class="row">
				<div class="col-md-12" style="text-align:center;">
					<img src="{{asset('public/img/LG1.png')}}" alt="" width="200px;">
				</div>
			</div>
			<div class="row">
				<div class="col-md-2"></div>
				<div class="col-md-8">
					<h1 style="text-align:center; font-size:27px; margin-top:-2% !important;">{{$title}}</h1>
					<hr>
					<p style="text-align: center; font-size:18px;">
						Saludos, <br>
						{{$data}}
					</p>
				</div>
				<div class="col-md-2"></div>
			</div>
		</div>
	</body>
</html>
