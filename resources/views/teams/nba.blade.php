@extends('master')

@section('content')

	<div class="row">
		<div class="col-lg-12">
			<h2>NBA Teams</h2>

			<ul>
				@foreach ($teams as $team)
					<li><a href="/teams/nba/{{ $team->abbr_br }}">{{ $team->name_br }}</a></li>
				@endforeach
			</ul>
		</div>
	</div>

@stop