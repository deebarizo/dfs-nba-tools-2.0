<?php

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

/****************************************************************************************
HOME
****************************************************************************************/

$router->get('/', 'PlayerPoolsController@home');


/****************************************************************************************
GAMES
****************************************************************************************/

$router->get('games', 'GamesController@showNbaGames');


/****************************************************************************************
SCRAPERS
****************************************************************************************/

$router->post('scrapers/br_nba_box_score_lines', 'ScrapersController@br_nba_box_score_lines');
$router->post('scrapers/br_nba_games', 'ScrapersController@br_nba_games');
$router->post('scrapers/fd_nba_salaries', 'ScrapersController@fd_nba_salaries');

$router->post('scrapers/dk_mlb_salaries', 'ScrapersController@dk_mlb_salaries');
$router->post('scrapers/bat_mlb_projections', 'ScrapersController@bat_mlb_projections');
$router->post('scrapers/fg_mlb_box_score_lines', 'ScrapersController@fg_mlb_box_score_lines');
$router->post('scrapers/dk_mlb_contests', 'ScrapersController@dkMlbContests');


/****************************************************************************************
STUDIES (NBA)
****************************************************************************************/

$router->get('studies/correlations/scores_and_vegas_scores', 'StudiesController@correlationScoresAndVegasScores');
$router->get('studies/histograms/scores', 'StudiesController@histogramScores');
$router->get('studies/correlations/scores_and_fd_scores', 'StudiesController@correlationScoresAndFDScores');
$router->get('studies/correlations/spreads_and_player_fpts_error/{mpgMax}/{fppgMax}/{fppgMin}/{absoluteSpread}', 'StudiesController@correlationSpreadsAndPlayerFptsError');
$router->get('studies/general/classifying_projected_fpts/', 'StudiesController@classifyingProjectedFpts');


/****************************************************************************************
PLAYERS
****************************************************************************************/

$router->get('players/nba/{playerId}', 'PlayersController@getPlayerStats');

$router->get('players/mlb/{playerId}', 'PlayersMlbController@getPlayerStats');


/****************************************************************************************
DAILY
****************************************************************************************/

$router->get('daily/{site}/{sport}/{timePeriod}/{date}/{contestId}', 'DailyController@showDaily');

$router->post('daily/fd/nba/update_top_plays/{playerFdIndex}/{isPlayerActive}', 'DailyController@update_top_plays'); // ajax
$router->post('daily/fd/nba/update_target_percentage/{playerFdIndex}/{newTargetPercentage}', 'DailyController@updateTargetPercentage'); // ajax

$router->post('daily/dk/mlb/update_target_percentage_for_dk_mlb', 'DailyController@updateTargetPercentageForDkMlb'); // ajax


/****************************************************************************************
STATIC PAGES
****************************************************************************************/

$router->get('scrapers/br_nba_box_score_lines', function() {
	return View::make('scrapers/br_nba_box_score_lines');
});
$router->get('scrapers/br_nba_games', function() {
	return View::make('scrapers/br_nba_games');
});
$router->get('scrapers/fd_nba_salaries', function() {
	return View::make('scrapers/fd_nba_salaries');
});

$router->get('scrapers/dk_mlb_salaries', function() {
	return View::make('scrapers/dk_mlb_salaries');
});
$router->get('scrapers/bat_mlb_projections', function() {
	return View::make('scrapers/bat_mlb_projections');
});
$router->get('scrapers/fg_mlb_box_score_lines', function() {
	return View::make('scrapers/fg_mlb_box_score_lines');
});
$router->get('scrapers/dk_mlb_contests', function() {
	return View::make('scrapers/dk_mlb_contests');
});

$router->get('scrapers', function() {
	return View::make('pages/scrapers');
});
$router->get('studies', function() {
	return View::make('pages/studies');
});
$router->get('admin', function() {
	return View::make('pages/admin');
});
$router->get('players', function() {
	return View::make('players');
});


/****************************************************************************************
SOLVER NBA
****************************************************************************************/

