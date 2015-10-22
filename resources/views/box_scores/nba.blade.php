@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>NBA Box Score</h2>

			<h3>{{ $boxScore['subhead'] }}</h3>

			@foreach ($boxScore['box_score_lines'] as $locations)

				<table class="table table-striped table-bordered table-hover table-condensed">

					@foreach ($locations as $roles)

						<thead>
								<tr>
									<th style="width: 20%">{{ $roles[0]->abbr_br }} {{ ucfirst($roles[0]->role) }}s</th>
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

							<tbody>

						@foreach ($roles as $boxScoreLine)

								<tr>
									<td>{{ $boxScoreLine->name }}</td>
									<td>{{ $boxScoreLine->mp }}</td>
									<td>{{ $boxScoreLine->fg }}-{{ $boxScoreLine->fga }}</td>
									<td>{{ $boxScoreLine->threep }}-{{ $boxScoreLine->threepa }}</td>
									<td>{{ $boxScoreLine->ft }}-{{ $boxScoreLine->fta }}</td>
									<td>{{ $boxScoreLine->orb }}</td>
									<td>{{ $boxScoreLine->drb }}</td>
									<td>{{ $boxScoreLine->trb }}</td>
									<td>{{ $boxScoreLine->ast }}</td>
									<td>{{ $boxScoreLine->blk }}</td>
									<td>{{ $boxScoreLine->stl }}</td>
									<td>{{ $boxScoreLine->pf }}</td>
									<td>{{ $boxScoreLine->tov }}</td>
									<td>{{ $boxScoreLine->pts }}</td>
									<td>{{ $boxScoreLine->usg }}</td>
									<td>{{ $boxScoreLine->fdpts }}</td>
									<td>{{ $boxScoreLine->fdppm }}</td>
								</tr>
							
						@endforeach

							</tbody>

					@endforeach

				</table>

				<hr>
			
			@endforeach

			

		</div>
	</div>
@stop