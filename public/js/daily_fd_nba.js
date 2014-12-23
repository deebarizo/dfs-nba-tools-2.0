$(document).ready(function() {

	/********************************************
	CREATE TABLE
	********************************************/

	$('#daily').dataTable({
		"scrollY": "600px",
		"paging": false,
		"order": [[8, "desc"]]
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

				var targetPercentageAmount = $this.parent('a').parent('td').next('td').children('span.target-percentage-amount');
				var targetPercentageGroup = $this.parent('a').parent('td').next('td').children('span.target-percentage-group');

				if ($this.hasClass('daily-lock-active')) {
					$(targetPercentageAmount).text('0');
					$(targetPercentageGroup).removeClass('hide-target-percentage-group');
				} else {
					$(targetPercentageAmount).text('---');
					$(targetPercentageGroup).addClass('hide-target-percentage-group');
				}

				$this.next('img').remove();
            }
        }); 
	});

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