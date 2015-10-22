@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>NBA Games</h2>

			<table class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Date</th>
						<th>Matchup</th>
						<th>Result</th>
						<th>Line</th>
						<th>Links</th>
						<th>Ot</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($games as $game)
						<tr>
							<td>{{ $game->date }}</td>
							<td>{{ $game->matchup }}</td>
							<td>{{ $game->result }}</td>
							<td>{{ $game->line }}</td>
							<td>{!! $game->links !!}</td>
							<td>{!! $game->ot !!}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
@stop