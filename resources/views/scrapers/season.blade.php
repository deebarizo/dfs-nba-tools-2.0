@extends('master')

@section('content')
	<h2>{{ $heading }}</h2>

	{{ $season->end_year }}

	{!!	Form::open() !!}

	
	
	{!!	Form::close() !!}
@stop