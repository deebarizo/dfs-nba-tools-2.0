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

		// console.log(players);

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
		// console.log(filter);

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

		// console.log(lineups);

		return lineups;
	}


	//// CHECK LINEUP FOR PLAYERS ////

	function checkLineupForPlayers(lineup, players, playerFilterType) {
		console.log(players);

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
		var isPlayerInLineup = $(lineup).find('tr.roster-spot[data-id='+playerId+']').length;

		if (isPlayerInLineup == 0) {
			$(lineup).addClass('hide-lineup');
		}
	}

	function checkForPlayerInLineupToHide(lineup, playerId) {
		var isPlayerInLineup = $(lineup).find('tr.roster-spot[data-id='+playerId+']').length;

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


	//// HIDE ALL SPENT PLAYERS ////

	$('.hide-spent-players-link').click(function(e) {
		e.preventDefault();

		$('select.player-filter option').removeClass('hide-player-in-filter');

		$('span.selected-player-to-hide').next('.remove-selected-player-link').remove();
		$('span.selected-player-to-hide').remove();
		
		var players = getPlayerPercentages(barChartFilter);

		var spentPlayers = players.filter(isSpentPlayer);

		addSpentPlayersToView(spentPlayers);

		runFilter();
	});

	function isSpentPlayer(player) {
		return player['unspentTargetPercentage'] <= 4;
	}

	function addSpentPlayersToView(spentPlayers) {
		for (var i = 0; i < spentPlayers.length; i++) {
			$('span.selected-players-to-hide').append('<span data-player-id="'+spentPlayers[i].id+'" class="selected-player selected-player-to-hide">'+spentPlayers[i].name+'</span> <a class="remove-selected-player-link" href="#"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>');

			$('select.player-filter').find('option[value='+spentPlayers[i].id+']').addClass('hide-player-in-filter');
		};
	}


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

		$(this).closest('table.lineup').next('.edit-lineup-buy-in-amount').toggleClass("edit-lineup-buy-in-amount-hidden");
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
            	drawBarChart(barChartFilter);
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

		var lineup = $(this).closest('table.lineup');

		var hash = lineup.data('hash');
		var totalSalary = lineup.data('salary');
		
		var players = [];

		var rosterSpots = lineup.find('tr.roster-spot');

		$(rosterSpots).each(function() {
			var id = $(this).data('id');
			var position = $(this).data('position');

			var player = {
				id: id,
				position: position
			};

			players.push(player);
		});

		var $this = $(this);

		// console.log(players); return;

    	$.ajax({
            url: baseUrl+'/solver_top_plays/dk/mlb/add_or_remove_lineup/',
           	type: 'POST',
           	data: { 
           		playerPoolId: playerPoolId,
           		hash: hash,
           		totalSalary: totalSalary,
           		buyIn: lineupBuyIn,
           		addOrRemove: addOrRemove,
           		players: players
           	},
            success: function() {
				lineup.toggleClass("active-lineup");	
				lineup.removeClass("money-lineup");	
				lineup.find('.edit-lineup-buy-in').toggleClass("edit-lineup-buy-in-hidden");	
				lineup.find(".add-or-remove-lineup-link-loading-gif").hide();

				switch(addOrRemove) {
				    case "Add":
				    	lineup.find('.lineup-buy-in-amount').text(lineupBuyIn);
				    	lineup.find('.lineup-buy-in-percentage').text(lineupBuyInPercentage);
						lineup.next('.edit-lineup-buy-in-amount').find('.edit-lineup-buy-in-input').val(lineupBuyIn);
						
						lineup.find(".add-or-remove-lineup-anchor-text").text("Remove");
						
				        break;
				    
				    case "Remove":
				    	lineup.find('edit-lineup-buy-in-amount').addClass("edit-lineup-buy-in-amount-hidden");

				        lineup.find(".add-or-remove-lineup-anchor-text").text("Add");
				        lineup.find(".play-or-unplay-lineup-anchor-text").text("Play");

				        break;
				}

				updateUnspentBuyIn();
				drawBarChart(barChartFilter);
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

	var barChartSorter = $('select.player-percentages-filter').val();
	var barChartFilter = {
		types: $('select.player-types-show-filter').val(),
		percentages: $('select.player-percentages-show-filter').val()
	};

	drawBarChart(barChartFilter);

	$('select.player-percentages-filter').on('change', function() {
		barChartSorter = $('select.player-percentages-filter').val();

		drawBarChart(barChartFilter);
	});

	$('select.player-percentages-show-filter').on('change', function() {
		barChartFilter['percentages'] = $('select.player-percentages-show-filter').val();

		drawBarChart(barChartFilter);
	});

	$('select.player-types-show-filter').on('change', function() {
		barChartFilter['types'] = $('select.player-types-show-filter').val();

		drawBarChart(barChartFilter);
	});

	function drawBarChart(barChartFilter) {
		var players = getPlayerPercentages(barChartFilter);

		sortBarChart(barChartSorter, players);

		var playerContents = [];
		var percentages = [];
		var targetPercentages = [];

		for (var i = 0; i < players.length; i++) {
			if (players[i]['contents'] !== null) {
				playerContents.push(players[i]['contents']);
				percentages.push(parseFloat(players[i]['percentage']));
				targetPercentages.push(players[i]['targetPercentage']);			
			}
		};

		if (barChartFilter['percentages'] == 'All') {
			var series = [		
				{ data: percentages },
				{ data: targetPercentages }
			];			
		}

		if (barChartFilter['percentages'] == 'Only Actual Percentage') {
			var series = [		
				{ data: percentages }
			];			
		}

		// console.log(playerContents);
		console.log(series);

		var barChartContainerHeight = (playerContents.length * 45) + 50;
		$('#player-percentages-container').css('height', barChartContainerHeight+'px');

	    $('#player-percentages-container').highcharts({
	        chart: {
	            type: 'bar'
	        },
	        title: {
	        	text: null
	        },
	        xAxis: {
	            categories: playerContents
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
	        series: series,
	        legend: {
	        	enabled: false
	        }
	    });	
	}		

	function arrayUnique(arr) {
	    var a = [];
	    for (var i=0, l=arr.length; i<l; i++)
	        if (a.indexOf(arr[i]) === -1 && arr[i] !== '')
	            a.push(arr[i]);
	    return a;
	}		

	function getActiveLineupsInfo(activeLineup, activeLineups, lineupBuyIn) {
		activeLineup.find('tr.roster-spot').each(function() {
			var id = $(this).data('id');
			var name = $(this).data('name');
			var position = $(this).data('position');
			var salary = $(this).data('salary');
			var teamAbbrBr = $(this).data('team-abbr');
			var targetPercentage = $(this).data('target-percentage');

			var rosterSpot = { 
				id: id,
				name: name, 
				position: position,
				salary: salary,
				teamAbbrBr: teamAbbrBr,
				targetPercentage: targetPercentage,
				lineupBuyIn: lineupBuyIn,
			};

			// console.log(rosterSpot);

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

	function addTopPlayIfMissing(isTopPlayInActiveLineup, topPlay, players) {
		if (!isTopPlayInActiveLineup) {
			var player = {
				id: topPlay.id,
				name: topPlay.name,
				percentage: 0,
				targetPercentage: topPlay.target_percentage
			}

			player['unspentTargetPercentage'] = topPlay.target_percentage - 0;

			player['position'] = $('td.roster-spot-name:contains("'+player['name']+'")').first().siblings('td.position').text();

			player['teamAbbrBr'] = $('td.roster-spot-name:contains("'+player['name']+'")').first().parent('tr.roster-spot').data('team-abbr-br');

			player['salary'] = $('td.roster-spot-name:contains("'+player['name']+'")').first().next('td').text();

			players.push(player);
		}

		return;
	}

	function sortBarChart(barChartSorter, players) {
		if (barChartSorter === 'Unspent Target Percentage (Desc)') {
			players.sort(function(a,b) {
			    return b.unspentTargetPercentage - a.unspentTargetPercentage || 
			    	   (b.unspentTargetPercentage == a.unspentTargetPercentage && b.percentage - a.percentage);
			});
		}

		if (barChartSorter === 'Unspent Target Percentage (Asc)') {
			players.sort(function(a,b) {
			    return a.unspentTargetPercentage - b.unspentTargetPercentage || 
			    	   (a.unspentTargetPercentage == b.unspentTargetPercentage && b.percentage - a.percentage);
			});
		}

		if (barChartSorter === 'Target Percentage') {
			players.sort(function(a,b) {
			    return b.targetPercentage - a.targetPercentage || 
			    	   (b.targetPercentage == a.targetPercentage && b.percentage - a.percentage);
			});
		}

		if (barChartSorter === 'Actual Percentage') {
			players.sort(function(a,b) {
			    return b.percentage - a.percentage;
			});
		}

		if (barChartSorter === 'First Name') {
			players.sort(function(a,b) {
			    return a.name.localeCompare(b.name);
			});
		}

		if (barChartSorter === 'Position') {
			players.sort(function(a,b) {
			    return a.position.localeCompare(b.position);
			});
		}

		if (barChartSorter === 'Team') {
			players.sort(function(a,b) {
			    return a.teamAbbrBr.localeCompare(b.teamAbbrBr);
			});
		}

		if (barChartSorter === 'Salary') {
			players.sort(function(a,b) {
			    return b.salary - a.salary || 
			    	   (b.salary == a.salary && b.percentage - a.percentage);
			});
		}
	}


	/****************************************************************************************
	PLAYER PERCENTAGES
	****************************************************************************************/

	function getPlayerPercentages(barChartFilter) {
		var activeLineups = {};
		activeLineups.rosterSpots = [];
		activeLineups.names = [];

		$(".active-lineup").each(function() {
			var activeLineup = $(this);

			var lineupBuyIn = activeLineup.find('span.lineup-buy-in-amount').text();

			getActiveLineupsInfo(activeLineup, activeLineups, lineupBuyIn);
		});

		// console.log(activeLineups);

		activeLineups.names = arrayUnique(activeLineups.names);

		var players = [];

		for (var i = 0; i < activeLineups.names.length; i++) {
			players[i] = {};

			for (var n = 0; n < activeLineups.rosterSpots.length; n++) {
				if (activeLineups.names[i] == activeLineups.rosterSpots[n]['name']) {
					players[i]['name'] = activeLineups.rosterSpots[n]['name'];
					players[i]['id'] = activeLineups.rosterSpots[n]['id'];
					players[i]['position'] = activeLineups.rosterSpots[n]['position'];
					players[i]['salary'] = activeLineups.rosterSpots[n]['salary'];
					players[i]['teamAbbrBr'] = activeLineups.rosterSpots[n]['teamAbbrBr'];
					players[i]['targetPercentage'] = activeLineups.rosterSpots[n]['targetPercentage'];

					break;
				} 
			};

			var totalBuyInOfPlayer = 0;

			for (var n = 0; n < activeLineups.rosterSpots.length; n++) {
				if (players[i]['name'] == activeLineups.rosterSpots[n]['name']) {
					totalBuyInOfPlayer += parseInt(activeLineups.rosterSpots[n]['lineupBuyIn']);
				} 
			};

			var percentage = totalBuyInOfPlayer / buyIn * 100;
			percentage = parseFloat(Math.round(percentage * 100) / 100).toFixed(2);

			players[i]['percentage'] = percentage;

			players[i]['unspentTargetPercentage'] = players[i]['targetPercentage'] - players[i]['percentage'];
		};

		// console.log(players);

		for (var i = 0; i < players.length; i++) {
			if (barChartFilter['types'] == 'Only Pitchers') {
				if (players[i]['position'] == 'SP' || players[i]['position'] == 'RP') {
					players[i]['contents'] = createPlayerContent(players[i]['name'], players[i]['position'], players[i]['teamAbbrBr'], players[i]['salary']);
				} else {
					players[i]['contents'] = null;
				}
				continue;
			}

			if (barChartFilter['types'] == 'Only Hitters') {
				if (players[i]['position'] != 'SP' && players[i]['position'] != 'RP') {
					players[i]['contents'] = createPlayerContent(players[i]['name'], players[i]['position'], players[i]['teamAbbrBr'], players[i]['salary']);
				} else {
					players[i]['contents'] = null;
				}
				continue;
			}

			players[i]['contents'] = createPlayerContent(players[i]['name'], players[i]['position'], players[i]['teamAbbrBr'], players[i]['salary']);
		};

		// console.log(players);

		return players;
	}

	function createPlayerContent(name, position, team, salary) {
		return name+'<br>('+position+') ('+team+') ('+salary+')';
	}

});