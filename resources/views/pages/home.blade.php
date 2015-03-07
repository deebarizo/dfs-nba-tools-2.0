@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-6">
			<h2>Player Pools</h2>

			<table class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Date</th>
						<th>Site</th>
						<th>Sport</th>
						<th>Time Period</th>
						<th>Buy In</th>
						<th>Link</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($playerPools as $playerPool)
						<tr>
							<td>{{ $playerPool['date'] }}</td>
							<td>{{ $playerPool['site'] }}</td>
							<td>{{ $playerPool['sport'] }}</td>
							<td>{{ $playerPool['time_period'] }}</td>
							<td>{{ $playerPool['buy_in'] }}</td>
							<td><a href="/daily/{{ $playerPool['site_in_url'] }}/{{ $playerPool['sport_in_url'] }}/{{ $playerPool['time_period_in_url'] }}/{{ $playerPool['date'] }}">Link</a></td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
@stop