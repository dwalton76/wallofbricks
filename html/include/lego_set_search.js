
function updateSets() {
	// console.log('updateSets called');
	var theme = $('input#theme').val();
	var min_year = $("#year_slider_range").slider("values", 0);
	var max_year = $("#year_slider_range").slider("values", 1);
	var min_age = $("#age_slider_range").slider("values", 0);
	var max_age = $("#age_slider_range").slider("values", 1);
	var min_price = $("#price_slider_range").slider("values", 0);
	var max_price = $("#price_slider_range").slider("values", 1);
	var min_pieces = $("#pieces_slider_range").slider("values", 0);
	var max_pieces = $("#pieces_slider_range").slider("values", 1);
	var username = $("#username").html();
	var page = $("#page").html();
	var last_page = $("#last_page").html();

	var dataString = 'theme=' + theme + '&min_year=' + min_year + '&max_year=' + max_year + '&min_age=' + min_age + '&max_age=' + max_age + '&min_price=' + min_price + '&max_price=' + max_price + '&min_pieces=' + min_pieces + '&max_pieces=' + max_pieces + '&page=' + page + '&last_page=' + last_page + '&username=' + username;

	$.ajax({
	  type: "POST",
	  url: "/ajax-get-sets.php",
	  data: dataString,
	  cache: false,
	  success: function(response) {
		  $('div#set-choices').html(response);
		  postAjaxCode();

		  var show_prev = $("#show-prev-button").html();
		  var show_next = $("#show-next-button").html();
		  if (last_page <= 0) {
			  var new_last_page = $('#new_last_page').html();
			  $("#last_page").html(new_last_page);
		  }

		  if (show_prev == 1) {
			  $('a#prev-set-by-search').show();
		  } else {
			  $('a#prev-set-by-search').hide();
		  }

		  if (show_next == 1) {
			  $('a#next-set-by-search').show();
		  } else {
			  $('a#next-set-by-search').hide();
		  }
	  }
	});
}

