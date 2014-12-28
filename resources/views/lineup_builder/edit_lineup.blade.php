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
		</div>

		<table>
			<thead>
				<tr>
					<th>Pos</th>					
					<th>Name</th>
					<th>Salary</th>
					<th>Update</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td></td>
				</tr>				
			</tbody>
		</table>
	</div>
@stop