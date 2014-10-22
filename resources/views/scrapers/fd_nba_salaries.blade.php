@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Scrapers - FD NBA Salaries</h2>
		</div>
	</div>
	<div class="row">
		<?php $today_date = date('Y-m-d'); ?>
		<?php $errors = Session::get('errors') ? : $errors; ?>

		{!!	Form::open(['url' => 'scrapers/fd_nba_salaries_scraper']) !!}

			<div class="col-lg-2"> 
				<div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
					{!! Form::label('date', 'Date:') !!}
					{!! Form::text('date', $today_date, ['class' => 'form-control']) !!}
					{!! $errors->first('date', '<span class="help-block">:message</span>') !!}
				</div>
			</div>

			<div class="col-lg-6"> 
				<div class="form-group {{ $errors->has('url') ? 'has-error' : '' }}">
					{!! Form::label('url', 'URL:') !!}
					{!! Form::text('url', null, ['class' => 'form-control']) !!}
					{!! $errors->first('url', '<span class="help-block">:message</span>') !!}
				</div>
			</div>

			<div class="col-lg-3"> 
				<div class="form-group {{ $errors->has('time_period') ? 'has-error' : '' }}">
					{!! Form::label('time_period', 'Time Period:') !!}<br>
					{!! Form::radio('time_period', 'All Day') !!} All Day &nbsp;
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