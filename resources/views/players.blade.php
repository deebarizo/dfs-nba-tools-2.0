@extends('master')

@section('content')

	<div class="row">
		<div class="col-lg-12">
			<h2>Players ({{ $name }}</a>)</h2>

			<p><strong>Links:</strong> <a target="_blank" href="http://www.google.com/search?q={{ $name }}+Rotoworld">RT</a> | <a target="_blank" href="http://www.google.com/search?q={{ $name }}+Basketball+Reference">BR</a> | <a target="_blank" href="http://www.google.com/search?q={{ $name }}+ESPN">ESPN</a></p>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<h3>All</h3>

			<h4>Overview</h4>
			
			<table id="overview-all" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>MP</th>
						<th>FP</th>
						<th>CV</th>
						<th>FPPM</th>
						<th>CVPM</th>
						<th>USG</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>{{ $overviews['all']['mppg'] }}</td>
						<td>{{ $overviews['all']['fppg'] }}</td>
						<td>{{ $overviews['all']['cv'] }}</td>
						<td>{{ $overviews['all']['fppm'] }}</td>
						<td>{{ $overviews['all']['cv_fppm'] }}</td>
						<td>{{ $overviews['all']['usg'] }}</td>
					</tr>
				</tbody>
			</table>	
		</div>
	</div>

	<hr>

	@foreach ($stats as $yearKey => $year)
		@if (empty($year) === false && isset($overviews[$yearKey]) === true)
			<div class="row">
				<div class="col-lg-12">
					<h3>{{ $yearKey }}</h3>

					<h4>Overview</h4>
					
					<table id="overview-{{ $yearKey }}" class="table table-striped table-bordered table-hover table-condensed">
						<thead>
							<tr>
								<th>MP</th>
								<th>FP</th>
								<th>CV</th>
								<th>FPPM</th>
								<th>CVPM</th>
								<th>USG</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>{{ $overviews[$yearKey]['mppg'] }}</td>
								<td>{{ $overviews[$yearKey]['fppg'] }}</td>
								<td>{{ $overviews[$yearKey]['cv'] }}</td>
								<td>{{ $overviews[$yearKey]['fppm'] }}</td>
								<td>{{ $overviews[$yearKey]['cv_fppm'] }}</td>
								<td>{{ $overviews[$yearKey]['usg'] }}</td>
							</tr>
						</tbody>
					</table>					

					<h4>Game Log</h4>

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
								<th>OT</th>
								<th>FG</th>
								<th>3P</th>
								<th>FT</th>
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
								<th>FDPM</th>
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
								    	<td>{{ $row->ot_periods }}</td>
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
								    	<td>{{ numFormat($row->pts_fd / $row->mp, 2) }}</td>
								    @else
								    	<td style="text-align: center" colspan="17">{{ $row->bs_status }}</td>
								    @endif
							    </tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>

			<hr>
		@endif
	@endforeach
@stop