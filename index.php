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
		$filteredNote = ( $grandFilteredAbsTotal == $grandAbsTotal ) ? '' : ", $grandFilteredAbsTotal (filtered -- check manually)";

		echo "<p>Found contributions for $username between $start and $end in the article namespace. Grand total: $grandNetTotal (net), $grandAbsTotal (absolute)$filteredNote</p><ul>";
		foreach( $byPage as $title => $sizeDiffs ){
			echo "<li><strong>$title</strong>: " . $netTotals[$title] . ' net ' . $absTotals[$title] . ' absolute ([' . implode( '], [', $sizeDiffs ) . '])</li>';
		}
		echo '</ul>';
	}

	echo get_html( 'footer' );