
/**
 * @typedef RelationListConfig
 * @type Object
 * @property {String} baseUrl - Needed to construct full URLs for REST calls
 * @property {String} boltUrl - Needed to construct full URLs for editcontent calls*
 * @property {String} contenttype - Contenttype of the current content object. Used to retrieve the field configrations within the backend
 * @property {String} fieldName - Name of the field that is currently being edited. Used to retrieve the field configrations within the backend
 * @property {Object} validation - Holds all validation parameters
 * @property {String} validation.min - Minimum number of selected elements
 * @property {String} validation.max - Maximum number of selected elements
 */

/**
 * Constructor
 * @param {RelationListConfig} config  Holds all configurations for the component
 */
var RelationListComponent = function( config )
{
	var self = this;

	/**
	 *	Holds all necessary fields that needs to
	 * be accessed accross the component
	 */
	self.config = config || {};

	/**
	 * Api URLs to be used
	 */
	self.apiUrls = {
		findEntries: "/bolt/relationlist/finditems/##contenttype##/##field##/##search##",
		fetchJsonList: "/bolt/relationlist/fetchJsonList"
	};

	self.initialKeyword = "Search...";

	self.errorMessage = "There was a problem with the relationlist. Please try again or contact your tech support!";

	if ( typeof self.config.baseUrl !== "string" )
		console.warn("[RelationList::fetchJsonElements] self.config.baseUrl is not defined! This may lead to invalid API calls on different environments!");

	if ( typeof self.config.boltUrl !== "string" )
		console.warn("[RelationList::fetchJsonElements] self.config.boltUrl is not defined! This may lead to invalid API calls on different environments!");

	/**
	 * Generate an absolute API url based on the self.config.baseUrl setting.
	 *
	 * @param {String} relativeUrl - Url with leading slash `/`
	 *
	 * @return {String} Absolute URL to the API service
	 */
	self.getAbsoluteApiUrl = function( relativeUrl ) {
		if ( typeof self.config.baseUrl !== "string"
			|| typeof relativeUrl !== 'string'
			|| relativeUrl.length === 0 )
			return relativeUrl;

		var baseUrl = self.config.baseUrl;
		var lastBaseChar = baseUrl.substr( baseUrl.length-1, 1 );

		// Remove trailing slash
		if ( lastBaseChar === "/" || lastBaseChar === "\\" )
			baseUrl = baseUrl.substr( 0, baseUrl.length-1 );

		return ( baseUrl + relativeUrl );
	};

	/**
	 * Initialize node elements
	 */
	self.config.storageFieldNode = $("#" + self.config.fieldName);
	self.config.componentContainerNode = self.config.storageFieldNode.closest(".RelationListComponent");
	self.config.searchFieldNode = $("#search-" + self.config.fieldName);
	self.config.outputContainerNode = $("#searchResult-" + self.config.fieldName);
	self.config.selectedElementsNode = $("#selectedElements-" + self.config.fieldName);

	self.config.outputContainerNode.hide();

	/**
	 * HTML templates to be compiled
	 * using the RelationList.applyVariables method
	 */
	self.BaseEntryTemplate = "<div class='preview'>"
		+ "<img src='"+self.getAbsoluteApiUrl("/files/##thumbnail##")+"' data-url=\"##thumbnail##\"/>"
		+ "<span class=\"title\">##title##</span> "
		+ "<span class=\"contenttype\">##contenttype##</span> <span class=\"datechanged\">##datechanged##</span> - "
		+ "<span class=\"excerpt\">##excerpt##</span></a></div>";

	self.searchResultEntryTemplate = "<div class=\"entry clearfix\" data-entry-id=\"##id##\">"
		+ "<a href=\"#\">"+self.BaseEntryTemplate+"</a>"
		+ "</div>";

	self.selectedEntryTemplate = "<div class=\"entry clearfix\" data-entry-id=\"##id##\">"
		+ "<a target=\”_blank\” href=\""+self.config.boltUrl+"editcontent/##id##\">"+self.BaseEntryTemplate+"</a>"
		+ "<a href=\"#\" class=\"remove fa fa-trash\">&nbsp;</a>"
		+ "</div>";

	/**
	 * Keeps the currently processed AJAX search request
	 */
	self.searchRequest = null;

	/**
	 * Private methods
	 */

	/**
	 * Check if variable is a number
	 *
	 * @param  {[type]} n - Variable to be parsed
	 * @return {Boolean}
	 */
	self.isNumeric = function( n ) {
		return !isNaN( parseFloat( n ) ) && isFinite(n);
	};


	/**
	 * Compile HTML markup templates
	 *
	 * @param {String} template - Hold HTML markup with placeholders in the format `##key##`. `key` gets replaced by data[key]
	 * @param {Object} data - Holds the variables necessary to process the markup
	 *
	 * @return {String} Compiled HTML
	 */
	self.applyVariables = function( template, data ) {
		var result = template;
		var expr = /\#\#([a-z0-9_]*)\#\#/;
		var newResult = result;

		do {
			result = newResult;
			newResult = result.replace( expr, function parsePlaceholders( match, placeholder ){
				return ( typeof data[placeholder] === "string" ) ? data[placeholder] : "undefined";
			});
		} while ( result !== newResult );

		return result;
	};

	/**
	 * Print an info text, that there are no elements selected
	 */
	self.setNothingSelected = function( ) {
		self.config.selectedElementsNode.html("<span class=\"message\">Nothing selected...</span>")
	};

	/**
	 * Callback method that removes the clicked content element
	 * from the `selected elements` list
	 *
	 * Method requires context! (In case, use `apply`)
	 */
	self.removeEntry = function(event) {
		event.preventDefault();

		// Do validation beforehand!
		if ( self.config.validation
			&& typeof self.config.validation.min === "string"
			&& self.isNumeric(self.config.validation.min) ) {
			if ( self.config.selectedElementsNode.children(".entry").length <= self.config.validation.min ) {
				alert("At least " + self.config.validation.min + " element(s) have to be selected!" );
				return;
			}
		}

		var htmlEntry = $(this).closest('.entry');
		var elementId = $(htmlEntry).data('entry-id');

		$(htmlEntry).remove();

		if ( self.config.selectedElementsNode.children('.entry').length === 0 )
			self.setNothingSelected();

		self.storeSelectedList();
	};

	/**
	 * Callback method that adds the clicked content element
	 * to the `selected elements` list
	 *
	 * Method requires context! (In case, use `apply`)
	 */
	self.addEntry = function( ) {
		// Do validation beforehand
		if ( self.config.validation
			&& typeof self.config.validation.max === "string"
			&& self.isNumeric(self.config.validation.max) ) {
			if ( self.config.selectedElementsNode.children(".entry").length >= self.config.validation.max ) {
				alert("A maximum of " + self.config.validation.max + " element(s) can be selected!" );
				self.hideSearchPanel();
				return;
			}
		}

		var node = $(this);

		var id = node.parents("div.entry").data("entry-id");

		// Check, if element was already selected
		var found = false;
		self.config.selectedElementsNode.children(".entry").each(function(idx, element){
			if($(element).data("entry-id") == id)
				found = found | true;
		});
		if(found)
			return;

		var newElement = {
			"id": id,
			"title": node.find(".title").html(),
			"contenttype": node.find(".contenttype").html(),
			"datechanged": node.find(".datechanged").html(),
			"excerpt": node.find(".excerpt").html(),
			"thumbnail": node.find("img").data("url")
		};

		if (self.config.selectedElementsNode.children('.entry').length === 0)
			self.config.selectedElementsNode.html("");

		var remover = $(self.applyVariables( self.selectedEntryTemplate, newElement ));
		remover.find('a.remove').on('click', self.removeEntry);
		self.config.selectedElementsNode.append( remover );

		self.storeSelectedList();

		return false;
	};

	/**
	 * Adds the clicked content elements from
	 * the search list to the `selected elements` list
	 */
	self.registerClickListeners = function( ) {
		self.config.outputContainerNode.find('.entry a').mousedown(function( ) {
			self.addEntry.apply(this);
			self.config.searchFieldNode.val('');
		});
	};


	/**
	 * In case of an internal component error, the component
	 * should be freezed so that the data consistency can
	 * be obtained
	 */
	self.freeze = function ( ) {
		//self.config.searchFieldNode.prop('disabled', true);
		self.config.componentContainerNode.addClass('error');
	};

	/**
	 * Write current list into the hidden input field
	 */
	self.storeSelectedList = function( ) {
		var res = {};
		var jsonValue = '';
		var hasElements = false;

		self.config.selectedElementsNode.children(".entry").each(function(idx, element){
			res[idx] = $(element).data("entry-id");
			hasElements = true;
		});

		if(hasElements)
			jsonValue = JSON.stringify(res);

		self.config.storageFieldNode.val(jsonValue);
	};

	/**
	 * Send a JSON string with a list of IDs in form of `<contenttype>/<id>` to the API service
	 *
	 * @param {String} jsonString - Holds the ID list
	 * @param {Function} successCallback - Function to be called when finished processing. First parameter will be the data.
	 *
	 * @return {undefined}
	 */
	self.fetchJsonElements = function( jsonString, successCallback ) {
		if ( typeof jsonString === "string" && jsonString.length === 0 )
			return;

		$.ajax({
			url: self.getAbsoluteApiUrl(self.apiUrls.fetchJsonList),
			method: "POST",
			data: {
				"elements": jsonString
			},
			success: successCallback,
			error: function fetchingJsonFailed( xhr ) {
				try {
					var error = $.parseJSON(xhr.responseText);
					alert(self.errorMessage + '\n\n([RelationList::fetchJsonElements] ' + error.status +' : '+ error.message + ')');
				} catch(e) {
					alert(self.errorMessage + '\n([RelationList::fetchJsonElements])');
				}
			}
		});
	};

	/**
	 * Search function to find according entries
	 *
	 * @param {String} keyword - Term to search for
	 */
	self.findEntries = function ( keyword )
	{
		if ( self.searchRequest !== null ) {
			self.searchTermChanged = true;
			return;
		}

		self.lastSearchTerm = keyword;
		var searchUrl = self.applyVariables(
			self.getAbsoluteApiUrl( self.apiUrls.findEntries ), {
				"contenttype": self.config.contenttype,
				"field": self.config.fieldName,
				"search": keyword
			});

		var inputClass = self.config.searchFieldNode.attr('class');
		if ( inputClass.indexOf('searching') == -1 ) {
			self.config.searchFieldNode.attr('class', inputClass + ' searching');
		}

		self.searchRequest = $.ajax({
			type: "GET",
			url: searchUrl,
			success: function __searchSuccess( response ) {
				var inputClass = self.config.searchFieldNode.attr('class');
				self.config.searchFieldNode.attr('class', inputClass.replace('searching', ''));

				if ( response.status === "error" ) {
					alert(self.errorMessage + '\n\n([RelationListComponent::__searchSuccess] ' + response.status +' : ' + response.message + ')');
					return;
				}

				var data = response.data;

				if ( self
					&& self.config
					&& self.config.outputContainerNode ) {

					var outputNode = self.config.outputContainerNode;
					var htmlResult = "";

					if ( data.length === 0 ) {
						outputNode.html("No results!");
						return;
					}

					for ( var i=0; i < data.length; i++ )
						htmlResult += self.applyVariables( self.searchResultEntryTemplate, data[i] );

					outputNode.html( htmlResult );
					self.registerClickListeners();

					outputNode.show();
				}

				self.searchRequest = {};
			},
			error: function searchError( res ) {
				if ( res.statusText === "abort" )
					return;

				self.config.outputContainerNode.html("An error occured!");
				console.error('RelationList: An error occured!');
			}
		});

		self.searchRequest.then(function( ) {
			self.searchRequest = null;

			if ( self.searchTermChanged == true ) {
				self.searchTermChanged = false;

				if ( self.lastSearchTerm !== self.config.searchFieldNode.val() ) {
					self.hideSearchPanel();
					self.findEntries( self.config.searchFieldNode.val() );
				}
			}
		});

		return self.searchRequest;
	};



	/**
	 * To be called, when search process
	 * was cancelled. Removes the search result list
	 * and aborts the search request, if running.
	 */
	self.hideSearchPanel = function ( )
	{
		self.config.outputContainerNode.hide();
	};

	/**
	 * When a search is already running and further chracters are being typed,
	 * this flag will change to `true`, indicating that another search request
	 * will be done when the other one finished
	 *
	 * @type {Boolean}
	 */
	self.searchTermChanged = false;

	/**
	 * Represents the last search keyword
	 * @type {String}
	 */
	self.lastSearchTerm = null;

	/**
	 * Initialize...
	 */
	self.setNothingSelected();

	/**
	 * Add some UI interaction magic:
	 * - Search field user interactions
	 */
	self.config.searchFieldNode.on('keyup', function handleSearchInput( event ) {
		if ( self.config.searchFieldNode.val().length > 2 ) {
			self.findEntries( self.config.searchFieldNode.val() );
		}
	}).on("blur", self.hideSearchPanel);

	self.config.searchFieldNode.val( self.initialKeyword );

	self.config.searchFieldNode.on('focus', function( ) {
		if ( self.config.searchFieldNode.val() === self.initialKeyword ) {
			self.config.searchFieldNode.val('');
		}
	}).on('blur', function( ) {
		if ( self.config.searchFieldNode.val().length === 0 )
			self.config.searchFieldNode.val( self.initialKeyword );
	});


	// Store initial selected list
	var elementList = self.config.storageFieldNode.val();
	if ( elementList.length > 0 )
		elementList = JSON.parse(elementList);

	self.fetchJsonElements( elementList, function __handleInitialContentFetch( response ) {
		if ( response.status === "error" ) {
			alert(self.errorMessage + "\n\n([RelationListComponent::__handleInitialContentFetch] " + response.status + " : " + response.message + ')');
			self.freeze();
			return;
		}

		var results = response.data.results;

		if ( results.length > 0 )
			self.config.selectedElementsNode.html("");

		for ( var i=0; i < results.length; i++ ) {
			var html = self.applyVariables( self.selectedEntryTemplate, results[i] );
			self.config.selectedElementsNode.append( html );
		}

		self.config.selectedElementsNode.find(".entry a.remove").on("click", self.removeEntry);
	});

	self.config.selectedElementsNode.sortable({
		update: self.storeSelectedList
	});

	/**
	 * Public methods
	 */
	return {

		/**
		 * Return the internal list of selected elements
		 *
		 * @return {Object} List of all selected elements
		 */
		getSelectedElements: function( ) {
			return jQuery.extend( {}, self.selectedElements);
		}

	};
};
