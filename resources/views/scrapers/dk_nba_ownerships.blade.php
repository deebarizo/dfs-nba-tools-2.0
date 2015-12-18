@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Scrapers - DK NBA Ownerships</h2>
		</div>
	</div>
	<div class="row">
		<?php date_default_timezone_set('America/Chicago'); ?>
		<?php $today_date = date('Y-m-d', strtotime("-1 days")); ?>
		<?php $errors = Session::get('errors') ? : $errors; ?>

		@if(Session::has('message'))
		    <div class="col-lg-12">
				<div class="alert alert-{{ Session::get('alert') }} fade in" role="alert" style="width: 100%">
					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
					{!! Session::get('message') !!}
				</div>
		    </div>
		@endif

		{!! Form::open(array('url'=>'scrapers/dk_nba_ownerships', 'files'=>true)) !!}
			<div class="col-lg-2"> 
				<div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
					{!! Form::label('date', 'Date:') !!}
					{!! Form::text('date', '2015-12-16', ['class' => 'form-control']) !!}
					{!! $errors->first('date', '<span class="help-block">:message</span>') !!}
				</div>
			</div>

			<div class="col-lg-3 col-lg-offset-1"> 
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