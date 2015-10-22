<?php namespace App\Classes;

use App\Models\Team;

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

				break;
			}
		}

		foreach ($teams as $team) {
			if ($game->road_team_id == $team->id) {
				$game->road_team_abbr_br = $team->abbr_br;

				$game->road_team_abbr_pm = $team->abbr_pm;

				break;
			}
		}

		return $game;
	}

	private function addNbaResult($game) {
		if ($game->home_team_score > $game->road_team_score) {
			$game->result = $game->home_team_abbr_br.' '.$game->home_team_score.', '.$game->road_team_abbr_br.' '.$game->road_team_score;

			return $game;
		}

		if ($game->road_team_score > $game->home_team_score) {
			$game->result = $game->road_team_abbr_br.' '.$game->road_team_score.', '.$game->home_team_abbr_br.' '.$game->home_team_score;

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

}