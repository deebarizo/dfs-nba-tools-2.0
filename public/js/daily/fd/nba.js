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

	$('#daily').dataTable({
		// "scrollY": "600px",
		"paging": false,
		"order": [[13, "desc"]],
		"columns": [
		    { "width": "44%" },
		    { "width": "2%" },
		    { "width": "5%", "orderDataType": "dom-text-numeric" },
		    { "width": "7%" },
		   	{ "width": "16%" },
		   	{ "width": "16%" },
		   	{ "width": "1%" },
		   	{ "width": "1%" },
		   	{ "width": "1%" },
		   	{ "width": "1%" },
		   	{ "width": "1%" },
		   	{ "width": "1%" },
		   	{ "width": "1%" },
		   	{ "width": "1%" },
		   	{ "width": "1%" },
		   	{ "width": "1%" }
		]
	});

	$('#daily_filter').hide();


	/********************************************
	PLAYER STATS FILTER TOOLTIP
	********************************************/

    $('.player-filter').each(function() {
        $(this).qtip({
            content: {
                text: $(this).next('.player-filter-tooltip')
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

    $('.edit-target-percentage-input').keypress(function (event) {
        if (event.which == 13) {
			var rawDataHasQtip = $(this).closest('div.qtip').attr('id');

			var dataHasQtip = rawDataHasQtip.replace(/qtip-/gi, '');

			var playerRow = $('a[data-hasqtip='+dataHasQtip+']').closest('tr');

			var playerFdIndex = playerRow.data('player-fd-index');

			var newTargetPercentage = $(this).val();

			// console.log(playerFdIndex); return;

			updateTargetPercentage(newTargetPercentage, dataHasQtip, playerFdIndex, playerRow);
		}
    });

	$(".edit-target-percentage-button").click(function(e) {
		e.preventDefault();

		var e = jQuery.Event('keypress');
		e.which = 13;
		$(this).prev(".edit-target-percentage-input").focus();
		$(this).prev(".edit-target-percentage-input").trigger(e);
	});

	function updateTargetPercentage(newTargetPercentage, dataHasQtip, playerFdIndex, playerRow) {
		$('a[data-hasqtip='+dataHasQtip+']').closest('td').siblings('td.target-percentage-amount').html('<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />');

		$.ajax({
            url: baseUrl+'/daily/fd/nba/update_target_percentage/'+playerFdIndex+'/'+newTargetPercentage,
            type: 'POST',
            success: function() {
            	$('a[data-hasqtip='+dataHasQtip+']').closest('td').siblings('td.target-percentage-amount').html('');

            	var targetPercentageTooltipInput = $('div#qtip-'+dataHasQtip+'-content').children('div.edit-target-percentage-tooltip').children('input.edit-target-percentage-input');

           		$(targetPercentageTooltipInput).val(newTargetPercentage);

            	if (newTargetPercentage == 0 && playerRow != null) {
            		playerRow.find('span.daily-lock').removeClass("daily-lock-active");
            	} else if (newTargetPercentage > 0 && playerRow != null) {
            		playerRow.find('span.daily-lock').addClass("daily-lock-active");
            	}

				$('a[data-hasqtip='+dataHasQtip+']').closest('td').siblings('td.target-percentage-amount').text(newTargetPercentage);

				showTotalTargetPercentage();
            }
        });	
	}


	/********************************************
	TOGGLE DTD PLAYERS
	********************************************/

	$(".show-toggle-dtd-players").click(function(){
	  $("#daily-dtd").toggle();
	}); 


	/********************************************
	ADD/REMOVE TOP PLAYS
	********************************************/

	$(".daily-lock").click(function(e) {
		e.preventDefault();

		var playerFdIndex = $(this).closest('tr').data('player-fd-index');
		var isPlayerActive = $(this).hasClass("daily-lock-active");

		$(this).hide();
		$(this).after('<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />');

		var defaultTargetPercentage = $('input.default-target-percentage').val();

		var $this = $(this);
		
    	$.ajax({
            url: baseUrl+'/daily/fd/nba/update_top_plays/'+playerFdIndex+'/'+isPlayerActive,
            type: 'POST',
            success: function() {
            	$this.toggleClass("daily-lock-active");

            	$this.siblings('img').remove();

            	$this.show();

				var tdTargetPercentageAmount = $this.parent('a').parent('td').siblings('td.target-percentage-amount');
				var editTargetPercentageInput = $this.closest('a').siblings('div.edit-target-percentage-tooltip').find('input.edit-target-percentage-input');

				if ($this.hasClass('daily-lock-active')) {
					$(tdTargetPercentageAmount).text(defaultTargetPercentage);
					$(editTargetPercentageInput).val(defaultTargetPercentage);
				} else {
					$(tdTargetPercentageAmount).text(0);
					$(editTargetPercentageInput).val('0');
				}

				var targetPercentageAmount = tdTargetPercentageAmount.text();

				var dataHasQtip = $this.parent('a').parent('td').next('td').children('span.target-percentage-group').children('a.target-percentage-qtip').data('hasqtip');

				updateTargetPercentage(targetPercentageAmount, dataHasQtip, playerFdIndex, null);

				showTotalTargetPercentage();
            }
        });

		
	});


	/********************************************
	SHOW TOTAL TARGET PERCENTAGE
	********************************************/

	showTotalTargetPercentage();

	function showTotalTargetPercentage() {
		addTargetPercentagesOfPositions();

		addTargetPercentagesOfSalaries();
		
		addTargetPercentagesOfAll();
	}

	function addTargetPercentagesOfPositions() {
		var totalPercentagesByPosition = {
			PG: {
				percentage: 0,
				salary: 0
			},
			SG: {
				percentage: 0,
				salary: 0
			},
			SF: {
				percentage: 0,
				salary: 0
			},
			PF: {
				percentage: 0,
				salary: 0
			},
			C: {
				percentage: 0,
				salary: 0
			}
		};

		var positions = ['PG', 'SG', 'SF', 'PF', 'C'];

		for (var i = 0; i < positions.length; i++) {
			$('td.target-percentage-amount').each(function() {
				var positionOfPlayer = $(this).closest('tr').data('player-position');

				var salary = $(this).closest('td').siblings('td.salary').text();

				if (positions[i] == positionOfPlayer) {
					var targetPercentageAmount = $(this).text();

					totalPercentagesByPosition[positions[i]]['percentage'] += addTargetPercentage(targetPercentageAmount);

					var weightedSalary = salary * addTargetPercentage(targetPercentageAmount) / 100;

					totalPercentagesByPosition[positions[i]]['salary'] += weightedSalary;
				}	
			});
		};

		// console.log(totalPercentagesByPosition);

		for (var i = 0; i < positions.length; i++) {
			$('span.total-target-percentage-'+positions[i]).text(totalPercentagesByPosition[positions[i]]['percentage']);
		};

		var totalWeightedSalary = 0;

		for (var i = 0; i < positions.length; i++) {
			totalWeightedSalary += totalPercentagesByPosition[positions[i]]['salary'];
		};

		$('span.total-weighted-salary').text(totalWeightedSalary);
	}

	function addTargetPercentagesOfSalaries() {
		var totalPercentagesBySalaries = {
			plus: 0,
			minus: 0
		};

		var salaries = ['plus', 'minus'];

		$('td.target-percentage-amount').each(function() {
			var salary = $(this).closest('td').siblings('td.salary').text();

			if (salary >= 6500) {
				var targetPercentageAmount = $(this).text();

				totalPercentagesBySalaries['plus'] += addTargetPercentage(targetPercentageAmount);		
			}	
		});

		$('td.target-percentage-amount').each(function() {
			var salary = $(this).closest('td').siblings('td.salary').text();

			if (salary < 6500) {
				var targetPercentageAmount = $(this).text();

				totalPercentagesBySalaries['minus'] += addTargetPercentage(targetPercentageAmount);		
			}	
		});		

		for (var i = 0; i < salaries.length; i++) {
			$('span.total-target-percentage-'+salaries[i]).text(totalPercentagesBySalaries[salaries[i]]);
		};
	}

	function addTargetPercentagesOfAll() {
		var totalPercentage = 0;

		$('td.target-percentage-amount').each(function() {
			var salary = $(this).closest('td').siblings('td.salary').text();
			var targetPercentageAmount = $(this).text();

			totalPercentage += addTargetPercentage(targetPercentageAmount);		
		});		

		$('span.total-target-percentage').text(totalPercentage);
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
		runTimeFilter(filter);

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
		time = $('select.time-filter').val();

		filter = {
			position: position,
			team: team,
			showOnlyTopPlays: showOnlyTopPlays,
			salaryInput: salaryInput,
			time: time
		};

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
		var playerRowPosition = $(playerRow).data('player-position');

		if (playerRowPosition != position) {
			$(playerRow).addClass('hide-player-row');
		}
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
		var playerRowTeam = $(playerRow).data('player-team');

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
		var salary = parseInt($(playerRow).find('td.salary').text());

		if (salary < salaryInput['salary'] && salaryInput['salaryToggle'] == 'greater-than') {
			$(playerRow).addClass('hide-player-row');

			return;
		}

		if (salary > salaryInput['salary'] && salaryInput['salaryToggle'] == 'less-than') {
			$(playerRow).addClass('hide-player-row');
		}
	}


	//// Salary reset button ////

	$('.salary-reset').on('click', function(event) { 
		$('.salary-input').val(0);
		$('#greater-than').prop('checked', true);

		runFilter();
	});


	//// Time filter ////

	$('select.time-filter').on('change', function() {
		runFilter();
	});

	function runTimeFilter(filter) {
		if (filter.time == 'All') {
			return;
		}

		$('tr.player-row').each(function() {
			var playerRow = $(this);

			hideTimesNotSelected(playerRow, filter.time);
		});				
	}

	function hideTimesNotSelected(playerRow, time) {
		var playerRowTime = $(playerRow).find('td.time').text();

		if (playerRowTime != time) {
			$(playerRow).addClass('hide-player-row');
		}
	}

});