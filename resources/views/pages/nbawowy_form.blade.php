@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>nbawowy! (Form)</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<form class="form" style="margin: 0 0 10px 0">

				<label>Player</label>
				<input class="salary-input form-control" type="text" style="width: 15%">		

				<label>Salary</label>
				<input class="salary-input form-control" type="number" value="0" style="width: 10%">
				<input class="form-control" type="radio" name="salary-toggle" id="greater-than" value="greater-than" checked="checked">>=
				<input class="form-control" type="radio" name="salary-toggle" id="less-than" value="less-than"><=				
				<input style="width: 10%; margin-right: 20px; outline: none" class="salary-reset btn btn-default" name="salary-reset" value="Salary Reset">

			</form>
		</div>
	</div>
@stop