$router->get('solver_fd_nba', 'SolverFdNbaController@solverFdNba');
$router->get('solver_fd_nba/{date}', 'SolverFdNbaController@solverFdNba');
$router->get('solver_fd_nba/{date}/{numTopLineups}', 'SolverFdNbaController@solverFdNba');


/****************************************************************************************
SOLVER TOP PLAYS (NBA)
****************************************************************************************/

$router->get('solver_with_top_plays_fd_nba/', 'SolverFdNbaController@solver_with_top_plays');
$router->get('solver_with_top_plays_fd_nba/{date}', 'SolverFdNbaController@solver_with_top_plays');

$router->post('solver_top_plays/update_buy_in/{playerPoolId}/{buyIn}', 'SolverFdNbaController@updateBuyIn');
$router->post('solver_top_plays/add_default_lineup_buy_in/{addDefaultLineupBuyIn}', 'SolverFdNbaController@addDefaultLineupBuyIn');
$router->post('solver_top_plays/add_or_remove_lineup/', 'SolverFdNbaController@addOrRemoveLineup'); 
$router->post('solver_top_plays/update_lineup_buy_in/{playerPoolId}/{hash}/{lineupBuyIn}', 'SolverFdNbaController@updateLineupBuyIn');
$router->post('solver_top_plays/play_or_unplay_lineup/', 'SolverFdNbaController@playOrUnplayLineup'); 


/****************************************************************************************
SOLVER TOP PLAYS (MLB)
****************************************************************************************/

$router->get('solver_top_plays/{siteInUrl}/mlb/{timePeriodInUrl}/{date}', 'SolverTopPlaysMlbController@solverTopPlaysMlb');
$router->get('solver_top_plays/{siteInUrl}/mlb/{timePeriodInUrl}/{date}/{sorter}', 'SolverTopPlaysMlbController@solverTopPlaysMlb');

$router->post('solver_top_plays/dk/mlb/add_or_remove_lineup/', 'SolverTopPlaysMlbController@addOrRemoveLineup'); 


/****************************************************************************************
FILTERS
****************************************************************************************/

$router->resource('daily_fd_filters', 'DailyFdFiltersController', ['except' => ['create']]);

$router->get('daily_fd_filters/{player_id}/create', 'DailyFdFiltersController@create');
$router->get('daily_fd_filters/{player_id}/create/{dailyFdFilterId}', 'DailyFdFiltersController@create');


/****************************************************************************************
LINEUP BUILDER
****************************************************************************************/

$router->get('lineup_builder/', 'LineupBuilderController@showActiveLineups');
$router->get('lineup_builder/{date}', 'LineupBuilderController@showActiveLineups');
$router->get('lineup_builder/{date}/create', 'LineupBuilderController@createLineup');
$router->get('lineup_builder/{date}/create/{hash}', 'LineupBuilderController@createLineup');

$router->get('lineup_builder/{siteInUrl}/mlb/{timePeriodInUrl}/{date}', 'LineupBuilderController@createLineupMlb');
$router->get('lineup_builder/{siteInUrl}/mlb/{timePeriodInUrl}/{date}/{hash}', 'LineupBuilderController@createLineupMlb');


/****************************************************************************************
AUTOCOMPLETE
****************************************************************************************/

$router->get('get_player_name_autocomplete/{sportInUrl}', 'PlayersController@getNbaPlayerNameAutocomplete');


/****************************************************************************************
MISC
****************************************************************************************/

$router->get('teams/{abbr_br}', 'TeamsController@getTeamStats');

$router->get('nbawowy/', 'NbawowyController@nbawowy_form');
$router->get('nbawowy/{name}/{startDate}/{endDate}/on/{playerOn}/off/{playerOff}/{team}', 'NbawowyController@nbawowy');

$router->get('one-of', 'OneOfController@run');


/****************************************************************************************
ADMIN
****************************************************************************************/

$router->get('admin/{sport}/add_player', 'AdminController@addPlayerForm');
$router->post('admin/{sport}/add_player', 'AdminController@addPlayer');