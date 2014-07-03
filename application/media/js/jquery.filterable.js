
( function ( jQuery, global ) {

	var settings = {

		"limit": 1000,
		"selector": "select[data-filterable]",
		"host": window.location.protocol + "//" + window.location.host,

		"datasource": function ( select ) {
			var type = select.attr( 'data-type' );
			return settings.host + '/api/status/' + type
		},

		"collector": function ( select, data ) {
			var names = [];
			for ( var i = 0; i < data.length; i++ )
				names.push( data[ i ].name );
			select.filterable( names );
		},

		"ajax": {
			data: { 'format': 'json', csrf_token: _csrf_token, 'auth_method': 'ninja' },
			dataType: 'json',
			error: function( xhr ) {
				console.log( xhr.responseText );
			}
		}

	};

	var getBoxing = function ( filtered, multi ) {

		if ( multi ) {
			return $( '<div class="jq-filterable-box">' ).append(
				$( '<div class="jq-filterable-left">' ).append(
					$( '<input type="text" class="jq-filterable-filter jq-filterable-larger" placeholder="Search...">' ),
					$( '<input type="button" value="➤" class="jq-filterable-move" title="Use objects matching search">' ),
					"<br>",
					filtered.clone()
						.addClass( "jq-filterable-list" ),
					"<br>",
					$( '<div class="jq-filterable-stats jq-filterable-larger">' )
				),$( '<div class="jq-filterable-right">' ).append(
					$( '<select multiple class="jq-filterable-results">' ),
					"<br>",
					$( '<div class="jq-filterable-result-stats jq-filterable-larger">' ).append( "No items selected..." )
				)
			);
		} else {
			return $( '<div class="jq-filterable-box">' ).append(
				$( '<div class="jq-filterable-left">' ).append(
					$( '<input type="text" class="jq-filterable-filter" placeholder="Search...">' ),
					filtered.clone()
						.addClass( "jq-filterable-list" ),
					"<br>",
					$( '<div class="jq-filterable-stats">' )
				)
			);
		}

	}

	var Filterable = function Filterable ( filtered, data ) {

		var defaults = [];

		if ( filtered.find( 'option' ).length > 0 ) {
			filtered.find( 'option' ).each( function ( i, e ) {
				defaults.push( e.value );
			} );
		}

		var self = this;
		this.box = null;

		if ( filtered.attr( "multiple" ) ) {
			this.box = getBoxing( filtered, true );
			this.multiple = true;
		} else {
			this.box = getBoxing( filtered, false );
			this.multiple = false;
		}

		if (!this.multiple && !filtered.attr('data-required')) {
			data.unshift(' ');
		}

		this.results =			new Set();
		this.memory =				new Set();
		this.data =					new Set( data );

		this.filter =				this.box.find( ".jq-filterable-filter" );
		this.filtered =			this.box.find( ".jq-filterable-list" );

		this.statusbar =		this.box.find( '.jq-filterable-stats' );
		this.selected =			this.box.find( '.jq-filterable-results' );
		this.resultstats =	this.box.find( '.jq-filterable-result-stats' );
		this.mover =				this.box.find( '.jq-filterable-move' );

		if ( this.multiple ) {

			this.selected.attr( "id", this.filtered.attr( "id" ) );
			this.selected.attr( "name", this.filtered.attr( "name" ) );

			this.filtered.attr("id", this.selected.attr("id").replace('[', '_tmp['));
			this.filtered.removeAttr( "name" );

			if ( defaults.length > 0 ) {
				defaults = new Set( defaults );
				this.add( defaults );
			}

		}

		filtered.replaceWith( this.box );

		var _default = filtered.find( ':selected' );
		if ( _default ) this.filter.val( _default.val() );

		if ( _default.length < 1 && defaults.length < 1 ) {
			this.search( this.filter.val() );
		}

		// Add relevant events

		var key_timeout = null;
		this.box.on( "keypress", ".jq-filterable-filter", function ( e ) {

			if ( $.inArray( e.which, [ 32 ] ) >= 0 ) return;
			else if ( e.which == 13 ) {

				e.preventDefault();
				clearTimeout( key_timeout );
				self.search( self.filter.val() );

			} else {

				clearTimeout( key_timeout );
				key_timeout = setTimeout( function () {
					self.search( self.filter.val() );
				}, 250 );

			}

		} );

		this.selected.closest("form").on( "submit", function ( e ) {
			e.preventDefault();
			self.selected.find( "option" ).attr( "selected", true );
		} );

		this.box.on('click', '.deselect_all', function( e ) {
			e.preventDefault();
			self.reset();
		});

		if ( this.multiple ) {

			this.box.on( "change", ".jq-filterable-list, .jq-filterable-results", function ( e ) {

				var parent = $( e.target ),
					values = new Set( parent.val() );

				if ( parent[0] == self.selected[0] )
					self.remove( values );
				else self.add( values );

			} );

			this.mover.on( "click", function () {

				var values = self.search( self.filter.val(), null, true );
				self.add( values );

			} );

		}

	};

	Filterable.prototype.batcher = function batcher ( set ) {

		var iterator = new SetIterator( set ),
			self = this;

		this.selected.empty();

		return function () {

			var fragment = document.createDocumentFragment(),
				counter = 0,
				index = null,
				opt = null;

			while ( index = iterator.next() ) {

				opt = document.createElement( 'option' );
				opt.innerHTML = index;
				opt.value = index;

				fragment.appendChild( opt );

				counter++;
				if ( counter > 1000 ) break;

			}

			self.selected.append( fragment );
			return ( counter < 1000 );

		}

	};

	Filterable.prototype.add = function add ( set ) {

		var self = this;

		this.memory.reset();
		this.memory = set.union( this.memory );

		this.filtered.attr( 'disabled', 'disabled' );
		this.box.addClass( 'jq-filterable-working' );

		var batch = this.batcher( this.memory ),
			completed = batch(),
			interval = setInterval( function () {

				completed = batch();
				if ( completed ) {

					clearInterval( interval );

					self.filtered.attr( 'disabled', false );
					self.box.removeClass( 'jq-filterable-working' );
					self.search( self.filter.val() );

				}
			}, 10 );

	};

	Filterable.prototype.remove = function remove ( set ) {

		var iterator = new SetIterator( set ),
			index = null, i = null;

		while ( index = iterator.next() ) {
			i = this.memory.find( index );
			if ( i >= 0 ) this.memory.remove( i );
			this.selected.find( 'option[value="' + index + '"]' ).remove();
		}

		this.search( this.filter.val() );

	};

	Filterable.prototype.note = function note ( message, type ) {

		this.statusbar.html( message );

		if ( type && type == "error" )
			this.statusbar.attr( "data-state", "error" );
		else if ( type && type == "warning" )
			this.statusbar.attr( "data-state", "warning" );
		else this.statusbar.attr( "data-state", "info" );

	};

	Filterable.prototype.error = function error ( message ) {

		this.filter.css( { "border-color": "#f40" } );
		this.note( message, "error" );

	};

	Filterable.prototype.reset = function reset () {

		this.memory.empty();
		this.selected.empty();
		this.search( this.filter.val() );

	};

	Filterable.prototype.update_labels = function update_labels ( ) {

		if ( this.results.size() >= settings.limit ) {
			this.note( "Not all items shown; " + settings.limit + "/" + this.data.size() );
		} else {
			this.note( this.results.size() + " Items" );
		}

		// Fixes IE 9 error with dynamic options
		this.selected.css( 'width', '0px' );
		this.selected.css( 'width', '' );

		if( this.memory.size() > 0 ) {
			this.resultstats.html( this.memory.size() + " items selected. <a href='#' class='deselect_all'>Deselect all</a>" );
		} else {
			this.resultstats.text( "No items selected..." );
		}

	};

	/** method search ( string term )
	  *
	  * Searches the data array for regexp matches
	  * against term, then runs method populate.
	  *
	  * @param string term
	  * @param boolean respond
	  * @return void
	  */
	Filterable.prototype.search = function search ( term, source, respond ) {

		var memresult = [];
		this.results = new Set();

		try {
			term = new RegExp( term, "i" );
		} catch ( e ) {
			this.error( "Invalid search ( " + e.message + " ) " );
			return;
		}

		var iterator = new SetIterator( this.data ),
			index = null;

		while ( index = iterator.next() ) {
			if ( index.match( term ) )
				this.results.push( index );
		}


		this.memory.reset();
		this.results.reset();

		this.results = this.results.diff( this.memory );

		if ( respond ) {
			this.results.reset();
			return this.results;
		} else {
			this.results.shrink( 0, settings.limit );
			this.populate();
		}

	};

	/** method populate ( string array data )
	  *
	  * Searches the data array for regexp matches
	  * against term, then runs method populate.
	  *
	  * @param string term
	  * @return void
	  */
	Filterable.prototype.populate = function populate () {

		var fragment = document.createDocumentFragment(),
			iterator = null,
			opt = null,
			index = 0;

		iterator = new SetIterator( this.results );

		while ( index = iterator.next() ) {

			opt = document.createElement( 'option' );
			opt.innerHTML = index;
			opt.value = index;

			fragment.appendChild( opt );

		}

		this.filtered.empty();
		this.filtered.append( fragment );
		this.update_labels();

		if ( this.multiple ) {
			this.filtered.val([]);
		}

	};

	var Filterables = [];
	var FilterableFactory = function FilterableFactory ( data ) {

		var F = ( new Filterable( this, data ) );
		Filterables.push( F );
		return F;

	};

	jQuery.fn.filterable = FilterableFactory;
	jQuery.fn.filterable.find = function ( element ) {

		for ( var i = 0; i < Filterables.length; i++ ) {
			if ( Filterables[ i ].selected[ 0 ] == element[ 0 ] ) {
				return Filterables[ i ];
			}
		}

		return null;

	};

	function selectload ( index, element ) {

		var select = $( element );

		if ( select.attr( 'data-type' ) ) {

			settings.ajax.success = function ( data ) {
				settings.collector( select, data );
			};

			settings.ajax.url = settings.datasource( select );
			$.ajax( settings.ajax );

		} else if (select.length) {

			var options = $.map(select.children(), function(option) {return option.text;});

			select.children().each( function() {
				if (!$(this).attr('selected')) {
					select.removeOption(this.text);
				}
			} );

			select.filterable( options );

		}

	}

	$( document ).ready( function () {
		var selects = $( settings.selector );
		selects.each( selectload );
	} );

} ) ( jQuery, window );
