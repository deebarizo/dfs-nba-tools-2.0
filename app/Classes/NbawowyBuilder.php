<?php namespace App\Classes;

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;
use App\Models\DailyFdFilter;
use App\Models\TeamFilter;
use App\Classes\Solver;
use App\Classes\SolverTopPlays;
use App\Models\Lineup;
use App\Models\LineupPlayer;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Session;

class NbawowyBuilder {

	public function getStats($name, $startDate, $endDate, $playerOff) {
		// Minutes

        $json = file_get_contents('http://nbawowy.com/api/both/m/poss/q/[1,2,3,4,0,5,6,7]/team/Knicks/vs/[76ers,Bobcats,Bucks,Bulls,Cavaliers,Celtics,Clippers,Grizzlies,Hawks,Heat,Hornets,Jazz,Kings,Knicks,Lakers,Magic,Mavericks,Nets,Nuggets,Pacers,Pelicans,Pistons,Raptors,Rockets,Spurs,Suns,Thunder,Timberwolves,Trail%20Blazers,Warriors,Wizards]/on/[]/off/['.$playerOff.']/from/'.$startDate.'/to/'.$endDate);

        $players = json_decode($json, true);

        foreach ($players as $player) {
        	if ($player['_id']['name'] == $name) {
        		$stats['minutes'] = $player['time'] / 60;

        		break;
        	}
        }

        // Field goals

        $json = file_get_contents('http://nbawowy.com/api/both/fga/q/[1,2,3,4,0,5,6,7]/team/Knicks/vs/[76ers,Bobcats,Bucks,Bulls,Cavaliers,Celtics,Clippers,Grizzlies,Hawks,Heat,Hornets,Jazz,Kings,Knicks,Lakers,Magic,Mavericks,Nets,Nuggets,Pacers,Pelicans,Pistons,Raptors,Rockets,Spurs,Suns,Thunder,Timberwolves,Trail%20Blazers,Warriors,Wizards]/on/[]/off/['.$playerOff.']/from/'.$startDate.'/to/'.$endDate);

        $players = json_decode($json, true);

        foreach ($players as $player) {
        	if ($player['_id']['name'] == $name 
        		&& $player['_id']['made'] == 1
        		&& $player['_id']['value'] == 2) {
        			$stats['2p_made'] = $player['count'];

        			continue;
        	}

        	if ($player['_id']['name'] == $name 
        		&& $player['_id']['made'] == ''
        		&& $player['_id']['value'] == 2) {
        			$stats['2p_missed'] = $player['count'];

        			continue;
        	}

        	if ($player['_id']['name'] == $name 
        		&& $player['_id']['made'] == 1
        		&& $player['_id']['value'] == 3) {
        			$stats['3p_made'] = $player['count'];

        			continue;
        	}

        	if ($player['_id']['name'] == $name 
        		&& $player['_id']['made'] == ''
        		&& $player['_id']['value'] == 3) {
        			$stats['3p_missed'] = $player['count'];

        		continue;
        	}
        }        

        $stats['2p_percentage'] = numFormat($stats['2p_made'] / ($stats['2p_made'] + $stats['2p_missed']), 3);
        $stats['3p_percentage'] = numFormat($stats['3p_made'] / ($stats['3p_made'] + $stats['3p_missed']), 3);

        // Free throws

        $json = file_get_contents('http://nbawowy.com/api/both/fta/q/[1,2,3,4,0,5,6,7]/team/Knicks/vs/[76ers,Bobcats,Bucks,Bulls,Cavaliers,Celtics,Clippers,Grizzlies,Hawks,Heat,Hornets,Jazz,Kings,Knicks,Lakers,Magic,Mavericks,Nets,Nuggets,Pacers,Pelicans,Pistons,Raptors,Rockets,Spurs,Suns,Thunder,Timberwolves,Trail%20Blazers,Warriors,Wizards]/on/[]/off/['.$playerOff.']/from/'.$startDate.'/to/'.$endDate);

        $players = json_decode($json, true);

        $stats['ft_made'] = 0;
        $stats['ft_missed'] = 0;

        foreach ($players as $player) {
        	if ($player['_id']['name'] == $name 
        		&& $player['_id']['made'] == 1) {
        			$stats['ft_made'] += $player['count'];

        			continue;
        	}

        	if ($player['_id']['name'] == $name 
        		&& $player['_id']['made'] == '') {
        			$stats['ft_missed'] += $player['count'];

        			continue;
        	}
        }     

        $stats['ft_percentage'] = numFormat($stats['ft_made'] / ($stats['ft_made'] + $stats['ft_missed']), 3);

        // Rebounds

        $json = file_get_contents('http://nbawowy.com/api/both/reb/q/[1,2,3,4,0,5,6,7]/team/Knicks/vs/[76ers,Bobcats,Bucks,Bulls,Cavaliers,Celtics,Clippers,Grizzlies,Hawks,Heat,Hornets,Jazz,Kings,Knicks,Lakers,Magic,Mavericks,Nets,Nuggets,Pacers,Pelicans,Pistons,Raptors,Rockets,Spurs,Suns,Thunder,Timberwolves,Trail%20Blazers,Warriors,Wizards]/on/[]/off/['.$playerOff.']/from/'.$startDate.'/to/'.$endDate);

        $players = json_decode($json, true);

       	$stats['trb'] = 0;

        foreach ($players as $player) {
        	if ($player['_id']['name'] == $name) {
       			$stats['trb'] += $player['count'];

       			continue;
        	}
        }        	

        // Assists

        $json = file_get_contents('http://nbawowy.com/api/both/ast/q/[1,2,3,4,0,5,6,7]/team/Knicks/vs/[76ers,Bobcats,Bucks,Bulls,Cavaliers,Celtics,Clippers,Grizzlies,Hawks,Heat,Hornets,Jazz,Kings,Knicks,Lakers,Magic,Mavericks,Nets,Nuggets,Pacers,Pelicans,Pistons,Raptors,Rockets,Spurs,Suns,Thunder,Timberwolves,Trail%20Blazers,Warriors,Wizards]/on/[]/off/['.$playerOff.']/from/'.$startDate.'/to/'.$endDate);

        $players = json_decode($json, true);

       	$stats['ast'] = 0;

        foreach ($players as $player) {
        	if ($player['_id']['name'] == $name) {
       			$stats['ast'] += $player['count'];

       			continue;
        	}
        }    

        // Turnovers

        $json = file_get_contents('http://nbawowy.com/api/both/tov/q/[1,2,3,4,0,5,6,7]/team/Knicks/vs/[76ers,Bobcats,Bucks,Bulls,Cavaliers,Celtics,Clippers,Grizzlies,Hawks,Heat,Hornets,Jazz,Kings,Knicks,Lakers,Magic,Mavericks,Nets,Nuggets,Pacers,Pelicans,Pistons,Raptors,Rockets,Spurs,Suns,Thunder,Timberwolves,Trail%20Blazers,Warriors,Wizards]/on/[]/off/['.$playerOff.']/from/'.$startDate.'/to/'.$endDate);

        $players = json_decode($json, true);

       	$stats['tov'] = 0;

        foreach ($players as $player) {
        	if ($player['_id']['name'] == $name) {
       			$stats['tov'] += $player['tov'];

       			continue;
        	}
        }   

        // Steals

        $json = file_get_contents('http://nbawowy.com/api/both/stl/q/[1,2,3,4,0,5,6,7]/team/Knicks/vs/[76ers,Bobcats,Bucks,Bulls,Cavaliers,Celtics,Clippers,Grizzlies,Hawks,Heat,Hornets,Jazz,Kings,Knicks,Lakers,Magic,Mavericks,Nets,Nuggets,Pacers,Pelicans,Pistons,Raptors,Rockets,Spurs,Suns,Thunder,Timberwolves,Trail%20Blazers,Warriors,Wizards]/on/[]/off/['.$playerOff.']/from/'.$startDate.'/to/'.$endDate);

        $players = json_decode($json, true);

       	$stats['stl'] = 0;

        foreach ($players as $player) {
        	if ($player['_id']['name'] == $name) {
       			$stats['stl'] += $player['count'];

       			continue;
        	}
        }  

        // Blocks

        $json = file_get_contents('http://nbawowy.com/api/both/blk/q/[1,2,3,4,0,5,6,7]/team/Knicks/vs/[76ers,Bobcats,Bucks,Bulls,Cavaliers,Celtics,Clippers,Grizzlies,Hawks,Heat,Hornets,Jazz,Kings,Knicks,Lakers,Magic,Mavericks,Nets,Nuggets,Pacers,Pelicans,Pistons,Raptors,Rockets,Spurs,Suns,Thunder,Timberwolves,Trail%20Blazers,Warriors,Wizards]/on/[]/off/['.$playerOff.']/from/'.$startDate.'/to/'.$endDate);

        $players = json_decode($json, true);

       	$stats['blk'] = 0;

        foreach ($players as $player) {
        	if ($player['_id']['name'] == $name) {
       			$stats['blk'] += $player['count'];

       			continue;
        	}
        }  

        $fppm = (($stats['2p_made'] * 2) + 
        	($stats['3p_made'] * 3) +
        	$stats['ft_made'] +
        	($stats['trb'] * 1.2) +
        	($stats['ast'] * 1.5) +
        	($stats['tov'] * -1) +
        	($stats['stl'] * 2) +
        	($stats['blk'] * 2)) / $stats['minutes'];

        $stats['fppm'] = numFormat($fppm);

        return $stats;
	}

}