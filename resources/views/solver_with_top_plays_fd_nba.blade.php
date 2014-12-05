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
				<table data-player-pool-id="{{ $playerPoolId }}" 
					   data-hash="{{ $lineup['hash'] }}" 
					   data-total-salary="{{ $lineup['total_salary'] }}" 
					   class="table table-striped table-bordered table-hover table-condensed {{ $lineup['css_class'] }}">
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
								<a href="#" class="add-or-remove-lineup-link">
									<span class="add-or-remove-lineup-anchor-text">{{ $lineup['anchor_text'] }}</span>
								</a>
								<span class="add-or-remove-lineup-link-loading-gif">
									<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />
								</span>
							</td>
							<td style="color: green"><strong>{{ $lineup['total_salary'] }}</strong></td>
						</tr>
					</tbody>
				</table>
			@endforeach	
	</div>

	<script>
		$(document).ready(function() {
			$(".edit-buy-in-link").click(function(e) {
				e.preventDefault();

				$(".edit-buy-in").toggleClass("form-hidden");
			});

			$(".add-or-remove-lineup-link").click(function(e) {
				e.preventDefault();

				var addOrRemove = $(this).children(".add-or-remove-lineup-anchor-text").text();

				$(this).children(".add-or-remove-lineup-anchor-text").text('');
				$(this).next(".add-or-remove-lineup-link-loading-gif").show();

				var playerPoolId = $(this).parent().parent().parent().parent().data('player-pool-id');
				var hash = $(this).parent().parent().parent().parent().data('hash');
				var totalSalary = $(this).parent().parent().parent().parent().data('total-salary');
				var $this = $(this);

		    	$.ajax({
		            url: '<?php echo url(); ?>/solver_top_plays/add_or_remove_lineup/'+playerPoolId+'/'+hash+'/'+totalSalary+'/'+addOrRemove,
		            type: 'POST',
		            success: function() {
						$this.parent().parent().parent().parent().toggleClass("active-lineup");	
						$this.next(".add-or-remove-lineup-link-loading-gif").hide();

						switch(addOrRemove) {
						    case "Add":
								$this.children(".add-or-remove-lineup-anchor-text").text("Remove");
						        break;
						    case "Remove":
						        $this.children(".add-or-remove-lineup-anchor-text").text("Add");
						        break;
						}
		            }
		        }); 

		
			});

		});
	</script>
@stop