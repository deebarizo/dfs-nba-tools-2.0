@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>nbawowy! (Results)</h2>

			<strong>Name: </strong> {{ $name }}</br>
			<strong>Start Date: </strong> {{ $startDate }}</br>
			<strong>End Date: </strong> {{ $endDate }}</br>
			<strong>Player On: </strong> {{ $playerOnInView }}</br>
			<strong>Player Off: </strong> {{ $playerOffInView }}</br></br>

			<table class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Min</th>
						<th>2PM</th>
						<th>2PA</th>
						<th>2P%</th>
						<th>3PM</th>
						<th>3PA</th>
						<th>3P%</th>
						<th>FTM</th>
						<th>FTA</th>
						<th>FT%</th>
						<th>TRB</th>
						<th>AST</th>
						<th>TOV</th>
						<th>STL</th>
						<th>BLK</th>
						<th>FPPM</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>{{ numFormat($stats['minutes']) }}</td>
						<td>{{ $stats['2p_made'] }}</td>
						<td>{{ $stats['2p_total'] }}</td>
						<td>{{ $stats['2p_percentage'] }}</td>
						<td>{{ $stats['3p_made'] }}</td>
						<td>{{ $stats['3p_total'] }}</td>
						<td>{{ $stats['3p_percentage'] }}</td>
						<td>{{ $stats['ft_made'] }}</td>
						<td>{{ $stats['ft_total'] }}</td>
						<td>{{ $stats['ft_percentage'] }}</td>
						<td>{{ $stats['trb'] }}</td>
						<td>{{ $stats['ast'] }}</td>
						<td>{{ $stats['tov'] }}</td>
						<td>{{ $stats['stl'] }}</td>
						<td>{{ $stats['blk'] }}</td>
						<td>{{ $stats['fppm'] }}</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
@stop