@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>nbawowy! (Form)</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<form class="form" style="margin: 0 0 10px 0">

				<label>Name</label>
				<input class="form-control name" type="text" style="width: 15%; margin-bottom: 10px">		

				<label>Starting Date</label>
				<input class="form-control starting-date" type="text" value="{{ $beginningOfSeasonDate }}" style="width: 10%; margin-bottom: 10px">

				<label>Ending Date</label>
				<input class="form-control ending-date" type="text" value="{{ $yesterdayDate }}" style="width: 10%; margin-bottom: 10px">	

				<label>Player Off</label>
				<input class="form-control player-off" type="text" style="width: 15%; margin-bottom: 20px">

				<input style="width: 10%; outline: none" class="btn btn-primary submit-info" value="Submit">
			
			</form>
		</div>
	</div>

	<script type="text/javascript">

		$(document).ready(function() {
			$('input.btn.submit-info').on('click', function() {
				var name = $('input.name').val();
				name = name.replace(' ', '_');

				var startingDate = $('input.starting-date').val();
				var endingDate = $('input.ending-date').val();

				var playerOff = $('input.player-off').val();
				playerOff = playerOff.replace(' ', '_');

				window.open('/nbawowy/'+name+'/'+startingDate+'/'+endingDate+'/off/'+playerOff, '_blank');
			});
		});

	</script>
@stop