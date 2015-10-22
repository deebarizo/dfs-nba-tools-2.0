<?php namespace App\Classes;

use App\Models\boxScoreLine;
use App\Models\Game;
use App\Models\Team;

use Illuminate\Support\Facades\DB;

class Formatter {

    /****************************************************************************************
    NBA GAMES
    ****************************************************************************************/

	public function formatNbaGames($games) {
		$teams = Team::all();

		foreach ($games as $game) {
			$game = $this->addTeams($teams, $game);

			$game->matchup = $game->road_team_abbr_br.' @ '.$game->home_team_abbr_br;

			$game = $this->addNbaResult($game);

			$game = $this->addNbaLine($game);

			$game = $this->addNbaLinks($game);

			$game->ot = ($game->ot_periods > 0 ? '<strong>'.$game->ot_periods.'</strong>' : $game->ot_periods);
		}

		# ddAll($games);

		return $games;
	}

	private function addTeams($teams, $game) {
		foreach ($teams as $team) {
			if ($game->home_team_id == $team->id) {
				$game->home_team_abbr_br = $team->abbr_br;

				$game->home_team_abbr_pm = $team->abbr_pm;

				$game->home_team_name_br = $team->name_br;

				break;
			}
		}

		foreach ($teams as $team) {
			if ($game->road_team_id == $team->id) {
				$game->road_team_abbr_br = $team->abbr_br;

				$game->road_team_abbr_pm = $team->abbr_pm;

				$game->road_team_name_br = $team->name_br;

				break;
			}
		}

		return $game;
	}

	private function addNbaResult($game) {
		$gameLink = '/games/nba/'.$game->id;

		if ($game->home_team_score > $game->road_team_score) {
			$game->result = '<a target="_blank" href="'.$gameLink.'">'.$game->home_team_abbr_br.' '.$game->home_team_score.', '.$game->road_team_abbr_br.' '.$game->road_team_score.'</a>';

			return $game;
		}

		if ($game->road_team_score > $game->home_team_score) {
			$game->result = '<a target="_blank" href="'.$gameLink.'">'.$game->road_team_abbr_br.' '.$game->road_team_score.', '.$game->home_team_abbr_br.' '.$game->home_team_score.'</a>';

			return $game;
		}
	}

	private function addNbaLine($game) {
		if ($game->vegas_home_team_score > $game->vegas_road_team_score) {
			$spread = $game->vegas_road_team_score - $game->vegas_home_team_score;

			$game->line = $game->home_team_abbr_br.' '.$spread;

			return $game;
		}

		if ($game->vegas_road_team_score > $game->vegas_home_team_score) {
			$spread = $game->vegas_home_team_score - $game->vegas_road_team_score;

			$game->line = $game->road_team_abbr_br.' '.$spread;

			return $game;
		}

		$game->line = 'PK';

		return $game;
	}	

	private function addNbaLinks($game) {
		$popcornMachineDate = preg_replace('/-/', '', $game->date);

		$popcornMachineLink = 'http://popcornmachine.net/gf?date='.$popcornMachineDate.'&game='.$game->road_team_abbr_pm.$game->home_team_abbr_pm;

		$game->links = '<a target="_blank" href="'.$game->link_br.'">BR</a> | <a target="_blank" href="'.$popcornMachineLink.'">PM</a>';

		return $game;
	}


    /****************************************************************************************
    NBA BOX SCORE
    ****************************************************************************************/

    public function formatNbaBoxScore($gameId) {
    	$boxScore = [];

    	$teams = Team::all();

		$boxScore['metadata'] = Game::where('id', $gameId)->first();

		$boxScore['metadata'] = $this->addTeams($teams, $boxScore['metadata']);

		$boxScore = $this->addNbaBoxScoreSubhead($boxScore);

		$boxScore['box_score_lines']['road']['starters'] = DB::table('box_score_lines')
													->select('*')
													->join('players', 'players.id', '=', 'box_score_lines.player_id')
													->where('game_id', $gameId)
													->where('box_score_lines.team_id', $boxScore['metadata']->road_team_id)
													->where('box_score_lines.role', 'starter')
													->get();

		$boxScore['box_score_lines']['road']['reserves'] = DB::table('box_score_lines')
													->select('*')
													->join('players', 'players.id', '=', 'box_score_lines.player_id')
													->where('game_id', $gameId)
													->where('box_score_lines.team_id', $boxScore['metadata']->road_team_id)
													->where('box_score_lines.role', 'reserve')
													->get();

		$boxScore['box_score_lines']['home']['starters'] = DB::table('box_score_lines')
													->select('*')
													->join('players', 'players.id', '=', 'box_score_lines.player_id')
													->where('game_id', $gameId)
													->where('box_score_lines.team_id', $boxScore['metadata']->home_team_id)
													->where('box_score_lines.role', 'starter')
													->get();

		$boxScore['box_score_lines']['home']['reserves'] = DB::table('box_score_lines')
													->select('*')
													->join('players', 'players.id', '=', 'box_score_lines.player_id')
													->where('game_id', $gameId)
													->where('box_score_lines.team_id', $boxScore['metadata']->home_team_id)
													->where('box_score_lines.role', 'reserve')
													->get();

		# ddAll($boxScore);

		return $boxScore;
    }

    private function addNbaBoxScoreSubhead($boxScore) {
		if ($boxScore['metadata']->home_team_score > $boxScore['metadata']->road_team_score) {
			$boxScore['subhead'] = $boxScore['metadata']->home_team_abbr_br.' '.$boxScore['metadata']->home_team_score.', '.$boxScore['metadata']->road_team_abbr_br.' '.$boxScore['metadata']->road_team_score.' | '.$boxScore['metadata']->date;

			return $boxScore;
		}

		if ($boxScore['metadata']->road_team_score > $boxScore['metadata']->home_team_score) {
			$boxScore['subhead'] = $boxScore['metadata']->road_team_abbr_br.' '.$boxScore['metadata']->road_team_score.', '.$boxScore['metadata']->home_team_abbr_br.' '.$boxScore['metadata']->home_team_score.' | '.$boxScore['metadata']->date;

			return $boxScore;
		}
    }

}