@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-9">
			<h2>Classifications of Projected Fpts</h2>

			<table class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Order</th>
						<th>Min Mpg</th>
						<th>Min Fppg</th>
						<th>Max Fppg</th>
						<th>Abs Link</th>
						<th>No Abs Link</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($playerGroups as $key => $playerGroup)
						<tr>
							<td>{{ $key }}</td>
							<td>{{ $mpgMin }}</td>
							<td>{{ $playerGroup['min'] }}</td>
							<td>{{ $playerGroup['max'] }}</td>
							<td><a target="_blank" href="/studies/correlations/spreads_and_player_fpts_error/{{ $mpgMin }}/{{ $playerGroup['min'] }}/{{ $playerGroup['max'] }}/ABS">Abs Link</a></td>
							<td><a target="_blank" href="/studies/correlations/spreads_and_player_fpts_error/{{ $mpgMin }}/{{ $playerGroup['min'] }}/{{ $playerGroup['max'] }}/NOABS">No Abs Link</a></td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
@stop