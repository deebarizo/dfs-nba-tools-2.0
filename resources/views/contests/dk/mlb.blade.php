@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Contests - DK MLB - Last {{ $numOfContests }}</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<form class="form-inline" style="margin: 12px 0 5px 0">
				<label>Positions</label>
				<select class="form-control position-filter" style="width: 10%; margin-right: 20px">
				  	<option value="All">All</option>
				  	<option value="C">C</option>
				  	<option value="1B">1B</option>
				  	<option value="2B">2B</option>
				  	<option value="3B">3B</option>
				  	<option value="SS">SS</option>
				  	<option value="OF">OF</option>
				</select>
			</form>
		</div>
	</div>

	@foreach ($contests as $contest) 
		<div class="row">
			<div class="col-lg-12">
				<h3>{{ $contest->date }} | {{ $contest->name }}</h3>
			</div>

			<div class="col-lg-12">
				<table id="daily" class="table table-striped table-bordered table-hover table-condensed">
					<thead>
						<tr>
							<th>Name</th>
							<th>Pos</th>
							<th>Sal</th>
							<th>Own</th>
							<th>oOwn</th>
							<th>tOwn</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($contest->players as $player)
						    <tr data-dk-mlb-player-id="{{ $player->dk_mlb_player_id }}" 
						    	data-date="{{ $contest->date }}"
						    	data-player-pool-id="{{ $contest->player_pool_id }}"
						    	data-mlb-player-id="{{ $player->mlb_player_id }}"
						    	data-position="{{ $player->position }}"
						    	data-salary="{{ $player->salary }}"
						    	data-name="{{ $player->name }}"
						    	data-ownership="{{ $player->ownership }}"
						    	data-other-ownership="{{ $player->other_ownership }}"
						    	data-total-ownership="{{ $player->total_ownership }}"
						    	class="player-row">
						    	<td>{{ $player->name }}</td>
						    	<td>{{ $player->position }}</td>
						    	<td>{{ $player->salary }}</td>
						    	<td>{{ $player->ownership }}</td>
						    	<td>{{ $player->other_ownership }}</td>
						    	<td>{{ $player->total_ownership }}</td>
						    </tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	@endforeach

	
	

	<script type="text/javascript">

		/****************************************************************************************
		GLOBAL VARIABLES
		****************************************************************************************/

		var baseUrl = '<?php echo url(); ?>';

	</script>

	<script src="/js/daily/dk/mlb.js"></script>
@stop