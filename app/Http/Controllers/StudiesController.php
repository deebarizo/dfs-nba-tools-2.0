<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\BoxScoreLine;

use App\Classes\StatBuilder;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class StudiesController {

	private $seasonStartYear = [
		'earliest' => 2012,
		'latest' => 2015
	];

	/****************************************************************************************
	CATEGORIZING PROJECTED FPTS
	****************************************************************************************/	

	public function classifyingProjectedFpts($mpgMax = 20, $fppgMax = 200, $fppgMin = -100, $absoluteSpread = '') {
		$numOfClassifications = 10;

		$statBuilder = new StatBuilder;

		$seasons = $statBuilder->getSpreadsAndPlayerFptsErrorBySeason($this->seasonStartYear['earliest'], $this->seasonStartYear['latest'], $mpgMax, $fppgMax, $fppgMin, $absoluteSpread);

		$numOfBoxScoreLines = 0;
		$count = 0;
		$projectedFpts = [];

		foreach ($seasons as $season) {
			$numOfBoxScoreLines += count($season['box_score_lines']);

			foreach ($season['box_score_lines'] as $boxScoreLine) {
				$count++;

				$projectedFpts[$count] = $boxScoreLine['projected_fpts'];
			}
		}

		sort($projectedFpts);

		# ddAll($projectedFpts);

		$classificationInterval = intval($numOfBoxScoreLines / $numOfClassifications);

		# ddAll($classificationInterval);

		$playerGroups = [];

		for ($i = 1; $i <= $numOfClassifications; $i++) { 
			$min = numFormat($projectedFpts[($i * $classificationInterval) - $classificationInterval]);
			$max = numFormat($projectedFpts[($i * $classificationInterval) - 1]);

			if ($i > 1 && $min == $playerGroups[$i-1]['max']) {
				$min += 0.01;
			}

			if ($i == 1) {
				$min = -100;
			}

			if ($i == $numOfClassifications) {
				$max = 200;
			}

			$playerGroups[$i] = array(
				'min' => numFormat($min),
				'max' => numFormat($max)
			);
		}

		ddAll($playerGroups);
	}


	/****************************************************************************************
	CORRELATION: SPREADS AND PLAYER FPTS ERROR
	****************************************************************************************/	

	public function correlationSpreadsAndPlayerFptsError($mpgMax, $fppgMax, $fppgMin, $absoluteSpread) {
		$statBuilder = new StatBuilder;

		$xMin = $statBuilder->getXMinBasedOnAbsoluteSpread($absoluteSpread);
		
		if ($absoluteSpread == 'NOABS') {
            $absoluteSpread = '';
        }

        $seasons = $statBuilder->getSpreadsAndPlayerFptsErrorBySeason($this->seasonStartYear['earliest'], $this->seasonStartYear['latest'], $mpgMax, $fppgMax, $fppgMin, $absoluteSpread);

		# ddAll($seasons[0]['box_score_lines']);

		$spreads = [];
		$playerFptsError = [];

		foreach ($seasons as $season) {
			foreach ($season['box_score_lines'] as $boxScoreLine) {
				$playerFptsError[] = $boxScoreLine['player_fpts_error'];

				if ($absoluteSpread == 'ABS') {
					$spreads[] = $boxScoreLine['absolute_spread'];
				}
				
				if ($boxScoreLine['team_id'] == $boxScoreLine['home_team_id'] && $absoluteSpread == '') {
					$spreads[] = $boxScoreLine['absolute_spread'];

					continue;
				}
				
				if ($boxScoreLine['team_id'] == $boxScoreLine['road_team_id'] && $absoluteSpread == '') {
					$spreads[] = $boxScoreLine['absolute_spread'] * -1;

					continue;
				}
			}
		}

		$data = calculateCorrelation($spreads, $playerFptsError, 'Spreads', 'Player Fpts Error');

		# ddAll($data);

		for ($x = $xMin; $x <= 25 ; $x++) { 
			$y = ($data['bOne'] * $x) + $data['bNaught'];
			$lineOfBestFitJSON[] = [$x, $y];
		}

		$data['lineOfBestFitJSON'] = $lineOfBestFitJSON;

		$perfectLineJSON = [];

		for ($x = $xMin; $x <= 25 ; $x++) { 
			$y = $x * 2;
			$perfectLineJSON[] = [$x, $y];
		}

		$data['perfectLineJSON'] = $perfectLineJSON;

		$data['subhead1'] = 'Calculate Player Fpts Error:';
		$data['subhead2'] = '(Absolute Spread * '.$data['bOne'].') + '.$data['bNaught']; 

		return view('studies/correlation_spreads_and_player_fpts_error', compact('data'));		
	}


	/****************************************************************************************
	CORRELATION: SCORES AND FD SCORES
	****************************************************************************************/

	public function correlationScoresAndFDScores() {
		$rawData = DB::table('box_score_lines')->select(DB::raw('SUM(pts) as score, SUM(pts + (trb * 1.2) + (ast * 1.5) + (blk * 2) + (stl * 2) - tov) as fd_score'))->groupBy('game_id', 'team_id')->get();

		$scores = [];

		foreach ($rawData as $key => $value) {
			$scores[$key] = $value->score;
		}

		$fdScores = [];

		foreach ($rawData as $key => $value) {
			$fdScores[$key] = $value->fd_score;
		}

		$data = calculateCorrelation($scores, $fdScores, 'Scores', 'FD Scores');

		for ($x=50; $x <= 150 ; $x++) { 
			$y = ($data['bOne'] * $x) + $data['bNaught'];
			$lineOfBestFitJSON[] = [$x, $y];
		}

		$data['lineOfBestFitJSON'] = $lineOfBestFitJSON;

		$perfectLineJSON = [];

		for ($x=50; $x <= 150 ; $x++) { 
			$y = $x * 2;
			$perfectLineJSON[] = [$x, $y];
		}

		$data['perfectLineJSON'] = $perfectLineJSON;

		$data['calculatePredictedFDScore'] = '(Score * '.$data['bOne'].') + '.$data['bNaught']; 

		return view('studies/correlation_scores_and_fd_scores', compact('data'));
	}


	/****************************************************************************************
	HISTOGRAM: SCORES
	****************************************************************************************/

	public function histogramScores() {
		$teamScores['home_team_score'] = Game::all(['home_team_score'])->toArray();
		$teamScores['road_team_score'] = Game::all(['road_team_score'])->toArray();

		$scores = [];

		foreach ($teamScores as $key => $location) {
			foreach ($location as $teamScore) {
				$scores[] = $teamScore[$key]; 
			}
		}

		sort($scores);

		$histogram = [];

		$lowestScore = $scores[0];
		$highestScore = $scores[count($scores) - 1];

		for ($i = $lowestScore; $i <= $highestScore; $i++) { 
			$histogram[] = [$i, 0];

			if (count($scores) > 0) {
				foreach ($scores as $index => $score) {
					if ($score == $i) {
						foreach ($histogram as &$array) {
							if ($score == $array[0]) {
								$array[1]++;

								break;
							}
						}

						unset($array);

						unset($scores[$index]);
					}
				}				
			}
		}

		return view('studies/histogram_scores', compact('histogram'));
	}


	/****************************************************************************************
	CORRELATION: SCORES AND VEGAS SCORES
	****************************************************************************************/

	public function correlationScoresAndVegasScores() {
		$teamScores['home_team_score'] = Game::all(['home_team_score'])->toArray();
		$teamScores['road_team_score'] = Game::all(['road_team_score'])->toArray();

		$scores = [];

		foreach ($teamScores as $key => $location) {
			foreach ($location as $teamScore) {
				$scores[] = $teamScore[$key]; 
			}
		}

		$vegasTeamScores['vegas_home_team_score'] = Game::all(['vegas_home_team_score'])->toArray();
		$vegasTeamScores['vegas_road_team_score'] = Game::all(['vegas_road_team_score'])->toArray();

		$vegasScores = [];

		foreach ($vegasTeamScores as $key => $location) {
			foreach ($location as $vegasTeamScore) {
				$vegasScores[] = $vegasTeamScore[$key]; 
			}
		}

		$data = calculateCorrelation($scores, $vegasScores, 'Scores', 'Vegas Scores');

		for ($x=40; $x <= 150 ; $x++) { 
			$y = ($data['bOne'] * $x) + $data['bNaught'];
			$lineOfBestFitJSON[] = [$x, $y];
		}

		$data['lineOfBestFitJSON'] = $lineOfBestFitJSON;

		$perfectLineJSON = [];

		for ($x=40; $x <= 150 ; $x++) { 
			$y = $x;
			$perfectLineJSON[] = [$x, $y];
		}

		$data['perfectLineJSON'] = $perfectLineJSON;

		$data['calculatePredictedScore'] = '(Vegas Score - '.$data['bNaught'].') / '.$data['bOne']; 

		return view('studies/correlation_scores_and_vegas_scores', compact('data'));
	}
	
}