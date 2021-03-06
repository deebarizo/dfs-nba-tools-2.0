@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Scrapers - DK NBA Salaries</h2>
		</div>
	</div>
	<div class="row">
		<?php date_default_timezone_set('America/Chicago'); ?>
		<?php $today_date = date('Y-m-d'); ?>
		<?php $errors = Session::get('errors') ? : $errors; ?>

		@if(Session::has('message'))
		    <div class="col-lg-12">
				<div class="alert alert-{{ Session::get('alert') }} fade in" role="alert" style="width: 100%">
					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
					{!! Session::get('message') !!}
				</div>
		    </div>
		@endif

		{!! Form::open(array('url'=>'scrapers/dk_nba_salaries', 'files'=>true)) !!}
			<div class="col-lg-2"> 
				<div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
					{!! Form::label('date', 'Date:') !!}
					{!! Form::text('date', $today_date, ['class' => 'form-control']) !!}
					{!! $errors->first('date', '<span class="help-block">:message</span>') !!}
				</div>
			</div>

			<div class="col-lg-3 col-lg-offset-1"> 
				<div class="form-group {{ $errors->has('csv') ? 'has-error' : '' }}">
					{!! Form::label('csv', 'CSV:') !!}
					{!! Form::file('csv', '', ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-3"> 
				<div class="form-group {{ $errors->has('time_period') ? 'has-error' : '' }}">
					{!! Form::label('time_period', 'Time Period:') !!}<br>
					{!! Form::radio('time_period', 'All Day', true) !!} All Day &nbsp;
					{!! Form::radio('time_period', 'Early') !!} Early &nbsp;
					{!! Form::radio('time_period', 'Late') !!} Late
					{!! $errors->first('time_period', '<span class="help-block">:message</span>') !!}
				</div>
			</div>

			<div class="col-lg-12"> 
				{!! Form::submit('Submit', ['class' => 'btn btn-primary']) !!}
			</div>
		{!!	Form::close() !!}
	</div>
@stop