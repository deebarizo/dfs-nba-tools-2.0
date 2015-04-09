@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Scrapers - FG MLB Box Score Lines</h2>
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

		{!!	Form::open(['url' => 'scrapers/fg_mlb_box_score_lines']) !!}
			<div class="col-lg-2"> 
				<div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
					{!! Form::label('date', 'Date:') !!}
					{!! Form::text('date', $yesterdayDate, ['class' => 'form-control']) !!}
					{!! $errors->first('date', '<span class="help-block">:message</span>') !!}
				</div>
			</div>

			<div class="col-lg-12"> 
				{!! Form::submit('Submit', ['class' => 'btn btn-primary']) !!}
			</div>
		{!!	Form::close() !!}
	</div>
@stop