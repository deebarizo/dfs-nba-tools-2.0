@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Update Player - NBA</h2>
		</div>
	</div>
	<div class="row">
		{!!	Form::open(['url' => '']) !!}
			<div class="col-lg-6"> 
				<div class="form-group">
					{!! Form::label('player', 'Find a Player:') !!}
					{!! Form::text('player', null, ['class' => 'form-control']) !!}
				</div>
			</div>
		{!!	Form::close() !!}
	</div>

	<script>

		$('#player').autocomplete({
			source: 'get_player_name_autocomplete/nba',
			minLength: 1,
			select: function (e, ui) {
				window.location = ui.item.url;
			}
		});

	</script>
@stop