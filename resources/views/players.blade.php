@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Players ({{ $name }})</h2>
		</div>
	</div>
	@foreach ($stats as $yearKey => $year)
			<div class="row">
				<div class="col-lg-12">
					<h3>{{ $yearKey }} Game Log</h3>

					<table id="game-log-{{ $yearKey }}" class="table table-striped table-bordered table-hover table-condensed">
						<thead>
							<tr>
								<th>Date</th>
								<th>Home</th>
								<th>HS</th>
								<th>Road</th>
								<th>RS</th>
								<th>BR</th>
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
								<th>USG</th>
								<th>FD</th>
							</tr>
						</thead>
						<tbody>
							@foreach ($year as $row)
							    <tr>
							    	<td>{{ $row->date }}</a></td>
							    	<td>{{ $row->home_team_abbr_br }}</td>
							    	<td>{{ $row->home_team_score }}</td>
							    	<td>{{ $row->road_team_abbr_br }}</td>
							    	<td>{{ $row->road_team_score }}</td>
							    	<td><a target="_blank" href="{!! $row->link_br !!}">BR</a></td>
							    	<td><a target="_blank" href="http://popcornmachine.net/gf?date={!! $row->date_pm !!}&game={!! $row->road_team_abbr_pm !!}{!! $row->home_team_abbr_pm !!}">PM</a></td>
							    	<td>{{ $row->role }}</td>
							    	@if ($row->bs_status == 'Played')
								    	<td>{{ $row->mp }}</td>
								    	<td>{{ $row->fg }}-{{ $row->fga }}</td>
								    	<td>{{ $row->threep }}-{{ $row->threepa }}</td>
								    	<td>{{ $row->ft }}-{{ $row->fta }}</td>
								    	<td>{{ $row->orb }}</td>
								    	<td>{{ $row->drb }}</td>
								    	<td>{{ $row->trb }}</td>
								    	<td>{{ $row->ast }}</td>
								    	<td>{{ $row->blk }}</td>
								    	<td>{{ $row->stl }}</td>
								    	<td>{{ $row->pf }}</td>
								    	<td>{{ $row->tov }}</td>
								    	<td>{{ $row->pts }}</td>
								    	<td>{{ $row->usg }}</td>
								    	<td>{{ $row->pts_fd }}</td>
								    @else
								    	<td style="text-align: center" colspan="15">{{ $row->bs_status }}</td>
								    @endif
							    </tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
	@endforeach
@stop