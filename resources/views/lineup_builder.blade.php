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
		@foreach ($lineups as $lineup)
			<div class="col-lg-4">
				<table>
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
								<td>{{ }}</td>
								<td>
									@foreach ($lineup['players'] as $player) 
										
									@endforeach
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		@endforeach
	</div>
@stop