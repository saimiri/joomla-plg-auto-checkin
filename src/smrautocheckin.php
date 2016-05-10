<?php
/**
 * A Joomla! plugin used to automatically check in articles, menus and modules
 * that have not been checked out for a certain period.
 *
 * Copyright 2016 Saimiri Design.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright		Copyright (c) 2016 Saimiri Design (http://www.github.com/saimiri)
 * @author			Juha Auvinen <juha@saimiri.fi>
 * @license			http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link				http://www.github.com/saimiri/joomla-plg-auto-checkin
 * @since				File available since Release 1.0.0
 */
defined( '_JEXEC' ) or die( 'hard.' );

class plgUserSmrAutoCheckin extends JPlugin
{

	/**
	 * Runs after a user has successfully logged in.
	 *
	 * @param array $options An array with: - remember
	 *                                      - return
	 *                                      - entry_url
	 *                                      - action
	 *                                      - user (JUser Object)
	 *                                      - responseType
	 */
	public function onUserAfterLogin( Array $options ) {
		$this->clearCheckouts( $this->params->get( 'checkout_expiry_time', 60 ) );
	}

	/*¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*\
	               ~ PRIVATE AND PROTECTED METHODS AND PROPERTIES ~
	\*__________________________________________________________________________*/

	/**
	 * Load translations automatically.
	 *
	 * @var boolean
	 */
	protected $autoLoadLanguage = true;

	/**
	 * The tables that are processed for expired checkouts.
	 *
	 * @var array
	 */
	protected $tables = [
		'#__content', '#__categories', '#__menu', '#__modules'
	];

	/**
	 * Loops through certain tables and resets all checkouts that are older than
	 * $minutes.
	 *
	 * @param   int   $minutes  Checkout expiry time in minutes
	 * @return  type
	 */
	protected function clearCheckouts( $minutes ) {
		if ( $minutes == 0 ) {
			return;
		}

		// Joomla stores the dates to the database in GMT, which is likely to
		// be different than the default timezone of the server
		$dateTime = new DateTime( "now", new DateTimeZone( 'GMT' ) );
		$dateTimeDiff = DateInterval::createFromDateString( (int)$minutes . ' minutes' );

		$isoDateTime = $dateTime->sub( $dateTimeDiff )->format( 'Y-m-d H:i:s' );

		$db = JFactory::getDbo();

		foreach ( $this->tables as $table ) {
			$query = $db->getQuery( true );
			$query->update( $table )
			      ->set( "checked_out = 0" )
			      ->set( "checked_out_time = '0000-00-00 00:00:00'" )
			      // TODO: Not certain if this helps or hinders the query
			      ->where( ( $table == '#__content' ? 'state' : 'published' ) . " = 1" )
			      ->where( "checked_out != 0" )
			      ->where( "checked_out_time < " . $db->quote( $isoDateTime ) );
			$db->setQuery( $query );
			$db->execute();
		}
	}
}
