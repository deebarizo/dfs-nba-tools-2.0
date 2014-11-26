@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily - FD NBA</h2>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12">
			<h3>{{ $date }} {{ $timePeriod }}</h3>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-3">
			<table id="daily" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Pos</th>
						<th>Name</th>
						<th>Salary</th>
						<th>FPPG-1</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>					
				</tbody>
			</table>
		</div>
	</div>
@stop