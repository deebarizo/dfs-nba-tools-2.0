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

			<h2>{{ $name }} (NBA Player)</h2>

			<p><strong>Links:</strong> <a target="_blank" href="http://www.google.com/search?q={{ $name }}+Rotoworld">RT</a> | <a target="_blank" href="http://www.google.com/search?q={{ $name }}+Basketball+Reference">BR</a> | <a target="_blank" href="http://www.google.com/search?q={{ $name }}+ESPN">ESPN</a> -- <a target="_blank" href="/daily_fd_filters/{{ $playerInfo['player_id'] }}/create"><span {!! $noFilterSpan !!} class="glyphicon glyphicon-filter" aria-hidden="true"></span></a></p>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<h3>Current Player Filter</h3>

			<table style="margin-bottom: 5px" class="table table-striped table-bordered table-hover table-condensed">
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

			<a class="previous-filters-link" href="#">Previous Filters</a>

			<table style="display:none; margin-top: 8px" id="previous-fd-filters" class="table table-striped table-bordered table-hover table-condensed">
			  	<thead>
				  	<tr>
				  		<th>Create</th>
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
				  	@foreach ($previousFdFilters as $previousFdFilter)
					  	<tr>
					  		<td>
					  			<a target="_blank" href="/daily_fd_filters/{{ $playerInfo['player_id'] }}/create/{{ $previousFdFilter->id }}">
					  				<span class="glyphicon glyphicon-filter" aria-hidden="true"></span>
				  				</a>
			  				</td>
						    <td>{{ $previousFdFilter->filter }}</td>
						    <td>{{ $previousFdFilter->playing }}</td>
						    <td>{{ $previousFdFilter->fppg_source }}</td>
						    <td>{{ $previousFdFilter->fppm_source }}</td>
						    <td>{{ $previousFdFilter->cv_source }}</td>
						    <td>{{ $previousFdFilter->mp_ot_filter }}</td>
						    <td>{{ $previousFdFilter->dnp_games }}</td>
						    <td>{{ $previousFdFilter->notes }}</td>
					  	</tr>
				  	@endforeach
			  	</tbody>
			</table>

		</div>
	</div>

	<hr>

	<div class="row">
		<div class="col-lg-12">
			<h3>Overviews</h3>

			<h4>All</h4>
			
			<table id="overview-all" style="width: 50%" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th style="width: 33%">MPG</th>
						<th style="width: 33%">FPPM</th>
						<th style="width: 34%">FPG</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>{{ numFormat($overviews['all']['mppg']) }}</td>
						<td>{{ numFormat($overviews['all']['fppm']) }}</td>
						<td>{{ numFormat($overviews['all']['fppg']) }}</td>
					</tr>
				</tbody>
			</table>	

			@foreach ($overviews as $yearKey => $overview)
				@if ($yearKey != 'all')
					<h4>{{ $yearKey }}</h4>
					
					<table id="overview-{{ $yearKey }}" style="width: 50%" class="table table-striped table-bordered table-hover table-condensed">
						<thead>
							<tr>
								<th style="width: 33%">MPG</th>
								<th style="width: 33%">FPPM</th>
								<th style="width: 34%">FPG</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>{{ numFormat($overview['mppg']) }}</td>
								<td>{{ numFormat($overview['fppm']) }}</td>
								<td>{{ numFormat($overview['fppg']) }}</td>
							</tr>
						</tbody>
					</table>	
				@endif
			@endforeach

			<!-- <div class="fpts-profile-chart"></div> -->

		</div>

	</div>

	<hr>

	@foreach ($boxScoreLines as $yearKey => $year)
		<div class="row">
			<div class="col-lg-12">
				<h3>{{ $yearKey }}</h3>

				<h4>Game Log</h4>

				<table style="font-size: 100%" id="game-log-{{ $yearKey }}" class="table table-striped table-bordered table-hover table-condensed">
					<thead>
						<tr>
							<th>Date</th>
							<th>Team</th>
							<th>Opp</th>
							<th>Score</th>
							<th>Line</th>
							<th>Links</th>
							<th>Role</th>
							<th>Mp</th>
							<th>Ot</th>
							<th>Fg</th>
							<th>3p</th>
							<th>Ft</th>
							<th>Or</th>
							<th>Dr</th>
							<th>Tr</th>
							<th>Ast</th>
							<th>Bl</th>
							<th>St</th>
							<th>Pf</th>
							<th>To</th>
							<th>Pt</th>
							<th>Usg</th>
							<th>Fdpts</th>
							<th>Fdppm</th>
							<th>Fdsh</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($year as $row)
						    <tr>
						    	<td>{{ $row->date }}</a></td>
						    	<td>{{ $row->team_of_player }}</td>
						    	<td>{{ $row->is_road_game.$row->opp_team }}</td>
						    	<td>{!! $row->game_score !!}</td>
						    	<td>{{ $row->line }}</td>
						    	<td><a target="_blank" href="{!! $row->link_br !!}">BR</a> | <a target="_blank" href="http://popcornmachine.net/gf?date={!! $row->date_pm !!}&game={!! $row->road_team_abbr_pm !!}{!! $row->home_team_abbr_pm !!}">PM</a></td>
						    	<td>{{ $row->role }}</td>
						    	@if ($row->status == 'Played')
							    	<td>{{ $row->mp }}</td>
							    	@if ($row->ot_periods > 0)
								    	<td><strong>{{ $row->ot_periods }}</strong></td>
								   	@else
								   		<td>{{ $row->ot_periods }}</td>
								   	@endif
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
							    	<td>{{ numFormat($row->usg, 1) }}</td>
							    	<td>{{ numFormat($row->fdpts) }}</td>
							    	@if ($row->mp != 0)
								    	<td>{{ numFormat($row->fdppm) }}</td>
								   	@else
								   		<td>0.00</td>
								   	@endif
								   	<td>{{ $row->fdsh }}%</td>
							    @else
							    	<td style="text-align: center" colspan="20">{{ $row->status }}</td>
							    @endif							    	
						    </tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>

		<hr>
	@endforeach

	<script>

		$(document).ready(function() {
			$( ".previous-filters-link" ).on('click', function() {
				$( "#previous-fd-filters" ).toggle();
			});

			$(function() {
		        $('.fpts-profile-chart').highcharts({
		            chart: {
		                type: 'column'
		            },
		            title: {
		                text: '2015 Fpts Profile'
		            },
		            xAxis: {
		                categories: ['PTS', '2P', '3P', 'FT', 'TRB', 'ORB', 'DRB', 'AST', 'TO', 'STL', 'BLK']
		            },
		            yAxis: {min: -20, max: 70},
		            credits: {
		                enabled: false
		            },
		            legend: false,
		            series: [{
	                	data: <?php echo json_encode($fptsProfile['view']); ?>
	                }],
	                plotOptions: {
	                	column: {colorByPoint: true}
	                }
		        });
		    });

		});

	</script>
@stop