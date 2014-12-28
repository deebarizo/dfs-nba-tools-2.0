@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Lineup Builder - FD NBA</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<h3>{{ $date }}</h3>
		</div>
	</div>

	<div class="row">
		@foreach ($lineups as $key => $lineup)
			<div class="col-lg-4">
				<table data-player-pool-id="{{ $lineup['metadata']->player_pool_id }}" 
					   data-hash="{{ $lineup['metadata']->hash }}" 
					   data-total-salary="{{ $lineup['metadata']->total_salary }}" 
					   class="table 
					   		  table-striped 
					   		  table-bordered 
					   		  table-hover 
					   		  table-condensed 
					   		  lineup">
					<thead>
						<tr>
							<th>Pos</th>								
							<th>Name</th>
							<th>Sal</th>
						</tr>				
					</thead>
					<tbody>
						@foreach ($lineup['players'] as $player)
							<tr>
								<td>{{ $player->position }}</td>
								<td>{{ $player->name }}</td>
								<td>{{ $player->salary }}</td>
							</tr>
						@endforeach
						<td class="edit-lineup-td" style="text-align: center" colspan="2">#{{ $key + 1 }} | ${{ $lineup['metadata']->lineup_buy_in }} ({{ $lineup['metadata']->lineup_buy_in_percentage }}%) | <a href="lineup_builder/{{ $date}}/{{ $lineup['metadata']->hash }}" class="edit-lineup-players-link">Edit Players</a></td>
						<td style="color: green"><strong>{{ $player->total_salary }}</strong></td>
					</tbody>
				</table>
			</div>
		@endforeach
	</div>
@stop