$(document).ready(function() {
	$('#prev-set-by-search').click(function() {
		var page_number = $("#page").html();
		$("#page").html(parseInt(page_number) - 1);
		updateSets();
	});

	$('#next-set-by-search').click(function() {
		var page_number = $("#page").html();
		$("#page").html(parseInt(page_number) + 1);
		updateSets();
	});

	// jquery UI sliders to pick the min/max year
	var min_year = $("span#min_year").html();
	var max_year = $("span#max_year").html();
	$( "#year_slider_range" ).slider({
		range: true,
		min: 1970,
		max: 2015,
		values: [ min_year, max_year],
		change: function() {
			$("#page").html(1);
			$("#last_page").html(0);
			updateSets();
		},
		slide: function( event, ui ) {
		  $("#year_range").val( ui.values[ 0 ] + " - " + ui.values[ 1 ] );
		}
	});
	$("#year_range").val( $( "#year_slider_range" ).slider( "values", 0 ) + " - " + $( "#year_slider_range" ).slider( "values", 1 ) );

	// jquery UI sliders to pick the min/max age
	var min_age = $("span#min_age").html();
	var max_age = $("span#max_age").html();
	$( "#age_slider_range" ).slider({
		range: true,
		min: 1,
		max: 16,
		values: [ min_age, max_age],
		change: function() {
			$("#page").html(1);
			$("#last_page").html(0);
			updateSets();
		},
		slide: function( event, ui ) {
		  $("#age_range").val( ui.values[ 0 ] + " - " + ui.values[ 1 ] );
		}
	});
	$("#age_range").val( $( "#age_slider_range" ).slider( "values", 0 ) + " - " + $( "#age_slider_range" ).slider( "values", 1 ) );

	// jquery UI sliders to pick the min/max price
	var min_price = $("span#min_price").html();
	var max_price = $("span#max_price").html();
	$( "#price_slider_range" ).slider({
		range: true,
		min: 0,
		max: 500,
		step: 5,
		values: [ min_price, max_price],
		change: function() {
			$("#page").html(1);
			$("#last_page").html(0);
			updateSets();
		},
		slide: function( event, ui ) {
			$("#price_range").val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
		}
	});
	$("#price_range").val( "$" + $( "#price_slider_range" ).slider( "values", 0 ) + " - $" + $( "#price_slider_range" ).slider( "values", 1 ) );

	var min_pieces = $("span#min_pieces").html();
	var max_pieces = $("span#max_pieces").html();
	$( "#pieces_slider_range" ).slider({
		range: true,
		min: 1,
		max: 6000,
		step: 50,
		values: [min_pieces, max_pieces],
		change: function() {
			$("#page").html(1);
			$("#last_page").html(0);
			updateSets();
		},
		slide: function( event, ui ) {
			$("#pieces_range").val(ui.values[ 0 ] + " - " + ui.values[ 1 ] );
		}
	});
	$("#pieces_range").val($("#pieces_slider_range" ).slider( "values", 0 ) + " - " + $( "#pieces_slider_range" ).slider( "values", 1 ) );

	$('input#set-name-or-id').autocomplete({
		source: "/ajax-get-set-options-by-id-or-name.php",
		select: function( event, ui ) {
			// Extract only the set ID# and put that in the text box then auto-submit the form
			var set_id_name = ui.item.value;
			var set_tuple = set_id_name.split(':');
			var set_id = set_tuple[0];
			$("input[name='set_id']").val(set_id);
			$('#set-id-form').submit();
		},
		change: function( event, ui ) {
			// Extract only the set ID# and put that in the text box then auto-submit the form
			var set_id_name = ui.item.value;
			var set_tuple = set_id_name.split(':');
			var set_id = set_tuple[0];
			$("input[name='set_id']").val(set_id);
			$('#set-id-form').submit();
		}
	});

	updateSets();

   // autocomple array for themes/subthemes
   var themeList = [
"4 Juniors",
"4 Juniors: Accessories",
"4 Juniors: City",
"4 Juniors: Pirates",
"4 Juniors: Spider-Man",
"Action Wheelers",
"Advanced Models",
"Advanced Models: Aircraft",
"Advanced Models: Buildings",
"Advanced Models: Maersk",
"Advanced Models: Miscellaneous",
"Advanced Models: Modular Buildings",
"Advanced Models: Sculptures",
"Advanced Models: Space",
"Advanced Models: Vehicles",
"Adventurers",
"Adventurers: Desert",
"Adventurers: Dino Island",
"Adventurers: Jungle",
"Adventurers: Orient Expedition",
"Agents",
"Alpha Team",
"Alpha Team: Mission Deep Freeze",
"Alpha Team: Mission Deep Sea",
"Alpha Team: Product Collection",
"Aqua Raiders",
"Aquazone",
"Aquazone: Accessories",
"Aquazone: Aquanauts",
"Aquazone: Aquaraiders",
"Aquazone: Aquasharks",
"Aquazone: Hydronauts",
"Aquazone: Stingrays",
"Architecture",
"Assorted",
"Assorted: Bonus/Value Pack",
"Atlantis",
"Atlantis: Product Collection",
"Avatar The Last Airbender",
"Baby",
"Baby: Disney's Baby Mickey",
"Basic",
"Basic: Large Vehicles",
"Basic: Model",
"Basic: Mosaic",
"Basic: Seasonal",
"Basic: Supplementaries",
"Basic: Universal Building Set",
"Batman",
"Belville",
"Belville: Fairy-Tale",
"Belville: Fairytales",
"Belville: Seasonal",
"Ben 10",
"Ben 10: Alien Force",
"Bionicle",
"Bionicle: Accessories",
"Bionicle: Agori",
"Bionicle: Barraki",
"Bionicle: Battle Vehicles",
"Bionicle: Bohrok",
"Bionicle: Bohrok Va",
"Bionicle: Bohrok-Kal",
"Bionicle: Glatorian",
"Bionicle: Glatorian Legends",
"Bionicle: Matoran",
"Bionicle: Mistika",
"Bionicle: Phantoka",
"Bionicle: Piraka",
"Bionicle: Playsets",
"Bionicle: Product Collection",
"Bionicle: Rahaga",
"Bionicle: Rahi",
"Bionicle: Rahkshi",
"Bionicle: Stars",
"Bionicle: Toa Hagah",
"Bionicle: Toa Hordika",
"Bionicle: Toa Inika",
"Bionicle: Toa Mahri",
"Bionicle: Toa Mata",
"Bionicle: Toa Metru",
"Bionicle: Toa Nuva",
"Bionicle: Vahki",
"Bionicle: Vehicles/Creatures",
"Bionicle: Visorak",
"Bionicle: Warriors",
"Boats",
"Bricks and More",
"Bricks and More: Accessories",
"Bricks and More: Bonus/Value Pack",
"Bricks and More: Product Collection",
"Building Set with People",
"Bulk Bricks",
"Bulk Bricks: Castle",
"Bulk Bricks: Seasonal",
"Bulk Bricks: Space",
"Bulk Bricks: Technic",
"Bulk Bricks: Trains",
"Cars",
"Cars: Product Collection",
"Castle",
"Castle: Accessories",
"Castle: Battle Pack",
"Castle: Battle Pack - Dragon Knights",
"Castle: Black Falcons",
"Castle: Black Knights",
"Castle: Bonus/Value Pack",
"Castle: Classic",
"Castle: Crusaders",
"Castle: Dark Forest",
"Castle: Dragon Knights",
"Castle: Fantasy Era",
"Castle: Forestmen",
"Castle: Fright Knights",
"Castle: Kingdoms",
"Castle: Knights' Kingdom I",
"Castle: Knights' Kingdom II",
"Castle: Lion Knights",
"Castle: Miscellaneous",
"Castle: My Own Creation",
"Castle: Ninja",
"Castle: Product Collection",
"Castle: Royal Knights",
"Castle: Seasonal",
"Castle: Wolfpack",
"City",
"City: Accessories",
"City: Airport",
"City: Cargo",
"City: Coast Guard",
"City: Construction",
"City: Farm",
"City: Fire",
"City: Forest Fire",
"City: Forest Police",
"City: Harbour",
"City: Houses",
"City: Medical",
"City: Mining",
"City: Police",
"City: Product Collection",
"City: Promotional",
"City: Seasonal",
"City: Shops and Services",
"City: Space",
"City: Special",
"City: Traffic",
"City: Trains",
"City: Virtual Product Collection",
"Classic",
"Classic: Accessories",
"Classic: Building",
"Classic: Large Vehicles",
"Clikits",
"Clikits: Product Collection",
"Clikits: Seasonal",
"Collectable Minifigures",
"Collectable Minifigures: Combi-pack",
"Collectable Minifigures: Series 1",
"Collectable Minifigures: Series 2",
"Collectable Minifigures: Series 3",
"Collectable Minifigures: Series 4",
"Collectable Minifigures: Series 5",
"Collectable Minifigures: Series 6",
"Collectable Minifigures: Series 7",
"Collectable Minifigures: Series 8",
"Collectable Minifigures: Series 9",
"Collectable Minifigures: Team GB",
"Creator",
"Creator: Designer Set",
"Creator: Expert",
"Creator: Inventor Set",
"Creator: Mosaic",
"Creator: Product Collection",
"Creator: Seasonal",
"Creator: X-Pod",
"Cuusoo",
"Dacta",
"Dacta: Adventurers",
"Dacta: Castle",
"Dacta: Duplo",
"Dacta: Storage",
"Dacta: Supplementary Set",
"Dacta: System",
"Dacta: Technic",
"Dacta: Town",
"Dino",
"Dino 2010",
"Dino Attack",
"Dinosaurs",
"Discovery",
"Duplo",
"Duplo: Accessories",
"Duplo: Airport",
"Duplo: Baby",
"Duplo: Bob the Builder",
"Duplo: Bonus/Value Pack",
"Duplo: Cars",
"Duplo: Castle",
"Duplo: Circus",
"Duplo: Construction",
"Duplo: Dino",
"Duplo: Disney",
"Duplo: Disney Princess",
"Duplo: Dolls",
"Duplo: Farm",
"Duplo: Ferrari",
"Duplo: Fire",
"Duplo: Holiday",
"Duplo: Jake and the Never Land Pirates",
"Duplo: LEGO PreSchool",
"Duplo: LEGO Ville",
"Duplo: Little Forest Friends",
"Duplo: Pirates",
"Duplo: Play Trains",
"Duplo: Primo",
"Duplo: Princess Castle",
"Duplo: Product Collection",
"Duplo: Thomas the Tank Engine",
"Duplo: Toolo",
"Duplo: Toy Story",
"Duplo: Trains",
"Duplo: Winnie the Pooh",
"Duplo: Zoo",
"Duplo: Zooters",
"Education",
"Education: Duplo",
"Education: Explore",
"Education: Mindstorms",
"Education: Storage",
"Education: Studios",
"Education: Supplementary Set",
"Education: System",
"Education: Technic",
"Education: Town",
"Exo-Force",
"Exo-Force: Deep Jungle",
"Exo-Force: Golden City",
"Exo-Force: Original",
"Explore",
"Explore: Being Me",
"Explore: Bob the Builder",
"Explore: Dora the Explorer",
"Explore: Imagination",
"Explore: Little Robots",
"Explore: Logic",
"Explore: Together",
"Fabuland",
"Factory",
"Factory: Custom Cars",
"Factory: Modular Buildings",
"Factory: Space",
"Factory: Trains",
"Freestyle",
"Friends",
"Friends: Collectables",
"Friends: Product Collection",
"Friends: Promotional",
"Galidor",
"Gear",
"Gear: Books/Ideas book",
"Gear: Books/Master Builders",
"Gear: Dacta",
"Gear: Dacta software",
"Gear: Key Chains/Miscellaneous",
"Gear: Pens",
"Gear: Seasonal",
"Gear: Storage",
"Harry Potter",
"Harry Potter: Chamber of Secrets",
"Harry Potter: Deathly Hallows",
"Harry Potter: General",
"Harry Potter: Goblet of Fire",
"Harry Potter: Half-Blood Prince",
"Harry Potter: Mini Building Set",
"Harry Potter: Order of the Phoenix",
"Harry Potter: Philospher's Stone",
"Harry Potter: Prisoner of Azkaban",
"Harry Potter: Product collection",
"Hero Factory",
"HERO Factory",
"HERO Factory: Product Collection",
"Hobby Set",
"Homemaker",
"Indiana Jones",
"Indiana Jones: Kingdom of the Crystal Skull",
"Indiana Jones: Last Crusade",
"Indiana Jones: Raiders of the Lost Ark",
"Indiana Jones: Temple of Doom",
"Island Xtreme Stunts",
"Jack Stone",
"Legends Of Chima",
"Legends Of Chima: Constraction",
"Legends Of Chima: Product Collection",
"Legends Of Chima: Speedorz",
"LEGOLAND",
"LEGOLAND: Boats",
"LEGOLAND: Building",
"LEGOLAND: Construction",
"LEGOLAND: Large Vehicle",
"LEGOLAND: Promotional",
"LEGOLAND: Town",
"LEGOLAND: Vehicle",
"Lone Ranger",
"Lord of the Rings",
"Lord of the Rings: The Fellowship of the Ring",
"Lord of the Rings: The Return of the King",
"Lord of the Rings: The Two Towers",
"Lord of the Rings: Virtual Product Collection",
"Make and Create",
"Make and Create: Product Collection",
"Master Builder Academy",
"Master Builder Academy: Virtual Product Collection",
"Mickey Mouse",
"Mindstorms",
"Mindstorms: 1.0",
"Mindstorms: 1.5",
"Mindstorms: 2.0",
"Mindstorms: NXT",
"Mindstorms: NXT 2.0",
"Mindstorms: Star Wars",
"Minitalia",
"Miscellaneous",
"Miscellaneous: Employee gift",
"Miscellaneous: FIRST LEGO League",
"Miscellaneous: LEGO Inside Tour Exclusive",
"Miscellaneous: LEGO internal",
"Miscellaneous: LEGO Universe",
"Miscellaneous: LEGOLAND model",
"Miscellaneous: Minifigures",
"Miscellaneous: Promotional",
"Miscellaneous: Target Gift Card",
"Model Team",
"Monster Fighters",
"Monster Fighters: Promotional",
"Monster Fighters: Virtual Product Collection",
"Ninjago",
"Ninjago: Booster pack",
"Ninjago: Product Collection",
"Ninjago: Promotional",
"Ninjago: Spinners",
"Pharaoh's Quest",
"Pirates",
"Pirates: Accessories",
"Pirates: Bonus/Value Pack",
"Pirates: Imperial Armada",
"Pirates: Imperial Guards",
"Pirates: Islanders",
"Pirates: Seasonal",
"Pirates of the Caribbean",
"Power Functions",
"Power Functions: Accessories",
"Power Functions: Technic",
"Power Functions: Trains",
"Power Miners",
"Power Miners: Product Collection",
"Power Miners: Promotional",
"PreSchool",
"Primo",
"Prince of Persia",
"Prince of Persia: The Sands of Time",
"Promotional",
"Promotional: Bricks",
"Promotional: Cube Dudes",
"Promotional: Event / Display",
"Promotional: Ferries",
"Promotional: LEGO Brand Store model",
"Promotional: LEGO Store Event",
"Promotional: LEGO Store Grand Opening Set",
"Promotional: LEGOLAND Parks",
"Promotional: Minifigure",
"Promotional: Miscellaneous",
"Promotional: Monthly Mini Model Build",
"Quatro",
"Racers",
"Racers: Drome Racers",
"Racers: Ferrari",
"Racers: Lamborghini",
"Racers: Outdoor RC",
"Racers: Power Racers",
"Racers: Product Collection",
"Racers: Radio-Control",
"Racers: Speed Racer",
"Racers: Tiny Turbos",
"Racers: Williams F1",
"Racers: Xalax",
"Rock Raiders",
"Rock Raiders: Promotional",
"Samsonite",
"Samsonite: Basic",
"Samsonite: Model Maker",
"Samsonite: Pre-School/Jumbo",
"Samsonite: Train",
"Scala",
"Scala: Jewellery",
"Seasonal",
"Seasonal: Birthday",
"Seasonal: Christmas",
"Seasonal: Easter",
"Seasonal: Halloween",
"Seasonal: Spring",
"Seasonal: Summer",
"Seasonal: Thanksgiving",
"Seasonal: Valentines",
"Seasonal: Winter Village",
"Serious Play",
"Service Packs",
"Service Packs: Adventurers",
"Service Packs: Aquazone",
"Service Packs: Belville",
"Service Packs: Castle",
"Service Packs: Divers",
"Service Packs: Duplo",
"Service Packs: Monorail",
"Service Packs: Pirates",
"Service Packs: Primo",
"Service Packs: Scala",
"Service Packs: Space",
"Service Packs: Technic",
"Service Packs: Toolo",
"Service Packs: Town",
"Service Packs: Trains",
"Service Packs: Western",
"Space",
"Space: Alien Conquest",
"Space: Blacktron",
"Space: Blacktron 2",
"Space: Bonus/Value Pack",
"Space: Classic",
"Space: Exploriens",
"Space: Futuron",
"Space: Galaxy Squad",
"Space: Ice Planet 2002",
"Space: Insectoids",
"Space: Life On Mars",
"Space: M-Tron",
"Space: Mars Mission",
"Space: Miscellaneous",
"Space: Product Collection",
"Space: RoboForce",
"Space: Space Police",
"Space: Space Police 2",
"Space: Space Police 3",
"Space: Spyrius",
"Space: UFO",
"Space: Unitron",
"Spider-Man",
"Spider-Man: Product Collection",
"SpongeBob SquarePants",
"Sports",
"Sports: Basketball",
"Sports: Football",
"Sports: Gravity Games",
"Sports: Hockey",
"Sports: Product Collection",
"Spybotics",
"Star Wars",
"Star Wars: Episode I",
"Star Wars: Episode II",
"Star Wars: Episode III",
"Star Wars: Episode IV-VI",
"Star Wars: Expanded Universe",
"Star Wars: Mini Building Set",
"Star Wars: Minifig Pack",
"Star Wars: Miscellaneous",
"Star Wars: Planet Set",
"Star Wars: Product Collection",
"Star Wars: Promotional",
"Star Wars: Seasonal",
"Star Wars: Technic",
"Star Wars: The Clone Wars",
"Star Wars: The Old Republic",
"Star Wars: Ultimate Collector Series",
"Star Wars: Virtual Product Collection",
"Star Wars: Yoda Chronicles",
"Studios",
"Studios: Jurassic Park III",
"Studios: Spider-Man",
"Super Heroes",
"Super Heroes: Constraction",
"Super Heroes: DC Universe",
"Super Heroes: Marvel Universe",
"Super Heroes: Virtual Product Collection",
"Technic",
"Technic: Arctic",
"Technic: Competition",
"Technic: Microtechnic",
"Technic: Product Collection",
"Technic: Robo Riders",
"Technic: Slizer",
"Technic: Speed Slammers",
"Technic: Supplementary",
"Technic: Universal",
"Teenage Mutant Ninja Turtles",
"The Hobbit",
"Time Cruisers",
"Time Cruisers: Time Twisters",
"Town",
"Town: Accessories",
"Town: Arctic",
"Town: Boats",
"Town: Bonus/Value Pack",
"Town: City",
"Town: Classic",
"Town: Coastguard",
"Town: Construction",
"Town: Divers",
"Town: Emergency",
"Town: Extreme Team",
"Town: Fire",
"Town: Flight",
"Town: Football",
"Town: Launch Command",
"Town: Leisure",
"Town: Maintenance",
"Town: Medical",
"Town: Monorail",
"Town: Outback",
"Town: Paradisa",
"Town: Police",
"Town: Postal",
"Town: Product Collection",
"Town: Race",
"Town: Racing",
"Town: Res-Q",
"Town: Rescue",
"Town: Seasonal",
"Town: Shell",
"Town: Shops and Services",
"Town: Space Port",
"Town: Special",
"Town: Telekom",
"Town: Vehicles",
"Toy Story",
"Trains",
"Trains: 4.5/12 V",
"Trains: 9 V",
"Trains: Product Collection",
"Universal Building Set",
"Universal Building Set: Gears",
"Vikings",
"Western",
"Western: Cowboys",
"Western: Indians",
"World City",
"World City: Police and Rescue",
"World City: Product Collection",
"World City: Special",
"World City: Trains",
"World Racers"
   ];

   $('input#theme').autocomplete({
      source: themeList,
      select: function( event, ui ) {
         $("input#theme").val(ui.item.value);
         $("#page").html(1);
         $('#last_page').html(0);
         updateSets();
      },
      change: function( event, ui ) {
         $("input[name='theme']").val(ui.item.value);
         $("#page").html(1);
         $('#last_page').html(0);
         updateSets();
      }
   });
});

