<html>
	<head></head>
	<style>
		.container{
			height: 400px;
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
				<div class="col-md-2"></div>
				<div class="col-md-8">
					<img src="{{asset('public/img/Cloudshield.png')}}" alt="" width="150px;">
				</div>
				<div class="col-md-2"></div>
			</div>
			<div class="row">
				<div class="col-md-2">

				</div>
				<div class="col-md-8">
					<h1 style="text-align:center; font-size:24px;">{{$title}}</h1>
					<hr>
					<p style="text-align:center; font-size:18px;">
						Saludos, <br>
						{{$data}}
						<br>
			         @if(isset($data2))
			            {{$data2}}
			         @endif
					</p>
				</div>
				<div class="col-md-2">

				</div>
			</div>
		</div>
	</body>
</html>
