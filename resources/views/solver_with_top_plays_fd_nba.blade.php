@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily FD NBA (Solver With Top Plays) | {{ $date }} {{ $timePeriod }}</h2>

			<p>
				<strong>Buy In: </strong> 
				<span class="buy-in-amount">$0</span>
				(<a href="#" class="edit-buy-in-link">Edit</a>) 
			</p>

			<div class="input-group edit-buy-in form-hidden" style="width: 20%; margin-bottom: 10px">
				<div class="input-group-addon">$</div>
			   	<input type="text" class="form-control">
			   	<span class="input-group-btn">
			    	<button class="btn btn-default" type="button">Submit</button>
			   	</span>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6">
			<h4>Player Percentages</h4>

			<div id="player-percentages-container" style="width:100%; height:700px; padding-right: 70px"></div>
		</div>

		<div class="col-lg-6" style="overflow-y: scroll; height: 800px">
			<h4>Lineups</h4>

			@foreach ($lineups as $lineup)
				<table class="table table-striped table-bordered table-hover table-condensed">
					<thead>
						<tr>
							<th style="width: 15%">Pos</th>
							<th style="width: 55%">Name</th>
							<th style="width: 30%">Sal</th>
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
							<td style="text-align: center" colspan="2">
								<a href="#" class="add-or-remove-lineup-link">Add</a>
							</td>
							<td style="color: green"><strong>{{ $lineup['total_salary'] }}</strong></td>
						</tr>
					</tbody>
				</table>
			@endforeach	
	</div>

	<script>
		$(document).ready(function() {
			$(".edit-buy-in-link").click(function() {
				$(".edit-buy-in").toggleClass("form-hidden");
			});

			$(".add-or-remove-lineup-link").click(function() {
				addOrRemoveAnchorText = $(this).text();

				switch(addOrRemoveAnchorText) {
				    case "Add":
				        $(this).text("Remove");
				        break;
				    case "Remove":
				        $(this).text("Add");
				        break;
				}				
			});

		});
	</script>
@stop