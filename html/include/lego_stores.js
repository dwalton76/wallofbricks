	var stores = [
"Austria:SCS Vösendorf:113",
"Austria:Vienna Donauzentrum:135",
"Belgium:Antwerp:128",
"Canada:AB - Calgary:58",
"Canada:AB - Edmonton:114",
"Canada:BC - Surrey:115",
"Canada:BC - Vancouver:99",
"Canada:Manitoba - Winnipeg:157",
"Canada:ON - Fairview Mall Toronto:84",
"Canada:ON - Sherway Gardens, Toronto:81",
"Canada:ON - Toronto:116",
"Canada:QB - Laval:136",
"China:Shanghai:153",
"Denmark:Copenhagen (K&#248;benhavn):65",
"Denmark:LEGOLAND:75",
"France:Bordeaux:145",
"France:Levallois-Perret:106",
"France:Lille:107",
"Germany:Berlin:45",
"Germany:Berlin (Legoland Discovery Centre):52",
"Germany:Cologne (K&ouml;ln):2",
"Germany:Essen:103",
"Germany:Frankfurt:46",
"Germany:Hamburg:1",
"Germany:Leipzig:134",
"Germany:Munich (M&#252;nchen) Pasing:108",
"Germany:Munich (M&#252;nchen) Riem:3",
"Germany:Nuremberg (N&#252;rnberg):62",
"Germany:Oberhausen:4",
"Germany:Saarbr&#252;cken:87",
"Germany:Wiesbaden:54",
"Italy:Arese:148",
"Italy:Bergamo:150",
"Italy:Marcianise:149",
"Italy:Torino:151",
"Italy:Venezia:152",
"Malaysia:Johor:129",
"Singapore:JEM:154",
"Singapore:Vivo City:155",
"Singapore:Jurong Point:156",
"Singapore:Ngee Ann City:142",
"Singapore:Resort World Sentosa:141",
"Singapore:Suntec City:139",
"Sweden:Stockholm:144",
"UK:Bluewater, Kent:8",
"UK:Brighton:9",
"UK:Cardiff, Wales:63",
"UK:Glasgow:112",
"UK:Leeds:111",
"UK:Liverpool:82",
"UK:London Stratford:83",
"UK:London Westfield:64",
"UK:Manchester:104",
"UK:Milton Keynes:11",
"UK:Newcastle:132",
"UK:Sheffield:105",
"UK:Watford:109",
"USA:AL - Birmingham:47",
"USA:AZ - Chandler:32",
"USA:AZ - Phoenix:66",
"USA:AZ - Tempe:147",
"USA:CA - Anaheim:12",
"USA:CA - Costa Mesa:76",
"USA:CA - Glendale:13",
"USA:CA - Mission Viejo:86",
"USA:CA - Ontario:117",
"USA:CA - Pleasanton:89",
"USA:CA - Sacramento:34",
"USA:CA - San Diego:85",
"USA:CA - San Mateo:15",
"USA:CA - Santa Clara:16",
"USA:CA - Woodland Hills:130",
"USA:CO - Denver:17",
"USA:CO - Lone Tree:118",
"USA:CT - Danbury:159",
"USA:CT - West Hartford:138",
"USA:DE - Newark:77",
"USA:FL - LEGOLAND:72",
"USA:FL - Miami:56",
"USA:FL - Orlando:18",
"USA:FL - Sunrise:80",
"USA:GA - Alpharetta:90",
"USA:GA - Atlanta (Legoland Discovery Centre):101",
"USA:GA - Lawrenceville:19",
"USA:HI - Honolulu:119",
"USA:IL - Chicago:20",
"USA:IL - Gurnee:131",
"USA:IL - Northbrook:21",
"USA:IL - Orland Park:44",
"USA:IL - Schaumburg:22",
"USA:IN - Indianapolis:78",
"USA:KS - Overland Park:91",
"USA:MA - Braintree:35",
"USA:MA - Burlington:23",
"USA:MA - Natick:24",
"USA:MA - Peabody:93",
"USA:MD - Annapolis:37",
"USA:MD - Hanover:120",
"USA:MI - Troy:61",
"USA:MN - Minneapolis:25",
"USA:MO - Des Peres:110",
"USA:NC - Concord:43",
"USA:NC - Raleigh:38",
"USA:NJ - Bridgewater:121",
"USA:NJ - Elizabeth:92",
"USA:NJ - Freehold:122",
"USA:NJ - Paramus:50",
"USA:NV - Las Vegas:137",
"USA:NY - Elmhurst:57",
"USA:NY - Flatiron:143",
"USA:NY - Garden City:53",
"USA:NY - New York City:59",
"USA:NY - Staten Island:140",
"USA:NY - Victor:94",
"USA:NY - West Nyack:123",
"USA:NY - White Plains:124",
"USA:OH - Beachwood:79",
"USA:OH - Cincinnati:39",
"USA:OH - Columbus:51",
"USA:OK - Oklahoma City:125",
"USA:OR - Tigard:28",
"USA:PA - King of Prussia:33",
"USA:RI - Providence:158",
"USA:TN - Nashville:95",
"USA:TX - Austin:60",
"USA:TX - Dallas:42",
"USA:TX - Dallas/Fort Worth (Legoland Discovery Centre):68",
"USA:TX - Friendswood:36",
"USA:TX - Frisco:41",
"USA:TX - Houston:55",
"USA:TX - San Antonio:126",
"USA:TX - Woodlands:96",
"USA:UT - Murray:127",
"USA:VA - McLean:29",
"USA:VA - Woodbridge:30",
"USA:WA - Bellevue:31",
"USA:WA - Lynnwood:97",
"USA:WA - Seattle:146",
"USA:WI - Wauwatosa:98",
];

function loadPerCountryCityStateOptions() {
	var pab_store_id = $.cookie("pab_store_id");
	var pab_country = $.cookie("pab_country");

	// If this page doesn't have the pick-a-store dropdown then don't do anything
	if ($('select#pab_store_id').length == 0) {
		return;
	}

	// If we didn't have the country cached then look at the form select
	if (!pab_country) {
		pab_country = $('#country').val();
	}


	var options = '';
	for (i = 0; i < stores.length; i++) {
		var store_tuple = stores[i].split(':');
		var country = store_tuple[0];

		if (country === pab_country) {
			var display = store_tuple[1];
			var id = store_tuple[2];

			if (id == pab_store_id) {
				options += '<option value="'+ id +'" selected>'+ display +'</option>';
			} else {
				options += '<option value="'+ id +'">'+ display +'</option>';
			}
		}
	}
	$('select#pab_store_id').html(options);
}

$(document).ready(function() {

	//
	// Auto submit the form when the user selects the city/state.
	//
	$('select.auto-submit').change(function() {
		saveStoreIDCountryCookies();
		this.form.submit();
	});

	//
	// When the user selects a different country change the list of city/states in the dropdown
	//
	$('select#country').change(function() {
		$.removeCookie("pab_store_id");
		$.removeCookie("pab_country");

		loadPerCountryCityStateOptions();
		saveStoreIDCountryCookies();
	});

	//
	// If the user clicks 'Submit' on the Pick-A-Store selects then remember the
	// store_id and country in a cookie
	//
	$('#pick-a-store-submit').click(function() {
		saveStoreIDCountryCookies();
		this.form.submit();
	});
});

