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
		<div class="col-lg-6">
			<h4>Available Players</h4>

			<table id="available-players" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Pos</th>					
						<th>Name</th>
						<th>Salary</th>
						<th>Update</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($players as $player)
						<tr class="available-player-row" data-player-pool-id="{{ $player->player_pool_id }}" data-player-id="{{ $player->player_id }}">
							<td class="available-player-position">{{ $player->position }}</td>
							<td class="available-player-name">{{ $player->name }}</td>
							<td class="available-player-salary">{{ $player->salary }}</td>
							<td class="available-player-update" style="width: 10%"><a class="update-available-player-link" href=""><div class="circle-plus-icon"><span class="glyphicon glyphicon-plus"></span></div></a></td>
						</tr>		
					@endforeach		
				</tbody>
			</table>
		</div>

		<div class="col-lg-6">
			<h4>Lineup</h4>

			<table id="lineup" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Pos</th>					
						<th>Name</th>
						<th>Salary</th>
						<th>Remove</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($fdPositions as $fdPosition)
						<tr class="lineup-row lineup-player-row" data-player-pool-id="" data-player-id="">
							<td style="width: 10%" class="lineup-player-position">{{ $fdPosition }}</td>
							<td class="lineup-player-name"></td>
							<td style="width: 15%" class="lineup-player-salary"></td>
							<td style="width: 10%"><a href="" class="remove-lineup-player-link"></a></td>
						</tr>
					@endforeach
					<tr class="lineup-row">
						<td colspan="2" style="text-align: center">$<span class="default-lineup-buy-in-amount">{{ $defaultLineupBuyIn}}</span> | <a href="" class="edit-default-lineup-buy-in-link">Edit</a></td>
						<td><span class="lineup-salary-total">0</span></td>
						<td></td>
					</tr>	
				</tbody>
			</table>
		</div>
	</div>

	<script src="/js/lineup_builder.js"></script>
@stop