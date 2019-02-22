<html>
	<head></head>
	<body style="background: white; color: black; font-size: 13px;">
		<h1>{{$title}}</h1>
		<p>
			Saludos, <br>
			{{$data}}
			<br>
         @if(isset($data2))
            {{$data2}}
         @endif
		</p>
	</body>
</html>
