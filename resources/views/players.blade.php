@extends('master')

@section('content')

	<div class="row">
		<div class="col-lg-12">
			<?php
				$noFilterSpan = ''; 

				if (!isset($player->filter)) { 
					$noFilterSpan = 'style="color: red"'; 
				} 
			?>

			<h2>Players ({{ $playerInfo['name'] }}</a>)</h2>

			<p><strong>Links:</strong> <a target="_blank" href="http://www.google.com/search?q={{ $playerInfo['name'] }}+Rotoworld">RT</a> | <a target="_blank" href="http://www.google.com/search?q={{ $playerInfo['name'] }}+Basketball+Reference">BR</a> | <a target="_blank" href="http://www.google.com/search?q={{ $playerInfo['name'] }}+ESPN">ESPN</a> -- <a target="_blank" href="/daily_fd_filters/{{ $playerInfo['player_id'] }}/create"><span {!! $noFilterSpan !!} class="glyphicon glyphicon-filter" aria-hidden="true"></span></a> <a target="_blank" href="/daily_fd_filters/{{ $playerInfo['player_id'] }}/edit"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a></p>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<h3>Current Player Filter</h3>

			<table class="table table-striped table-bordered table-hover table-condensed">
			  	<thead>
				  	<tr>
					    <th>Filter</th>
					    <th>Playing</th>
					    <th>FPPG</th>
					    <th>FPPM</th>
					    <th>CV</th>
					    <th>MP OT</th>
					    <th>DNP</th>
					    <th>Notes</th>
				  	</tr>
				</thead>
				<tbody>
				  	@if (isset($player->filter))
					  	<tr>
						    <td>{{ $player->filter->filter }}</td>
						    <td>{{ $player->filter->playing }}</td>
						    <td>{{ $player->filter->fppg_source }}</td>
						    <td>{{ $player->filter->fppm_source }}</td>
						    <td>{{ $player->filter->cv_source }}</td>
						    <td>{{ $player->filter->mp_ot_filter }}</td>
						    <td>{{ $player->filter->dnp_games }}</td>
						    <td>{{ $player->filter->notes }}</td>
					  	</tr>
				  	@else
					  	<tr>
						    <td>None</td>
						    <td>None</td>
						    <td>None</td>
						    <td>None</td>
						    <td>None</td>
						    <td>None</td>
						    <td>None</td>
					  	</tr>
				  	@endif
			  	</tbody>
			</table>	
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
								<th>Line</th>
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
							    	<td>{{ $row->line }}</td>
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
								    	@if ($row->mp != 0)
									    	<td>{{ numFormat($row->pts_fd / $row->mp) }}</td>
									   	@else
									   		<td>0.00</td>
									   	@endif
								    @else
								    	<td style="text-align: center" colspan="18">{{ $row->bs_status }}</td>
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