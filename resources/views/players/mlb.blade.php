@extends('master')

@section('content')

	<div class="row">
		<div class="col-lg-12">
			<h2>{{ $name }} (MLB Player)</h2>
		</div>
	</div>

	@foreach ($seasons as $season)
		<div class="row">
			<div class="col-lg-12">
				<h3>{{ $season['year'] }}</h3>

				<h4>Game Log</h4>

				<table style="font-size: {{ $fontSize }}" id="game-log-{{ $season['year'] }}" class="table table-striped table-bordered table-hover table-condensed">
					<thead>
						<tr>
							<th>Date</th>
							<th>Team</th>
							<th>Opp</th>
							<th>Score</th>
							@if (isset($season['box_score_lines'][0]->ip))
								<th>IP</th>
								<th>K</th>
								<th>W</th>
								<th>R</th>
								<th>ER</th>
								<th>H</th>
								<th>BB</th>
								<th>HBP</th>
								<th>CG</th>
								<th>SO</th>
								<th>NO</th>
							@else
								<th>LU</th>
								<th>Plat</th>
								<th>Opp SP</th>
								<th>PA</th>
								<th>1B</th>
								<th>2B</th>
								<th>3B</th>
								<th>HR</th>
								<th>RBI</th>
								<th>R</th>
								<th>BB</th>
								<th>IBB</th>
								<th>HBP</th>
								<th>SF</th>
								<th>SH</th>
								<th>GDP</th>
								<th>SB</th>
								<th>CS</th>
							@endif
							<th>FPTS</th>
							<th>Sal</th>
							<th>aVR</th>
							<th>3GPP</th>
							<th>5DU</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($season['box_score_lines'] as $boxScoreLine)
						    <tr>
						    	<td>{{ $boxScoreLine->date }}</a></td>
						    	<td>{{ $boxScoreLine->abbr_dk }}</td>
						    	<td>{{ $boxScoreLine->opp_abbr_dk }}</td>
						    	<td>{!! $boxScoreLine->score_column !!}</td>
								@if (isset($season['box_score_lines'][0]->ip))
									<td>{{ $boxScoreLine->ip }}</td>
									<td>{{ $boxScoreLine->so }}</td>
									<td>{{ $boxScoreLine->win }}</td>
									<td>{{ $boxScoreLine->runs_against }}</td>
									<td>{{ $boxScoreLine->er }}</td>
									<td>{{ $boxScoreLine->hits_against }}</td>
									<td>{{ $boxScoreLine->bb_against }}</td>
									<td>{{ $boxScoreLine->hbp_against }}</td>
									<td>{{ $boxScoreLine->cg }}</td>
									<td>{{ $boxScoreLine->cg_shutout }}</td>
									<td>{{ $boxScoreLine->no_hitter }}</td>
								@else
									<td>{{ $boxScoreLine->lineup }}</td>
									<td>{{ $boxScoreLine->platoon }}</td>
									<td>{{ $boxScoreLine->opp_sp }}</td>
									<td>{{ $boxScoreLine->pa }}</td>
									<td>{{ $boxScoreLine->singles }}</td>
									<td>{{ $boxScoreLine->doubles }}</td>
									<td>{{ $boxScoreLine->triples }}</td>
									<td>{{ $boxScoreLine->hr }}</td>
									<td>{{ $boxScoreLine->rbi }}</td>
									<td>{{ $boxScoreLine->runs }}</td>
									<td>{{ $boxScoreLine->bb }}</td>
									<td>{{ $boxScoreLine->ibb }}</td>
									<td>{{ $boxScoreLine->hbp }}</td>
									<td>{{ $boxScoreLine->sf }}</td>
									<td>{{ $boxScoreLine->sh }}</td>
									<td>{{ $boxScoreLine->gdp }}</td>
									<td>{{ $boxScoreLine->sb }}</td>
									<td>{{ $boxScoreLine->cs }}</td>
								@endif
								<td>{{ $boxScoreLine->fpts }}</td>
								<td>{{ $boxScoreLine->salary }}</td>
								<td>{{ $boxScoreLine->avr }}</td>
								<td>{!! $boxScoreLine->ownership_column_3gpp !!}</td>
								<td>{!! $boxScoreLine->ownership_column_5du !!}</td>
						    </tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>

		<hr>
	@endforeach

@stop