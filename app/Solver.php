<?php namespace App;

class Solver {

	private $originalPlayers;
	private $lineups;
	private $lineup;
	private $algorithmOrder;

	public function buildFdNbaLineups($players) {
		$originalPlayers = $players;

		for ($firstPlayerIndex = 0; $firstPlayerIndex < 10; $firstPlayerIndex++) { 
			$algorithmOrders = $this->setAlgorithmOrders($firstPlayerIndex);

			# ddAll($algorithmOrders);

			for ($i=0; $i < $firstPlayerIndex; $i++) { 
				unset($players['all'][$i]); 
			}

			foreach ($algorithmOrders as $order) {
				$lineup = array();
				$avgSalaryLeft = 0;
				$salaryToggle = 'lower';

				# ddAll($order);

				foreach ($order as $nthPlayerInLineup => $firstOrSecond) {
					if ($nthPlayerInLineup == 1) {
						$playerIndex = $firstPlayerIndex;

						list($players['all'], $lineup) = 
							$this->addPlayertoLineup($players['all'], 
													 $players['all'][$firstPlayerIndex], 
													 $playerIndex,
													 $nthPlayerInLineup,
													 $lineup);

						$avgSalaryLeft = (60000 - $lineup[$nthPlayerInLineup]->salary) / (9 - $nthPlayerInLineup);

						if ($avgSalaryLeft <= 6700) {
							$salaryToggle = 'lower';
						} else {
							$salaryToggle = 'higher';
						}

						# ddAll($avgSalaryLeft);
						# ddAll($lineup);
						# ddAll($players);

						continue;
					}

					$skippedOne = false;

					if ($nthPlayerInLineup == 9) {
						$position = $this->figureOutLastPosition($lineup);

						# ddAll($avgSalaryLeft);
						# ddAll($players[$position]);

						foreach ($players[$position] as $playerIndex => &$player) {
							if ($player->salary > $avgSalaryLeft) {
								continue;
							}

							foreach ($lineup as $rosterSpot) {
								if ($player->player_id == $rosterSpot->player_id) {
									continue;
								}
							}

							if ($firstOrSecond == 1 && !$skippedOne) {
								unset($lineup[$nthPlayerInLineup]);
								unset($player);

								$skippedOne = true;

								continue;
							}

							foreach ($players['all'] as $value) {
								if ($player->player_id == $value->player_id) {
									list($players[$position], $lineup) = 
										$this->addPlayertoLineup($players[$position], 
																 $players[$position][$playerIndex], 
																 $playerIndex,
																 $nthPlayerInLineup,
																 $lineup);

									break;						
								}
							}

							if(count($lineup) != 9) {
								continue;
							}

							break;
						}

						unset($player);

						break;
					}

					foreach ($players['all'] as $playerIndex => &$player) {
						if ($salaryToggle == 'lower') {
							if ($player->salary > $avgSalaryLeft) {
								continue;
							}
						}

						if ($salaryToggle == 'higher') {
							if ($player->salary < $avgSalaryLeft) {
								continue;
							}
						}	

						list($players['all'], $lineup) = 
							$this->addPlayertoLineup($players['all'], 
													 $players['all'][$playerIndex], 
													 $playerIndex,
													 $nthPlayerInLineup,
													 $lineup);

						$positionCheck = $this->checkForMaxPositions($lineup);

						if (!$positionCheck) {
							unset($lineup[$nthPlayerInLineup]);
							unset($player);

							continue;							
						}

						if ($firstOrSecond == 1 && !$skippedOne) {
							unset($lineup[$nthPlayerInLineup]);
							unset($player);

							$skippedOne = true;

							continue;
						}

						$avgSalaryLeft = $this->calculateAvgSalaryLeft($lineup);

						if ($avgSalaryLeft < 3500) {
							unset($lineup[$nthPlayerInLineup]);
							unset($player);

							continue;							
						}

						if ($avgSalaryLeft <= 6700) {
							$salaryToggle = 'lower';
						} else {
							$salaryToggle = 'higher';
						}

						break;
					}

					unset($player);
				}

				$salaryTotal = 0;
				$fppgMinus1Total = 0;
				$hashTotal = 0;

				foreach ($lineup as $rosterSpot) {
					$salaryTotal += $rosterSpot->salary;
					$fppgMinus1Total += $rosterSpot->fppg_minus1;
					$hashTotal += $rosterSpot->player_id +
										$rosterSpot->salary +
										$rosterSpot->team_id +
										$rosterSpot->opp_team_id +
										$rosterSpot->vr_minus1 +
										$rosterSpot->fppg_minus1;
				}

				$lineup['salary_total'] = $salaryTotal;
				$lineup['fppg_minus1_total'] = $fppgMinus1Total;
				$lineup['hash_total'] = $hashTotal;

				$lineups[] = $lineup;	

				unset($lineup);

				$players = $originalPlayers;

				for ($i=0; $i < $firstPlayerIndex; $i++) { 
					unset($players['all'][$i]); 
				}
			}

			$players = $originalPlayers;
		}

		// remove duplicate lineups

		foreach ($lineups as $lineup) {
			$hashTotals[] = $lineup['hash_total'];
		}

		$hashTotals = array_unique($hashTotals);

		foreach ($hashTotals as $hashTotal) {
			$count = 0;

			foreach ($lineups as $key => $lineup) {
				if ($hashTotal == $lineup['hash_total']) {
					$count++;

					if ($count > 1) {
						unset($lineups[$key]);
					}
				}
			}
		}

		// sort lineups by FPPG-1

		foreach ($lineups as $key => $lineup) {
			$fppg_minus1_total[$key] = $lineup['fppg_minus1_total'];
		}

		array_multisort($fppg_minus1_total, SORT_DESC, $lineups);

		foreach ($lineups as &$lineup) {
			$lineup = $this->reorderLineupByPositionAndFPPG($lineup);
		}

		unset($lineup);

		# ddAll($lineups);

		return $lineups;
	}

