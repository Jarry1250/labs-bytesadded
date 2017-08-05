<?php
	/*
	Bytecount generator � 2015 Harry Burt <jarry1250@gmail.com>

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
	*/

	require_once( '/data/project/jarry-common/public_html/global.php' );
	require_once( '/data/project/jarry-common/public_html/peachy/Init.php' );

	$site = Peachy::newWiki();
	$username = ( isset( $_GET['username'] ) && $_GET['username'] != "" ) ? $_GET['username'] : false;
	$start = ( isset( $_GET['start'] ) && preg_match( '/^20[012][0-9]-?[01][0-9]-?[0-3][0-9]$/', $_GET['start'] ) ) ? $_GET['start'] : '2015-01-01';
	$end = ( isset( $_GET['end'] ) && preg_match( '/^20[012][0-9]-?[01][0-9]-?[0-3][0-9]$/', $_GET['end'] ) ) ? $_GET['end'] : '2016-01-01';
	echo get_html( 'header', 'ByteCount generator' );
	if( $username === false ){
?>	<form action="index.php" method="get">
			<p><label>
				Username:
				<input name="username" type="text" value="<?=($username===false)?'':$username?>"/>
			</label></p>
			<p><label>
				Start (inclusive):
				<input name="start" type="date" value="<?=$start?>" min="2004-01-01"/>
			</label></p>
			<p><label>
				End (inclusive):
				<input name="end" type="date" value="<?=$end?>" min="2004-01-01""/>
			</label></p>
			<p><input type="submit" /></p>
		</form>
<?php
	} else {
		$user = initUser( $username );
		if( !$user->exists() ) die( 'Username not recognised.' );

		$ucArray = array(
			'_code'  => 'uc',
			'ucuser' => $username,
			'action' => 'query',
			'list'   => 'usercontribs',
			'ucnamespace' => 0,
			'ucprop'  => 'title|sizediff',
			'ucdir'   => 'newer',
			'ucstart' => str_replace( '-', '', $start ) . '000000', // oldest time
			'ucend' => str_replace( '-', '', $end ) . '235959', // newest time
		);
		$contribs = $site->listHandler( $ucArray );
		$byPage = array();
		foreach( $contribs as $contrib ){
			if( !isset( $byPage[$contrib['title']] ) ) $byPage[$contrib['title']] = array();
			array_push( $byPage[$contrib['title']], $contrib['sizediff'] );
		}
		ksort( $byPage );

		$netTotals = array();
		$absTotals = array();
		$filteredAbsTotals = array();
		foreach( $byPage as $title => $sizeDiffs ){
			$netTotals[$title] = array_sum( $sizeDiffs );
			$absTotals[$title] = array_sum( array_map( 'abs', $sizeDiffs ) );
			$filteredSizeDiffs = array();
			foreach( $sizeDiffs as $sizeDiff ){
				if( abs( $sizeDiff ) < 100 || !in_array( -1 * $sizeDiff, $sizeDiffs ) ){
					array_push( $filteredSizeDiffs, $sizeDiff );
				}
			}
			$filteredAbsTotals[$title] = array_sum( array_map( 'abs', $filteredSizeDiffs ) );
		}
		$grandNetTotal = array_sum( $netTotals );
		$grandAbsTotal = array_sum( $absTotals );
		$grandFilteredAbsTotal = array_sum( $filteredAbsTotals );
		$filteredNote = ( $grandFilteredAbsTotal == $grandAbsTotal ) ? '' : ", <span id=\"grandFilteredAbsTotal\">$grandFilteredAbsTotal</span> (filtered -- check manually)";

		echo "<p id=\"intro\">Found contributions for $username between $start and $end in the article namespace. Grand total: <span id=\"grandNetTotal\">$grandNetTotal</span> (net), <span id=\"grandAbsTotal\">$grandAbsTotal</span> (absolute)$filteredNote</p><ul>";
		foreach( $byPage as $title => $sizeDiffs ){
			echo "<li data-filteredabstotal=\"$filteredAbsTotals[$title]\"><strong>$title</strong>: <span class=\"netTotal\">{$netTotals[$title]}</span> net <span class=\"absTotal\">{$absTotals[$title]}</span> absolute ([" . implode( '], [', $sizeDiffs ) . '])</li>';
		}
		echo '</ul>';
	}
	?>
	<script type="text/javascript">
		var updateTotals = function () {
			var grandNetTotal = 0, grandAbsTotal = 0, grandFilteredAbsTotal = 0, $lis = $( 'li' ).not( '.excluded' );
			$lis.find( '.netTotal' ).each( function () {
				grandNetTotal += parseInt( $( this ).text() );
			} );
			$lis.find( '.absTotal' ).each( function () {
				grandAbsTotal += parseInt( $( this ).text() );
			} );
			$lis.each( function () {
				grandFilteredAbsTotal += parseInt( $( this ).data( 'filteredabstotal' ) );
			} );
			$( '#grandNetTotal' ).text( grandNetTotal );
			$( '#grandAbsTotal' ).text( grandAbsTotal );
			$( '#grandFilteredAbsTotal' ).text( grandFilteredAbsTotal );
		};
		$( 'li' ).each( function () {
			var $this = $( this );
			$this.click( function() {
				if( $this.hasClass( 'excluded' ) ) {
					$this.removeClass( 'excluded' );
				} else {
					$this.addClass( 'excluded' );
				}
				updateTotals();
			} );
		} );
		$( '<br/>' ).appendTo( '#intro' );
		$( '<br/>' ).appendTo( '#intro' );
		$( '<button>' ).click( function() {
			$( 'li' ).not( '.excluded' ).addClass( 'excluded' );
			updateTotals();
		} ).text( 'Deselect all' ).appendTo( '#intro' );
		$( '<button>' ).click( function() {
			$( '.excluded' ).removeClass( 'excluded' );
			updateTotals();
		} ).text( 'Select all' ).appendTo( '#intro' );

	// Basically just use the bit of Modernizr we need for Modernizr.inputtypes.date
	var Modernizr = { 'inputtypes': {} },
		inputElem = document.createElement( 'input' ), smile = ':)';
	inputElem.setAttribute( 'type', 'date' );
	if ( inputElem.type !== 'text' ) {
		inputElem.value = smile;
		inputElem.style.cssText = 'position:absolute;visibility:hidden;';
		Modernizr.inputtypes.date = ( inputElem.value != smile );
	} else {
		Modernizr.inputtypes.date = false;
	}
	</script>
	<script type="text/javascript" src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
	<script type="text/javascript" src="date-polyfill.min.js"></script>
	<style type="text/css">
		/* !HTML5 Date polyfill | Jonathan Stipe | https://github.com/jonstipe/date-polyfill */
		button.date-datepicker-button:after {
			display: inline-block;
			content: "";
			width: 0;
			height: 0;
			border-style: solid;
			border-width: 0.4em 0.4em 0em 0.4em;
			border-color: black transparent transparent transparent;
			margin: 0em 0em 0.2em 0.7em;
			vertical-align: middle;
		}
		.csstransitions div.date-calendar-dialog.date-closed {
			-moz-transition: opacity 0.4s linear;
			-webkit-transition: opacity 0.4s linear;
			-o-transition: opacity 0.4s linear;
			-ms-transition: opacity 0.4s linear;
			transition: opacity 0.4s linear;
			opacity: 0;
		}
		.csstransitions div.date-calendar-dialog.date-open {
			-moz-transition: opacity 0.4s linear;
			-webkit-transition: opacity 0.4s linear;
			-o-transition: opacity 0.4s linear;
			-ms-transition: opacity 0.4s linear;
			transition: opacity 0.4s linear;
			opacity: 1;
		}
		/* My own additions */
		.ui-datepicker-inline {
			background: white;
			padding: 5px;
		}
		.ui-datepicker-inline a {
			margin: 5px;
			text-decoration: underline;
		}
		.ui-datepicker-title {
			width: 50%;
			display: inline;
			font-weight: bold;
		}
		li {
			list-style-type: none;
		}
		li:before {
			content: '✔    ';
		}
		.excluded {
			color: #CCC;
		}
		.excluded:before {
			content: '✖    ';
		}
	</style>
<?php
	echo get_html( 'footer' );
