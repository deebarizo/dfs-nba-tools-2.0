@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Scrapers - DK MLB Ownership</h2>
		</div>
	</div>
	<div class="row">
		<?php date_default_timezone_set('America/Chicago'); ?>
		<?php $yesterdayDate = date('Y-m-d', strtotime('-1 day')); ?>
		<?php $errors = Session::get('errors') ? : $errors; ?>

		@if(Session::has('message'))
		    <div class="col-lg-12">
				<div class="alert alert-{{ Session::get('alert') }} fade in" role="alert" style="width: 50%">
					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
					{{ Session::get('message') }}
				</div>
		    </div>
		@endif

		{!!	Form::open(['url' => 'scrapers/dk_mlb_ownership']) !!}
			<div class="col-lg-2"> 
				<div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
					{!! Form::label('date', 'Date:') !!}
					{!! Form::text('date', $yesterdayDate, ['class' => 'form-control']) !!}
					{!! $errors->first('date', '<span class="help-block">:message</span>') !!}
				</div>
			</div>

			<div class="col-lg-6"> 
				<div class="form-group {{ $errors->has('contest') ? 'has-error' : '' }}">
					{!! Form::label('contest', 'Contest:') !!}
					{!! Form::text('contest', '', ['class' => 'form-control']) !!}
					{!! $errors->first('contest', '<span class="help-block">:message</span>') !!}
				</div>
			</div>

			<div class="col-lg-2"> 
				<div class="form-group {{ $errors->has('entry_fee') ? 'has-error' : '' }}">
					{!! Form::label('entry_fee', 'Entry Fee:') !!}
					{!! Form::text('entry_fee', '', ['class' => 'form-control']) !!}
					{!! $errors->first('entry_fee', '<span class="help-block">:message</span>') !!}
				</div>
			</div>

			<div class="col-lg-2"> 
				<div class="form-group {{ $errors->has('time_period') ? 'has-error' : '' }}">			
					<label>Time Period: </label>
					<select class="form-control" name="time_period">
						<option value="-">-</option>
					  	<option value="All Day">All Day</option>
					  	<option value="Early">Early</option>
					  	<option value="Late">Late</option>
					</select>
				</div>
			</div>

			<div class="col-lg-3" style="margin: 10px 0 20px 0"> 
				<div class="form-group {{ $errors->has('csv') ? 'has-error' : '' }}">
					{!! Form::label('csv', 'CSV:') !!}
					{!! Form::file('csv', '', ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-12"> 
				{!! Form::submit('Submit', ['class' => 'btn btn-primary']) !!}
			</div>
		{!!	Form::close() !!}
	</div>
@stop