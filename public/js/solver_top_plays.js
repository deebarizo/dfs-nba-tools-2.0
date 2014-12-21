$(document).ready(function() {

	/****************************************************************************************
	TOGGLE NAVBAR
	****************************************************************************************/

	$(".toggle-navbar-link").click(function(e) {
		e.preventDefault();

		$('.navbar').toggle();
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

	var lineupType;
	var filter = {};

	function runFilter() {
		filter = getFilter();

		$('table.lineup').removeClass('hide-lineup');

		runLineupTypeFilter(filter);
	}

	function getFilter() {
		lineupType = $('select.lineup-type-filter').val();

		filter = {
			lineupType: lineupType
		};

		return filter;
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

		console.log('test');

		$(this).parent().parent().parent().parent().parent().next().toggleClass("edit-lineup-buy-in-amount-hidden");
	});

	$(".edit-lineup-buy-in-button").click(function(e) {
		e.preventDefault();

		var lineupBuyIn = $(this).parent().prev().val();
		var lineupBuyInPercentage = lineupBuyIn / buyIn * 100;
		lineupBuyInPercentage = lineupBuyInPercentage.toFixed(2);

		var hash = $(this).parent().parent().prev().data('hash');

		// console.log(test); return false;

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

	if (areThereActiveLineups == 1) {
		drawBarChart();
	}

	if (areThereActiveLineups == 0) {
		$('#player-percentages-container').text("There are no active lineups.");
	}

	function drawBarChart() {
		var numActiveLineups = $(".active-lineup").length; 

		if (numActiveLineups == 0) {
			$('#player-percentages-container').text("There are no active lineups.");

			return true;
		}

		var rosterSpotsInActiveLineups = [];
		var playersInActiveLineups = [];

		$(".active-lineup").each(function() {
			var lineupBuyIn = $(this).find("span.lineup-buy-in-amount").text();

			$(this).children("tbody").children("tr.roster-spot").find("td.roster-spot-name").each(function() {
				var name = $(this).text();

				var rosterSpot = { 
					name: name, 
					lineupBuyIn: lineupBuyIn 
				};

				rosterSpotsInActiveLineups.push(rosterSpot);

				playersInActiveLineups.push(name);
			});
		});

		function arrayUnique(arr) {
		    var a = [];
		    for (var i=0, l=arr.length; i<l; i++)
		        if (a.indexOf(arr[i]) === -1 && arr[i] !== '')
		            a.push(arr[i]);
		    return a;
		}

		playersInActiveLineups = arrayUnique(playersInActiveLineups);

		var players = [];

		for (var i = 0; i < playersInActiveLineups.length; i++) {
			players[i] = {};

			players[i]['name'] = playersInActiveLineups[i];
			var totalBuyInOfPlayer = 0;

			for (var n = 0; n < rosterSpotsInActiveLineups.length; n++) {
				if (players[i]['name'] == rosterSpotsInActiveLineups[n]['name']) {
					totalBuyInOfPlayer += parseInt(rosterSpotsInActiveLineups[n]['lineupBuyIn']);
				} 
			};

			var percentage = totalBuyInOfPlayer / buyIn * 100;
			percentage = parseInt(percentage);

			players[i]['percentage'] = percentage;
		};

		players.sort(function(a,b) {
		    return b.percentage - a.percentage;
		});

		var playerNames = [];
		var percentages = [];

		for (var i = 0; i < players.length; i++) {
			playerNames.push(players[i]['name']);
			percentages.push(players[i]['percentage']);
		};

	    $('#player-percentages-container').highcharts({
	        chart: {
	            type: 'bar'
	        },
	        title: {
	        	text: null
	        },
	        xAxis: {
	            categories: playerNames,
	            labels: {
	            	step: 1
	            }
	        },
	        yAxis: {
	            min: 0,
	            title: {
	                text: 'Percentage'
	            },
	            max: 100
	        },
	        tooltip: {
	            valueSuffix: '%'
	        },
	        plotOptions: {
	            bar: {
	                dataLabels: {
	                    enabled: true
	                },
	                pointWidth: 20,
	                pointPadding: 0
	            }
	        },
	        credits: {
	            enabled: false
	        },
	        series: [{
	        	name: 'Percentage',
	            data: percentages
	        }],
	        legend: {
	        	enabled: false
	        }
	    });				
	}				

});