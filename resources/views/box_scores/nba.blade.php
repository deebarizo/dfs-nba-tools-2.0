@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>NBA Box Score</h2>

			<h3>{{ $boxScore['subhead'] }}</h3>

			@foreach ($boxScore['box_score_lines'] as $locations)

				@foreach ($locations as $roles)

					<table class="table table-striped table-bordered table-hover table-condensed">
						<thead>
							<tr>
								<th>{{ ucfirst($roles[0]->role) }}s</th>
								<th>Pos</th>
								<th>Mp</th>
								<th>Fg</th>
								<th>3p</th>
								<th>Ft</th>
								<th>Or</th>
								<th>Dr</th>
								<th>Tr</th>
								<th>Ast</th>
								<th>Bl</th>
								<th>St</th>
								<th>Pf</th>
								<th>To</th>
								<th>Pt</th>
								<th>Usg</th>
								<th>Fdpts</th>
								<th>Fdppm</th>
							</tr>
						</thead>
					</table>

					@foreach ($roles as $boxScoreLine)

						<?php prf($boxScoreLine) ?>

						@foreach ($boxScoreLine as $stat)



						@endforeach

					@endforeach
				
				@endforeach
			
			@endforeach

		</div>
	</div>
@stop