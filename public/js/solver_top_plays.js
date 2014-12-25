$(document).ready(function() {

	/****************************************************************************************
	TOGGLE NAVBAR
	****************************************************************************************/

	$(".toggle-navbar-link").click(function(e) {
		e.preventDefault();

		$('.navbar').toggle();

		var isNavbarVisible = $(".navbar").is(":visible");

		if (isNavbarVisible) {
			$("h2:").css({"margin-top":"20px"});

			return;
		}
		
		$("h2:").css({"margin-top":"10px"});
	});


	/****************************************************************************************
	EDIT BUY IN
	****************************************************************************************/

	$(".edit-buy-in-link").click(function(e) {
		e.preventDefault();

		$(".edit-buy-in").toggleClass("form-hidden");
	});

    $('.edit-buy-in-input').keypress(function (event) {
        if (event.which == 13) {
			buyIn = $(this).val();

			$.ajax({
	            url: baseUrl+'/solver_top_plays/update_buy_in/'+playerPoolId+'/'+buyIn,
	            type: 'POST',
	            success: function() {
	            	$(".edit-buy-in").addClass("form-hidden");

	            	$("span.buy-in-amount").text(buyIn).fadeIn();
	            }
	        }); 
        }
    });

	$(".edit-buy-in-button").click(function(e) {
		e.preventDefault();

		var e = jQuery.Event('keypress');
		e.which = 13;
		$(".edit-buy-in-input").focus();
		$(".edit-buy-in-input").trigger(e);
	});


	/****************************************************************************************
	UPDATE UNSPENT BUY IN
	****************************************************************************************/

	function updateUnspentBuyIn() {
		var buyIn = $("span.buy-in-amount").text();
		buyIn = parseInt(buyIn);

		var spentBuyIn = 0;
		spentBuyIn = parseInt(spentBuyIn);

		$(".active-lineup").each(function() {
			var lineupBuyIn = $(this).find("span.lineup-buy-in-amount").text();	

			spentBuyIn += parseInt(lineupBuyIn);
		});

		var unspentBuyIn = buyIn - spentBuyIn;

		$("span.unspent-buy-in-amount").text(unspentBuyIn);
	}


	/****************************************************************************************
	EDIT DEFAULT LINEUP BUY IN
	****************************************************************************************/

	$(".edit-default-lineup-buy-in-link").click(function(e) {
		e.preventDefault();

		$(".edit-default-lineup-buy-in").toggleClass("form-hidden");
	});

    $('.edit-default-lineup-buy-in-input').keypress(function (event) {
        if (event.which == 13) {
			defaultLineupBuyIn = $(this).val();

			$.ajax({
	            url: baseUrl+'/solver_top_plays/add_default_lineup_buy_in/'+defaultLineupBuyIn,
	            type: 'POST',
	            success: function() {
	            	$(".edit-default-lineup-buy-in").addClass("form-hidden");

	            	var defaultLineupBuyInPercentage = defaultLineupBuyIn / buyIn * 100;
	            	defaultLineupBuyInPercentage = defaultLineupBuyInPercentage.toFixed(2);

	            	$("span.default-lineup-buy-in-amount").text(defaultLineupBuyIn).fadeIn();
	            	$("span.default-lineup-buy-in-percentage").text(defaultLineupBuyInPercentage).fadeIn();
	            }
	        }); 
        }
    });

	$(".edit-default-lineup-buy-in-button").click(function(e) {
		e.preventDefault();

		var e = jQuery.Event('keypress');
		e.which = 13;
		$(".edit-default-lineup-buy-in-input").focus();
		$(".edit-default-lineup-buy-in-input").trigger(e);
	});


	/****************************************************************************************
	FILTERS
	****************************************************************************************/

	var players = {
		show: [],
		hide: []
	};
	var playerFilterTypes = ['show', 'hide'];
	var filter = {
		lineupType: '',
		players: {
			show: [],
			hide: []
		}
	};
	var lineupType;
	
	function runFilter() {
		filter = getFilter();

		$('table.lineup').removeClass('hide-lineup');

		runPlayerFilter(filter);
		
		runLineupTypeFilter(filter);
	}

	function getFilter() {
		lineupType = $('select.lineup-type-filter').val();

		filter.lineupType = lineupType;

		filter.players.show.length = 0;
		filter.players.hide.length = 0;

		for (var i = 0; i < playerFilterTypes.length; i++) {
			players[playerFilterTypes[i]] = getPlayers(playerFilterTypes[i]);
		}

		filter.players = players;

		return filter;
	}

	function getPlayers(playerFilterType) {
		var numSelectedPlayers = $('.selected-player-to-'+playerFilterType).length;

		if (numSelectedPlayers == 0) {
			return [];
		}

		for (var i = 0; i < numSelectedPlayers; i++) {
			var playerId = $('.selected-player-to-'+playerFilterType).eq(i).data('player-id');
			var playerName = $('.selected-player-to-'+playerFilterType).eq(i).text();

			players[playerFilterType][i] = {
				id: playerId,
				name: playerName
			};
		}

		return players[playerFilterType];
	}


	/********************************************
	PLAYER FILTER
	********************************************/

	$('select.player-filter').on('change', function() {
		var selectedPlayer = {
			id: $(this).find('option:selected').val(),
			name: $(this).find('option:selected').text()
		}

		if ($(this).hasClass('show-player-filter')) {
			addPlayerToView('show', selectedPlayer);
		}

		if ($(this).hasClass('hide-player-filter')) {
			addPlayerToView('hide', selectedPlayer);
		}

		$('select.player-filter').find('option[value='+selectedPlayer.id+']').addClass('hide-player-in-filter');
		$(this).val('Default');

		runFilter();
	});	

	function runPlayerFilter(filter) {
		runShowPlayerFilter(filter);
		runHidePlayerFilter(filter);
	}

	function runShowPlayerFilter(filter) {
		var lineups = getLineups();

		for (var i = 0; i < lineups.length; i++) {
			checkLineupForPlayers(lineups[i], filter.players.show, 'show');
		}
	}

	function runHidePlayerFilter(filter) {
		var lineups = getLineups();

		for (var i = 0; i < lineups.length; i++) {
			checkLineupForPlayers(lineups[i], filter.players.hide, 'hide');
		}
	}


	//// GET LINEUPS ////

	function getLineups() {
		var lineups = [];

		$('table.lineup').each(function() {
			var lineup = $(this);

			lineups.push(lineup);
		});		

		return lineups;
	}


	//// CHECK LINEUP FOR PLAYERS ////

	function checkLineupForPlayers(lineup, players, playerFilterType) {
		for (var i = 0; i < players.length; i++) {
			checkForPlayerInLineup(players[i], lineup, playerFilterType);
		}
	}

	function checkForPlayerInLineup(player, lineup, playerFilterType) {
		if (player.id == 'None') {
			return;
		}

		var playerId = player.id;

		if (playerFilterType == 'show') {
			checkForPlayerInLineupToShow(lineup, playerId);
		}

		if (playerFilterType == 'hide') {
			checkForPlayerInLineupToHide(lineup, playerId);
		}
	}

	function checkForPlayerInLineupToShow(lineup, playerId) {
		var isPlayerInLineup = $(lineup).find('tr.roster-spot[data-player-id='+playerId+']').length;

		if (isPlayerInLineup == 0) {
			$(lineup).addClass('hide-lineup');
		}
	}

	function checkForPlayerInLineupToHide(lineup, playerId) {
		var isPlayerInLineup = $(lineup).find('tr.roster-spot[data-player-id='+playerId+']').length;

		if (isPlayerInLineup == 1) {
			$(lineup).addClass('hide-lineup');
		}
	}


	//// ADD PLAYER TO VIEW ////

	function addPlayerToView(playerFilterType, selectedPlayer) {
		$('span.selected-players-to-'+playerFilterType).append('<span data-player-id="'+selectedPlayer.id+'" class="selected-player selected-player-to-'+playerFilterType+'">'+selectedPlayer.name+'</span> <a class="remove-selected-player-link" href="#"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>');
	}


	//// REMOVE SELECTED PLAYER ////

	$('.selected-players').on('click', '.remove-selected-player-link', function(e) {
		e.preventDefault();

		var selectedPlayerId = $(this).prev('span.selected-player').data('player-id');

		$('select.player-filter').find('option[value='+selectedPlayerId+']').removeClass('hide-player-in-filter');

		$(this).prev('span.selected-player').remove();
		$(this).remove();

		runFilter();
	});


	//// CLEAR ALL SELECTED PLAYERS ////

	$('.clear-selected-players-link').click(function(e) {
		e.preventDefault();

		$('select.player-filter option').removeClass('hide-player-in-filter');

		$('span.selected-player').remove();
		$('.remove-selected-player-link').remove();

		runFilter();
	});


	/********************************************
	LINEUP TYPE FILTER
	********************************************/

	$('select.lineup-type-filter').on('change', function() {
		runFilter();
	});

	function runLineupTypeFilter(filter) {
		if (filter.lineupType == 'All') {
			return;
		}

		$('table.lineup').each(function() {
			var lineup = $(this);

			hideLineupsNotSelected(lineup, filter.lineupType);
		});				
	}

	function hideLineupsNotSelected(lineup, lineupType) {
		var doWeWantActiveLineups = false;

		if (lineupType == 'Only Active') {
			doWeWantActiveLineups = true;
		}

		var isLineupActive = $(lineup).hasClass('active-lineup');

		if (doWeWantActiveLineups == isLineupActive) {
			return;
		}

		$(lineup).addClass('hide-lineup');
	}


	/****************************************************************************************
	EDIT LINEUP BUY IN
	****************************************************************************************/

	$(".edit-lineup-buy-in-link").click(function(e) {
		e.preventDefault();

		$(this).parent().parent().parent().parent().parent().next().toggleClass("edit-lineup-buy-in-amount-hidden");
	});

	$(".edit-lineup-buy-in-button").click(function(e) {
		e.preventDefault();

		var lineupBuyIn = $(this).parent().prev().val();
		var lineupBuyInPercentage = lineupBuyIn / buyIn * 100;
		lineupBuyInPercentage = lineupBuyInPercentage.toFixed(2);

		var hash = $(this).parent().parent().prev().data('hash');

		var $this = $(this);

    	$.ajax({
            url: baseUrl+'/solver_top_plays/update_lineup_buy_in/'+playerPoolId+'/'+hash+'/'+lineupBuyIn,
            type: 'POST',
            success: function() {
            	$this.parent().parent().addClass("edit-lineup-buy-in-amount-hidden");

            	$this.parent().parent().prev().children('tbody').children('tr.update-lineup-row').children('td.update-lineup-td').children('span.edit-lineup-buy-in').children('span.lineup-buy-in-amount').text(lineupBuyIn);
            	$this.parent().parent().prev().children('tbody').children('tr.update-lineup-row').children('td.update-lineup-td').children('span.edit-lineup-buy-in').children('span.lineup-buy-in-percentage').text(lineupBuyInPercentage);

            	updateUnspentBuyIn();
            	drawBarChart();
            }
        }); 				
	});


	/****************************************************************************************
	ADD OR REMOVE LINEUP
	****************************************************************************************/

	$(".add-or-remove-lineup-link").click(function(e) {
		e.preventDefault();

		var buyIn = $("span.buy-in-amount").text();

		if (buyIn == 0) {
			alert("Please enter a buy in.");

			return false;
		}

		var lineupBuyIn = defaultLineupBuyIn;
		var lineupBuyInPercentage = lineupBuyIn / buyIn * 100;
		lineupBuyInPercentage = lineupBuyInPercentage.toFixed(2);

		var addOrRemove = $(this).children(".add-or-remove-lineup-anchor-text").text();

		$(this).children(".add-or-remove-lineup-anchor-text").text('');
		$(this).next(".add-or-remove-lineup-link-loading-gif").show();

		var hash = $(this).parent().parent().parent().parent().data('hash');
		var totalSalary = $(this).parent().parent().parent().parent().data('total-salary');
		
		var playerIdsOfLineup = [];

		var rosterSpots = $(this).parent().parent().parent('tbody').find('tr.roster-spot');

		$(rosterSpots).each(function() {
			var playerId = $(this).data('player-id');

			playerIdsOfLineup.push(playerId);
		});

		var $this = $(this);

    	$.ajax({
            url: baseUrl+'/solver_top_plays/add_or_remove_lineup/',
           	type: 'POST',
           	data: { 
           		playerPoolId: playerPoolId,
           		hash: hash,
           		totalSalary: totalSalary,
           		buyIn: lineupBuyIn,
           		addOrRemove: addOrRemove,
           		playerIdsOfLineup: playerIdsOfLineup
           	},
            success: function() {
				$this.parent().parent().parent().parent().toggleClass("active-lineup");	
				$this.parent().parent().parent().parent().removeClass("money-lineup");	
				$this.prev().toggleClass("edit-lineup-buy-in-hidden");	
				$this.next(".add-or-remove-lineup-link-loading-gif").hide();

				switch(addOrRemove) {
				    case "Add":
				    	$this.prev().children('.lineup-buy-in-amount').text(lineupBuyIn);
				    	$this.prev().children('.lineup-buy-in-percentage').text(lineupBuyInPercentage);
						$this.parent().parent().parent().parent().next().children(".edit-lineup-buy-in-input").val(lineupBuyIn);								
						
						$this.children(".add-or-remove-lineup-anchor-text").text("Remove");
						
				        break;
				    
				    case "Remove":
				    	$this.parent().parent().parent().parent().next().addClass("edit-lineup-buy-in-amount-hidden");

				        $this.children(".add-or-remove-lineup-anchor-text").text("Add");
				        $this.prev().find(".play-or-unplay-lineup-anchor-text").text("Play");

				        break;
				}

				updateUnspentBuyIn();
				drawBarChart();
            }
        }); 
	});


	/****************************************************************************************
	PLAY OR UNPLAY LINEUP
	****************************************************************************************/

	$(".play-or-unplay-lineup-link").click(function(e) {
		e.preventDefault();

		var playOrUnplay = $(this).children(".play-or-unplay-lineup-anchor-text").text();

		$(this).children(".play-or-unplay-lineup-anchor-text").text('');
		$(this).children(".play-or-unplay-lineup-anchor-text").html('<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />');

		var hash = $(this).parent().parent().parent().parent().parent().data('hash');

		var $this = $(this);

    	$.ajax({
            url: baseUrl+'/solver_top_plays/play_or_unplay_lineup/',
           	type: 'POST',
           	data: { 
           		playerPoolId: playerPoolId,
           		hash: hash,
           		playOrUnplay: playOrUnplay,
           	},
            success: function() {
				$this.parent().parent().parent().parent().parent().toggleClass("money-lineup");	
				$(this).children(".play-or-unplay-lineup-anchor-text").html('');

				switch(playOrUnplay) {
				    case "Play":
						$this.children(".play-or-unplay-lineup-anchor-text").text("Unplay");
						
				        break;
				    
				    case "Unplay":
				    	$this.children(".play-or-unplay-lineup-anchor-text").text("Play");

				        break;
				}								
            }
        });
	});


	/****************************************************************************************
	DRAW BAR CHART
	****************************************************************************************/

	drawBarChart();

	function arrayUnique(arr) {
	    var a = [];
	    for (var i=0, l=arr.length; i<l; i++)
	        if (a.indexOf(arr[i]) === -1 && arr[i] !== '')
	            a.push(arr[i]);
	    return a;
	}

	function drawBarChart() {
		var activeLineups = {};
		activeLineups.rosterSpots = [];
		activeLineups.names = [];

		$(".active-lineup").each(function() {
			var lineupBuyIn = $(this).find("span.lineup-buy-in-amount").text();

			var $this = $(this);

			getActiveLineupsInfo($this, activeLineups, lineupBuyIn);
		});

		activeLineups.names = arrayUnique(activeLineups.names);

		var players = [];

		for (var i = 0; i < activeLineups.names.length; i++) {
			players[i] = {};

			players[i]['name'] = activeLineups.names[i];
			var totalBuyInOfPlayer = 0;

			for (var n = 0; n < activeLineups.rosterSpots.length; n++) {
				if (players[i]['name'] == activeLineups.rosterSpots[n]['name']) {
					totalBuyInOfPlayer += parseInt(activeLineups.rosterSpots[n]['lineupBuyIn']);
				} 
			};

			var percentage = totalBuyInOfPlayer / buyIn * 100;
			percentage = parseInt(percentage);

			players[i]['percentage'] = percentage;

			players[i]['targetPercentage'] = $('td.roster-spot-name:contains("'+players[i]['name']+'")').first().parent('tr.roster-spot').data('target-percentage');
		};

		for (var i = 0; i < topPlays.length; i++) {
			var isTopPlayInActiveLineup = checkForTopPlayInActiveLineup(topPlays[i], players);

			addTopPlayIfMissing(isTopPlayInActiveLineup, topPlays[i], players);
		};

		function addTopPlayIfMissing(isTopPlayInActiveLineup, topPlay, players) {
			if (!isTopPlayInActiveLineup) {
				var player = {
					name: topPlay.name,
					percentage: 0,
					targetPercentage: topPlay.target_percentage
				}

				players.push(player);
			}

			return;
		}


		players.sort(function(a,b) {
		    return b.targetPercentage - a.targetPercentage || 
		    	   (b.targetPercentage == a.targetPercentage && b.percentage - a.percentage);
		});

		var playerNames = [];
		var percentages = [];
		var targetPercentages = [];

		for (var i = 0; i < players.length; i++) {
			playerNames.push(players[i]['name']);
			percentages.push(players[i]['percentage']);
			targetPercentages.push(players[i]['targetPercentage']);
		};

	    $('#player-percentages-container').highcharts({
	        chart: {
	            type: 'bar'
	        },
	        title: {
	        	text: null
	        },
	        xAxis: {
	            categories: playerNames
	        },
	        yAxis: {
	            min: 0,
	            title: {
	                text: 'Percentage'
	            },
	            max: 100
	        },
	        tooltip: {
	            enabled: false
	        },
	        plotOptions: {
	            bar: {
	                dataLabels: {
	                    enabled: true
	                }
	            },
	            series: {
	            	states: {
	            		hover: {
	            			enabled: false
	            		}
	            	}
	            }
	        },
	        credits: {
	            enabled: false
	        },
	        series: [{
	            data: percentages
	        }, {
	        	data: targetPercentages
	        }],
	        legend: {
	        	enabled: false
	        }
	    });	
	}				

	function getActiveLineupsInfo($this, activeLineups, lineupBuyIn) {
		$this.children("tbody").children("tr.roster-spot").find("td.roster-spot-name").each(function() {
			var name = $(this).text();

			var rosterSpot = { 
				name: name, 
				lineupBuyIn: lineupBuyIn 
			};

			activeLineups.rosterSpots.push(rosterSpot);

			activeLineups.names.push(name);
		});			
	}

	function checkForTopPlayInActiveLineup(topPlay, players) {
		for (var n = 0; n < players.length; n++) {
			if (players[n]['name'] == topPlay['name']) {
				return true;
			}
		}

		return false;
	}

});