@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily FD NBA (Solver With Top Plays) | {{ $date }} {{ $timePeriod }}</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<h4>Lineups</h4>
		</div>

		@foreach ($lineups as $lineup)
			<div class="col-lg-3">
				

				<table class="table table-striped table-bordered table-hover table-condensed">
					<thead>
						<tr>
							<th>Pos</th>
							<th>Name</th>
							<th>Sal</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($lineup['roster_spots'] as $rosterSpot)
							<tr>
								<td>{{ $rosterSpot->position }}</td>
								<td>{{ $rosterSpot->name }}</td>
								<td>{{ $rosterSpot->salary }}</td>
							</tr>
						@endforeach

						<tr>
							<td style="text-align: center" colspan="2">&nbsp;</td>
							<td style="color: green"><strong>{{ $lineup['total_salary'] }}</strong></td>
						</tr>
					</tbody>
				</table>
			</div>
		@endforeach	
	</div>
@stop