	private function reorderLineupByPositionAndFPPG($lineup) {
		$dupLineup = $lineup;

		$lineup = array();

		# ddAll($lineup);

		$positions = [1 => 'PG', 
					  2 => 'SG', 
					  3 => 'SF', 
					  4 => 'PF', 
					  5 => 'C'];

		$count = 0;

		foreach ($positions as $key =>$position) {
			foreach ($dupLineup as $key2 => $dupRosterSpot) {
				if (is_numeric($key2)) {
					if ($position == $dupRosterSpot->position) {
						$index = $count++;

						$lineup[$index] = $dupRosterSpot;
						$lineup[$index]->position_number = $key;
					}					
				}
			}	
		}

		foreach ($lineup as $key => $rosterSpot) {
			if (is_numeric($key)) {
				$positionNumber[$key] = $rosterSpot->position_number;
				$fppg_minus1[$key] = $rosterSpot->fppg_minus1;
			}
		}

		array_multisort($positionNumber, SORT_ASC, $fppg_minus1, SORT_DESC, $lineup);

		$lineup['salary_total'] = $dupLineup['salary_total'];
		$lineup['fppg_minus1_total'] = $dupLineup['fppg_minus1_total'];
		$lineup['hash_total'] = $dupLineup['hash_total'];

		return $lineup;
	}

	private function addPlayertoLineup($players, $player, $playerIndex, $nthPlayerInLineup, $lineup) {
		$lineup[$nthPlayerInLineup] = $player;

		unset($players[$playerIndex]);

		return array($players, $lineup);
	}	

	private function figureOutLastPosition($lineup) {
		$maxPositions['PG'] = 2;
		$maxPositions['SG'] = 2;
		$maxPositions['SF'] = 2;
		$maxPositions['PF'] = 2;
		$maxPositions['C'] = 1;

		foreach ($lineup as $rosterSpot) {
			$maxPositions[$rosterSpot->position]--;
		}

		foreach ($maxPositions as $position => $numPlayers) {
			if ($numPlayers == 1) {
				return $position;
			}	
		}
	}

	private function calculateAvgSalaryLeft($lineup) {
		$totalSalaryUsed = 0;
		$rosterSpotCount = 0;

		foreach ($lineup as $rosterSpot) {
			$totalSalaryUsed += $rosterSpot->salary;

			$rosterSpotCount++;
		}

		return (60000 - $totalSalaryUsed) / (9 - $rosterSpotCount);
	}

	private function checkForMaxPositions($lineup) {
		$maxPositions['PG'] = 2;
		$maxPositions['SG'] = 2;
		$maxPositions['SF'] = 2;
		$maxPositions['PF'] = 2;
		$maxPositions['C'] = 1;

		foreach ($lineup as $rosterSpot) {
			$maxPositions[$rosterSpot->position]--;

			if ($maxPositions[$rosterSpot->position] < 0) {
				return false;
			}
		}

		return true;
	}

	private function setAlgorithmOrders($i) {
		$algorithmOrders = [
			1 => [1 => $i, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0],
			2 => [1 => $i, 2 => 1, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0],
			3 => [1 => $i, 2 => 0, 3 => 1, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0],
			4 => [1 => $i, 2 => 0, 3 => 0, 4 => 1, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0],
			5 => [1 => $i, 2 => 0, 3 => 0, 4 => 0, 5 => 1, 6 => 0, 7 => 0, 8 => 0, 9 => 0],
			6 => [1 => $i, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 1, 7 => 0, 8 => 0, 9 => 0],
			7 => [1 => $i, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 1, 8 => 0, 9 => 0],
			8 => [1 => $i, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 1, 9 => 0],
			9 => [1 => $i, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 1]
		];		

		return $algorithmOrders;
	}

}