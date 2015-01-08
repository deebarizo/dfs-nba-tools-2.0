$(document).ready(function() {

	/********************************************
	CREATE TABLE
	********************************************/

	$('#daily').dataTable({
		"scrollY": "600px",
		"paging": false,
		"order": [[9, "desc"]]
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
        });
    }); 

    $('.edit-target-percentage-input').keypress(function (event) {
        if (event.which == 13) {
			var rawDataHasQtip = $(this).parent('div.edit-target-percentage-tooltip').parent('div.qtip-content').parent('div.qtip').attr('id');

			var dataHasQtip = rawDataHasQtip.replace(/qtip-/gi, '');

			var playerFdIndex = $('a[data-hasqtip='+dataHasQtip+']').parent('span.target-percentage-group').parent('td').parent('tr').data('player-fd-index');

			// var targetPercentageAmount = $('a[data-hasqtip='+dataHasQtip+']').parent('span.target-percentage-group').prev('span.target-percentage-amount').text();

			// $(this).val(targetPercentageAmount);

			var newTargetPercentage = $(this).val();

			updateTargetPercentage(newTargetPercentage, dataHasQtip, playerFdIndex);
		}
    });

	$(".edit-target-percentage-button").click(function(e) {
		e.preventDefault();

		var e = jQuery.Event('keypress');
		e.which = 13;
		$(this).prev(".edit-target-percentage-input").focus();
		$(this).prev(".edit-target-percentage-input").trigger(e);
	});

	function updateTargetPercentage(newTargetPercentage, dataHasQtip, playerFdIndex) {
		$('a[data-hasqtip='+dataHasQtip+']').parent('span.target-percentage-group').addClass('hide-target-percentage-group');
		$('a[data-hasqtip='+dataHasQtip+']').parent('span.target-percentage-group').prev('span.target-percentage-amount').html('<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />');

		$.ajax({
            url: baseUrl+'/daily_fd_nba/update_target_percentage/'+playerFdIndex+'/'+newTargetPercentage,
            type: 'POST',
            success: function() {
            	$('a[data-hasqtip='+dataHasQtip+']').parent('span.target-percentage-group').prev('span.target-percentage-amount').html('');

            	var targetPercentageTooltipInput = $('div#qtip-'+dataHasQtip+'-content').children('div.edit-target-percentage-tooltip').children('input.edit-target-percentage-input');

            	if (newTargetPercentage == 0) {
            		$(targetPercentageTooltipInput).val(0);

            		newTargetPercentage = '---';

            		$('a[data-hasqtip='+dataHasQtip+']').parent('span.target-percentage-group').addClass('hide-target-percentage-group');
            	} else {
            		$(targetPercentageTooltipInput).val(newTargetPercentage);

            		$('a[data-hasqtip='+dataHasQtip+']').parent('span.target-percentage-group').removeClass('hide-target-percentage-group');
            	}

				$('a[data-hasqtip='+dataHasQtip+']').parent('span.target-percentage-group').prev('span.target-percentage-amount').text(newTargetPercentage);

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

		var playerFdIndex = $(this).parent().parent().parent().data('player-fd-index');
		var isPlayerActive = $(this).hasClass("daily-lock-active");

		$(this).hide();
		$(this).after('<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />');

		var $this = $(this);
		
    	$.ajax({
            url: baseUrl+'/daily_fd_nba/update_top_plays/'+playerFdIndex+'/'+isPlayerActive,
            type: 'POST',
            success: function() {
				$this.toggleClass("daily-lock-active");

				$this.show();

				var spanTargetPercentageAmount = $this.parent('a').parent('td').next('td').children('span.target-percentage-amount');
				var spanTargetPercentageGroup = $this.parent('a').parent('td').next('td').children('span.target-percentage-group');
				var editTargetPercentageInput = $this.parent('a').parent('td').next('td').children('div.edit-target-percentage-tooltip').children('input.edit-target-percentage-input');

				if ($this.hasClass('daily-lock-active')) {
					$(spanTargetPercentageAmount).text(defaultTargetPercentage);
					$(spanTargetPercentageGroup).removeClass('hide-target-percentage-group');
					$(editTargetPercentageInput).val(defaultTargetPercentage);
				} else {
					$(spanTargetPercentageAmount).text('---');
					$(spanTargetPercentageGroup ).addClass('hide-target-percentage-group');
					$(editTargetPercentageInput).val('0');
				}

				$this.next('img').remove();

				var targetPercentageAmount = $this.parent('a').parent('td').next('td').children('span.target-percentage-amount').text();

				if (targetPercentageAmount == '---') {
					var newTargetPercentage = 0;
				} else {
					var newTargetPercentage = targetPercentageAmount;
				}

				var dataHasQtip = $this.parent('a').parent('td').next('td').children('span.target-percentage-group').children('a.target-percentage-qtip').data('hasqtip');

				updateTargetPercentage(newTargetPercentage, dataHasQtip, playerFdIndex);
            }
        });

		
	});


	/********************************************
	SHOW TOTAL TARGET PERCENTAGE
	********************************************/

	showTotalTargetPercentage();

	function showTotalTargetPercentage() {
		var position = $('select.position-filter').val();

		var totalTargetPercentage = addTargetPercentages(position);
		totalTargetPercentage = Math.round(totalTargetPercentage);
		totalTargetPercentage = totalTargetPercentage+'%';
		
		if (position == 'All') {
			totalTargetPercentage = 'N/A';
		}

		$('span.total-target-percentage').text(totalTargetPercentage);
	}

	function addTargetPercentages(position) {
		var totalTargetPercentage = 0;

		$('span.target-percentage-amount:visible').each(function() {
			var targetPercentageAmount = $(this).text();

			totalTargetPercentage += addTargetPercentage(targetPercentageAmount);
		});			

		return totalTargetPercentage;
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
	}

	function getFilter() {
		position = $('select.position-filter').val();
		team = $('select.team-filter').val();
		showOnlyTopPlays = $('select.top-plays-filter').val();

		filter = {
			position: position,
			team: team,
			showOnlyTopPlays: showOnlyTopPlays
		};

		return filter;
	}


	//// Position filter ////

	$('select.position-filter').on('change', function() {
		runFilter();

		showTotalTargetPercentage();
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

});