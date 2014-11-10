@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Players ({{ $stats2015[0]['name']}})</h2>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12">
			<h3>2014-2015 Game Log</h3>

			<table id="game-log-2015" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Date</th>
						<th>Home</th>
						<th>HS</th>
						<th>Road</th>
						<th>RS</th>
						<th>PM</th>
						<th>Role</th>
						<th>MP</th>
						<th>FGM-FGA</th>
						<th>3PM-3PA</th>
						<th>FTM-FTA</th>
						<th>ORB</th>
						<th>DRB</th>
						<th>TRB</th>
						<th>AST</th>
						<th>BLK</th>
						<th>STL</th>
						<th>PF</th>
						<th>TOV</th>
						<th>PTS</th>
						<th>FD</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($players as $player)
					    <tr>
					    	<td>{{ $stats2015->date }}</a></td>
					    	<td>{{ $stats2015->home_team_abbr }}</td>
					    	<td>{{ $stats2015->home_team_score }}</td>
					    	<td>{{ $stats2015->road_team_abbr }}</td>
					    	<td>{{ $stats2015->road_team_score }}</td>
					    	<td></td>
					    	<td>{{ $stats2015->vr_minus_1sd }}</td>
					    	<td>{{ $stats2015->cv }}</td>
					    	<td>{{ $stats2015->fppmPerGame }}</td>
					    	<td>{{ $stats2015->cvFppm }}</td>
					    	<td>{{ $stats2015->fppm_minus_1sd }}</td>
					    	<td>{{ $stats2015->fppg }}</td>
					    	<td>{{ $stats2015->fppg_minus_1sd }}</td>
					    </tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
    		$('#daily').dataTable({
    			"scrollY": "600px",
    			"paging": false,
    			"order": [[6, "desc"]]
    		});
		});
	</script>
@stop