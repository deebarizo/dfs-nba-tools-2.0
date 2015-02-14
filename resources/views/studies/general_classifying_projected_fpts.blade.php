@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-6">
			<h2>Classifications of Projected Fpts</h2>

			<table class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Order</th>
						<th>Min</th>
						<th>Max</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($playerGroups as $key => $playerGroup)
						<tr>
							<td>{{ $key }}</td>
							<td>{{ $playerGroup['min'] }}</td>
							<td>{{ $playerGroup['max'] }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
@stop