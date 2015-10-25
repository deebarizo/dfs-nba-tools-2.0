@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>NBA Box Score</h2>

			<h3>{{ $boxScore['subhead'] }}</h3>

			@foreach ($boxScore['box_score_lines'] as $locations)

				<table class="table table-striped table-bordered table-hover table-condensed">

					@foreach ($locations as $key => $roles)

						@if ($key == 'starters' || $key == 'reserves')

							<thead>
								<tr>
									<th style="width: 20%">{{ $locations['abbr_br'] }} {{ ucfirst($roles[0]->role) }}s</th>
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
									<th>Pts</th>
									<th>Usg</th>
									<th>Fdpts</th>
									<th>Fdppm</th>
									<th>Fdsh</th>
								</tr>
							</thead>

							<tbody>

							@foreach ($roles as $boxScoreLine)

								<tr>
									<td><a href="/players/nba/{{ $boxScoreLine->player_id }}">{{ $boxScoreLine->name }}</a></td>
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
									<td>{{ numFormat($boxScoreLine->fdsh * 100, 1) }}%</td>
								</tr>

							@endforeach

							</tbody>

						@endif

						@if ($key == 'totals')

							<thead>
								<tr>
									<th style="width: 20%">{{ $locations['abbr_br'] }} Totals</th>
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
									<th>Pts</th>
									<th>&nbsp;</th>
									<th>Fdpts</th>
									<th>Fdppm</th>
									<th>Fdsh</th>
								</tr>
							</thead>

							@foreach ($roles as $boxScoreLine)

								<tbody>
									<tr>
										<td>&nbsp;</td>
										<td>{{ numFormat($boxScoreLine->mp, 0) }}</td>
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
										<td>&nbsp;</td>
										<td>{{ numFormat($boxScoreLine->fdpts, 2) }}</td>
										<td>{{ numFormat($boxScoreLine->fdppm, 2) }}</td>
										<td>100.0%</td>
									</tr>

									<tr>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>{{ numFormat($boxScoreLine->fg / $boxScoreLine->fga * 100, 1) }}%</td>
										<td>{{ numFormat($boxScoreLine->threep / $boxScoreLine->threepa * 100, 1) }}%</td>
										<td>{{ numFormat($boxScoreLine->ft / $boxScoreLine->fta * 100, 1) }}%</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
									</tr>
								</tbody>

							@endforeach

						@endif

					@endforeach

				</table>

				<table class="table table-striped table-bordered table-hover table-condensed">

					<thead>
						<tr>
							<th style="width: 20%">{{ $locations['abbr_br'] }} FD Totals</th>
							<th>aPts</th>
							<th>aFdpts</th>
							<th>apFdpts</th>
							<th>aDiff</th>
							<th>aDiff%</th>
							<th>pPts</th>
							<th>pFdpts</th>
							<th>pDiff</th>
							<th>pDiff%</th>
						</tr>
					</thead>

					<tbody>
						<tr>
							<td style="width: 20%">{{ $locations['abbr_br'] }}</td>
							<td>{{ $locations['fd_totals']->apts }}</td>
							<td>{{ numFormat($locations['fd_totals']->afdpts, 2) }}</td>
							<td>{{ numFormat(($locations['fd_totals']->apts * 1.5560525638751) + 40.088184690875, 2) }}</td>
							<td>{{ numFormat($locations['fd_totals']->afdpts - (($locations['fd_totals']->apts * 1.5560525638751) + 40.088184690875), 2) }}</td>
							<td>{{ numFormat(($locations['fd_totals']->afdpts - (($locations['fd_totals']->apts * 1.5560525638751) + 40.088184690875)) / (($locations['fd_totals']->apts * 1.5560525638751) + 40.088184690875) * 100, 2) }}%</td>
							<td>{{ numFormat($locations['fd_totals']->ppts, 2) }}</td>
							<td>{{ numFormat(($locations['fd_totals']->ppts * 1.5560525638751) + 40.088184690875, 2) }}</td>
							<td>{{ numFormat($locations['fd_totals']->afdpts - (($locations['fd_totals']->ppts * 1.5560525638751) + 40.088184690875), 2) }}</td>
							<td>{{ numFormat(($locations['fd_totals']->afdpts - (($locations['fd_totals']->ppts * 1.5560525638751) + 40.088184690875)) / (($locations['fd_totals']->ppts * 1.5560525638751) + 40.088184690875) * 100, 2) }}%</td>
						</tr>
					</tbody>

				</table>

				<hr>
			
			@endforeach

		</div>
	</div>
@stop