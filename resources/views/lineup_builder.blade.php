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

			<table>
				<thead>
					<tr>
						<th>Id</th>								
						<th>Players</th>
						<th>Edit Link</th>
					</tr>				
				</thead>
				<tbody>
					@foreach ($lineups as $key => $lineup)
						<tr>
							<td>{{ $key + 1 }}</td>
							<td>
								@foreach ($lineup['players'] as $player) 
									
								@endforeach
							</td>
	

						</tr>
					@endforeach
				</tbody>
			</table>