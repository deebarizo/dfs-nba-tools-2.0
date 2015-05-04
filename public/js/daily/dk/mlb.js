$(document).ready(function() {

	/********************************************
	CREATE TABLE
	********************************************/

	$.fn.dataTable.ext.order['dom-text-numeric'] = function  ( settings, col )
	{
	    return this.api().column( col, {order:'index'} ).nodes().map( function ( td, i ) {
	        return $(td).text() * 1;
	    } );
	}

	if (areThereBoxScoreLines == 0) {
		$('#daily').dataTable({
			"scrollY": "600px",
			"paging": false,
			"order": [[7, "desc"]],
			"columns": [
			    { "width": "30%" },
			    { "width": "10%" },
			    { "width": "10%", "orderDataType": "dom-text-numeric" },
			    { "width": "10%" },
			   	{ "width": "10%" },
			   	{ "width": "10%" },
			   	{ "width": "10%" },
			   	{ "width": "10%" }
			]
		});	
	}

	if (areThereBoxScoreLines == 1) {
		$('#daily').dataTable({
			"scrollY": "600px",
			"paging": false,
			"order": [[7, "desc"]],
			"columns": [
			    { "width": "19%" },
			    { "width": "7%" },
			    { "width": "6%", "orderDataType": "dom-text-numeric" },
			    { "width": "7%" },
			   	{ "width": "5%" },
			   	{ "width": "7%" },
			   	{ "width": "6%" },
			   	{ "width": "7%" },
			   	{ "width": "7%" },
			   	{ "width": "7%" },
			   	{ "width": "7%" },
			   	{ "width": "4%" },
			   	{ "width": "4%" },
			   	{ "width": "4%" },
			   	{ "width": "4%" }
			]
		});	
	}

	$('#daily_filter').hide();


	/********************************************
	BOX SCORE LINES TOOLTIP (MLB)
	********************************************/

    $('a.box-score-line-tooltip').each(function() {
        $(this).qtip({
            content: {
                text: $(this).next('div.box-score-line-tooltip')
            },
		    position: {
		        my: 'right top'
		    }
        });
    });


	/********************************************
	TARGET PERCENTAGE TOOLTIP
	********************************************/

    $('.target-percentage-qtip').each(function() {
        $(this).qtip({
            content: {
                text: $(this).parent().next('.edit-target-percentage-tooltip'),
                button: true
            },
            show: 'click',
            hide: {
            	event: false
            }
        }).bind('click', function(event) { event.preventDefault(); return false; });
    }); 

    $('.edit-target-percentage-input').keypress(function(event) {
        if (event.which == 13) {
			var rawDataHasQtip = $(this).closest('div.qtip').attr('id');
			var dataHasQtip = rawDataHasQtip.replace(/qtip-/gi, '');

			var playerRow = $('a[data-hasqtip='+dataHasQtip+']').closest('tr');

			var dkMlbPlayersId = playerRow.data('dk-mlb-player-id');

			var targetPercentage = $(this).val();

        	var spanTargetPercentage = playerRow.find('span.target-percentage-amount');
			$(spanTargetPercentage).hide();
			$(spanTargetPercentage).after('<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />');

			updateTargetPercentage(dataHasQtip, playerRow, dkMlbPlayersId, targetPercentage, 'tooltip');
		}
    });

	$(".edit-target-percentage-button").click(function(e) {
		e.preventDefault();

		var e = jQuery.Event('keypress');
		e.which = 13;
		$(this).prev(".edit-target-percentage-input").focus();
		$(this).prev(".edit-target-percentage-input").trigger(e);
	});


	/********************************************
	ADD/REMOVE TOP PLAYS
	********************************************/

	$(".daily-lock").click(function(e) {
		e.preventDefault();

		var defaultTargetPercentage = $('input.default-target-percentage').val();

		if (defaultTargetPercentage <= 0) {
			return;
		}

		var dkMlbPlayersId = $(this).closest('tr').data('dk-mlb-player-id');

		$(this).hide();
		$(this).after('<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />');

		var playerActive = $(this).hasClass("daily-lock-active");
		if (playerActive) {
			playerActive = 1;
			var targetPercentage = 0;
		} else {
			playerActive = 0;
			var targetPercentage = defaultTargetPercentage;
		}

		var dataHasQtip = $(this).closest('td').find('a.target-percentage-qtip').data('hasqtip');

		var playerRow = $(this).closest('tr.player-row');

		updateTargetPercentage(dataHasQtip, playerRow, dkMlbPlayersId, targetPercentage, 'lock');
	});


	/********************************************
	UDPATE TARGET PERCENTAGE
	********************************************/

	function updateTargetPercentage(dataHasQtip, playerRow, dkMlbPlayersId, targetPercentage, event) {
    	$.ajax({
            url: baseUrl+'/daily/dk/mlb/update_target_percentage_for_dk_mlb/',
            data: {
            	dkMlbPlayersId: dkMlbPlayersId,
            	targetPercentage: targetPercentage
            },
            type: 'POST',
            success: function() {
            	playerRow.find('span.target-percentage-amount').text(targetPercentage);

            	playerRow.find('input.edit-target-percentage-input').val(targetPercentage);

            	$('#qtip-'+dataHasQtip+'-content').find('input.edit-target-percentage-input').val(targetPercentage);

				showTotalTargetPercentage();

				var lockButton = playerRow.find('.daily-lock');

				if (event == 'lock') {
					lockButton.toggleClass("daily-lock-active");
			       	lockButton.siblings('img').remove();
					lockButton.show();
				}

				if (event == 'tooltip' && targetPercentage == 0) {
					lockButton.removeClass("daily-lock-active");
				}

				if (event == 'tooltip' && targetPercentage > 0) {
					lockButton.addClass("daily-lock-active");
				}

				if (event == 'tooltip') {
        			var spanTargetPercentage = playerRow.find('span.target-percentage-amount');
					$(spanTargetPercentage).siblings('img').remove();
					$(spanTargetPercentage).show();				
				}
            }
        });		
	}


	/********************************************
	SHOW TOTAL TARGET PERCENTAGE
	********************************************/

	showTotalTargetPercentage();

	function showTotalTargetPercentage() {
		addTargetPercentagesOfPositions();

		addTargetPercentagesOfAll();
	}

	function addTargetPercentagesOfPositions() {
		var totalPercentagesByPosition = {
			SP: {
				percentage: 0,
				salary: 0,
				maxPercentage: 200
			},
			C: {
				percentage: 0,
				salary: 0,
				maxPercentage: 100
			},
			'1B': {
				percentage: 0,
				salary: 0,
				maxPercentage: 100
			},
			'2B': {
				percentage: 0,
				salary: 0,
				maxPercentage: 100
			},
			'3B': {
				percentage: 0,
				salary: 0,
				maxPercentage: 100
			},
			SS: {
				percentage: 0,
				salary: 0,
				maxPercentage: 100
			},
			OF: {
				percentage: 0,
				salary: 0,
				maxPercentage: 300
			}
		};

		var positions = ['SP', 'C', '1B', '2B', '3B', 'SS', 'OF'];

		for (var i = 0; i < positions.length; i++) {
			$('td.target-percentage-amount').each(function() {
				var positionOfPlayer = $(this).closest('tr.player-row').data('position');

				var salary = $(this).closest('tr.player-row').data('salary');

				var targetPercentageAmount = $(this).text();
				
				if (positionOfPlayer.indexOf('/') > -1) {
					var splitPosition = [
						positionOfPlayer.replace(/\w+\//, ''),
						positionOfPlayer3 = positionOfPlayer.replace(/\/\w+/, '')
					];

					for (var n = 0; n < splitPosition.length; n++) {
						if (positions[i] == splitPosition[n]) {
							totalPercentagesByPosition[positions[i]]['percentage'] += addTargetPercentage(targetPercentageAmount) / 2;

							var weightedSalary = (salary * addTargetPercentage(targetPercentageAmount) / 100) / 2;

							totalPercentagesByPosition[positions[i]]['salary'] += weightedSalary;
						}
					};

					return;
				} 

				if (positions[i] == positionOfPlayer) {
					totalPercentagesByPosition[positions[i]]['percentage'] += addTargetPercentage(targetPercentageAmount);

					var weightedSalary = salary * addTargetPercentage(targetPercentageAmount) / 100;

					totalPercentagesByPosition[positions[i]]['salary'] += weightedSalary;
				}	
			});
		};

		// console.log(totalPercentagesByPosition);

		for (var i = 0; i < positions.length; i++) {
			$('span.total-target-percentage-'+positions[i]).text(totalPercentagesByPosition[positions[i]]['percentage']);

			if (totalPercentagesByPosition[positions[i]]['percentage'] == totalPercentagesByPosition[positions[i]]['maxPercentage']) {
				$('span.total-target-percentage-with-percentage-sign-'+positions[i]).css('color', 'green');
				continue;
			}

			if (totalPercentagesByPosition[positions[i]]['percentage'] < totalPercentagesByPosition[positions[i]]['maxPercentage']) {
				$('span.total-target-percentage-with-percentage-sign-'+positions[i]).css('color', 'blue');
				continue;
			}

			if (totalPercentagesByPosition[positions[i]]['percentage'] > totalPercentagesByPosition[positions[i]]['maxPercentage']) {
				$('span.total-target-percentage-with-percentage-sign-'+positions[i]).css('color', 'red');
				continue;
			}
		};

		var totalWeightedSalary = 0;

		for (var i = 0; i < positions.length; i++) {
			totalWeightedSalary += totalPercentagesByPosition[positions[i]]['salary'];
		};

		$('span.total-weighted-salary').text(totalWeightedSalary);

		if (totalWeightedSalary <= 50000 && totalWeightedSalary >= 49500) {
			$('span.total-weighted-salary-with-percentage-sign').css('color', 'green');
			return;
		}

		if (totalWeightedSalary > 50000) {
			$('span.total-weighted-salary-with-percentage-sign').css('color', 'red');
			return;
		}

		if (totalWeightedSalary < 50000) {
			$('span.total-weighted-salary-with-percentage-sign').css('color', 'blue');
			return;
		}
	}

	function addTargetPercentagesOfAll() {
		var totalPercentage = 0;

		$('td.target-percentage-amount').each(function() {
			var salary = $(this).closest('tr.player-row').data('salary');
			var targetPercentageAmount = $(this).text();

			totalPercentage += addTargetPercentage(targetPercentageAmount);		
		});		

		$('span.total-target-percentage').text(totalPercentage);

		if (totalPercentage == 1000) {
			$('span.total-target-percentage-with-percentage-sign').css('color', 'green');
			return;
		}

		if (totalPercentage > 1000) {
			$('span.total-target-percentage-with-percentage-sign').css('color', 'red');
			return;
		}

		if (totalPercentage < 1000) {
			$('span.total-target-percentage-with-percentage-sign').css('color', 'blue');
			return;
		}
	}

	function addTargetPercentage(targetPercentageAmount) {
		if (targetPercentageAmount == '---') {
			return 0;
		}

		return parseInt(targetPercentageAmount);
	}


	/********************************************
	FILTERS
	********************************************/

	var position;
	var team;
	var showOnlyTopPlays;
	var filter = {};

	function runFilter() {
		filter = getFilter();

		$('tr.player-row').removeClass('hide-player-row');

		runPositionFilter(filter);
		runTeamFilter(filter);
		runTopPlaysFilter(filter);
		runSalaryInputFilter(filter);

		showTotalTargetPercentage();
	}

	function getFilter() {
		position = $('select.position-filter').val();
		team = $('select.team-filter').val();
		showOnlyTopPlays = $('select.top-plays-filter').val();
		salaryInput = {
			salary: $('.salary-input').val(), 
			salaryToggle: $('input:radio[name=salary-toggle]:checked').val()
		};

		filter = {
			position: position,
			team: team,
			showOnlyTopPlays: showOnlyTopPlays,
			salaryInput: salaryInput
		};

		// console.log(filter);

		return filter;
	}


	//// Position filter ////

	$('select.position-filter').on('change', function() {
		runFilter();
	});

	function runPositionFilter(filter) {
		if (filter.position == 'All') {
			return;
		}

		$('tr.player-row').each(function() {
			var playerRow = $(this);

			hidePositionsNotSelected(playerRow, filter.position);
		});				
	}

	function hidePositionsNotSelected(playerRow, position) {
		var playerRowPosition = $(playerRow).data('position');

		if (playerRowPosition == position) {
			return;
		}

		if (position == 'Hitters') {
			if (playerRowPosition != 'SP' && playerRowPosition != 'RP') {
				return;
			}
		}

		playerRowPosition2 = playerRowPosition.replace(/\w+\//, '');

		if (playerRowPosition2 == position) {
			return;
		}

		playerRowPosition3 = playerRowPosition.replace(/\/\w+/, '');

		if (playerRowPosition3 == position) {
			return;
		}

		$(playerRow).addClass('hide-player-row');
	}


	//// Team filter ////

	$('select.team-filter').on('change', function() {
		runFilter();
	});

	function runTeamFilter(filter) {
		if (filter.team == 'All') {
			return;
		}

		$('tr.player-row').each(function() {
			var playerRow = $(this);

			hideTeamsNotSelected(playerRow, filter.team);
		});				
	}

	function hideTeamsNotSelected(playerRow, team) {
		var playerRowTeam = $(playerRow).data('abbr-dk');

		if (playerRowTeam != team) {
			$(playerRow).addClass('hide-player-row');
		}
	}


	//// Top plays filter ////

	$('select.top-plays-filter').on('change', function() {
		runFilter();
	});

	function runTopPlaysFilter(filter) {
		if (filter.showOnlyTopPlays == 0) {
			return;
		}

		$('tr.player-row').each(function() {
			var playerRow = $(this);

			hideNonTopPlays(playerRow);
		});						
	}

	function hideNonTopPlays(playerRow) {
		var isPlayerTopPlay = $(playerRow).find('span.daily-lock').hasClass('daily-lock-active');

		if (isPlayerTopPlay === false) {
			$(playerRow).addClass('hide-player-row');
		}
	}


	//// Salary input filter ////

	$('.salary-input').on('input', function() {
		runFilter();
	});

	$("input[name=salary-toggle]:radio").change(function() {
		runFilter();
	});

	function runSalaryInputFilter(filter) {
		$('tr.player-row').each(function() {
			var playerRow = $(this);

			hideBasedOnSalaryInput(playerRow, filter['salaryInput']);
		});		
	}

	function hideBasedOnSalaryInput(playerRow, salaryInput) {
		var salary = parseInt((playerRow).data('salary'));

		if (salary < salaryInput['salary'] && salaryInput['salaryToggle'] == 'greater-than') {
			$(playerRow).addClass('hide-player-row');

			return;
		}

		if (salary >= salaryInput['salary'] && salaryInput['salaryToggle'] == 'less-than') {
			$(playerRow).addClass('hide-player-row');
		}
	}


	//// Salary reset button ////

	$('.salary-reset').on('click', function(event) { 
		$('.salary-input').val(0);
		$('#greater-than').prop('checked', true);

		runFilter();
	});

});