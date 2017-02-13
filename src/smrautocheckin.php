<?php
/**
 * A Joomla! plugin used to automatically check in articles, menus and modules
 * that have not been checked out for a certain period.
 *
 * As of version x it also supports other extensions that conform to the
 * standard Joommla! checkout method: using two columns in table to denote the
 * user that has checked out the item and the time it was checked out.
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
		$expiryTime = $this->params->get( 'checkout_expiry_time', 60 );
		$this->clearCoreCheckouts( $expiryTime	);
		$this->clearAdditionalCheckouts( $expiryTime, $this->params->get( 'tables' ) );
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

	protected $langLoaded = false;

	/**
	 * The tables that are processed for expired checkouts.
	 *
	 * @var array
	 */
	protected $tables = [
		'#__content', '#__categories', '#__menu', '#__modules'
	];

	/**
	 *
	 * @param type $tables
	 */
	protected function clearAdditionalCheckouts( $minutes, $tables )
	{
		if ( empty( $tables ) ) {
			return;
		}
		$db = JFactory::getDbo();
		foreach ( $tables as $table ) {
			// This is needed because validation does not currently work with subforms.
			if ( !$this->verifyTable( $table ) ) {
				$this->showMessage( $table, 'SMR_AUTO_CHECKIN_ERROR_INVALID_TABLE' );
				continue;
			}

			if ( $table->checked_out_time_zone === 'GMT' ) {
				$dateTime = new DateTime( "now", new DateTimeZone( 'GMT' ) );
			} else {
				$dateTime = new DateTime( "now" );
			}

			$dateTimeDiff = DateInterval::createFromDateString( (int)$minutes . ' minutes' );
			$isoDateTime = $dateTime->sub( $dateTimeDiff )->format( 'Y-m-d H:i:s' );

			// Can't wait to get support for prepared statements...
			$q = $db->getQuery( true );
			$q->update( $db->quoteName( '#__' . $db->escape( $table->table_name ) ) )
			  ->set( $db->escape(  $table->checked_out_column ) . ' = 0' )
				->set( $db->escape(  $table->checked_out_time_column ) . " =  '0000-00-00 00:00:00'" )
				->where( $db->escape(  $table->checked_out_column ) . ' != 0' )
				->where( $db->escape(  $table->checked_out_time_column ) . ' <  ' . $db->quote( $isoDateTime ) )
				;
			if ( !empty( $table->status_column ) && $table->status_column_value !== '' ) {
				$q->where( $db->escape(  $table->status_column ) . ' = ' . (int)$table->status_column_value );
			}
			$db->setQuery( $q );
			$db->execute();
		}
	}

	/**
	 * Loops through certain core tables and resets all checkouts that are older
	 * than $minutes.
	 *
	 * @param   int   $minutes  Checkout expiry time in minutes
	 * @return  type
	 */
	protected function clearCoreCheckouts( $minutes )
	{
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

	protected function loadLanguageFile()
	{
		JFactory::getLanguage()->load( 'plg_user_smrautocheckin', dirname( __FILE__ ) );
		$this->langLoaded = true;
	}

	protected function showMessage( $table, $message, $type = 'error' )
	{
		// Language files are not automatically loaded at this point
		if ( !$this->langLoaded ) {
			$this->loadLanguageFile();
		}
		if ( $type === 'error' ) {
			$msg = JText::sprintf( $message, $table->table_name );
		} else {
			$msg = JText::_( $message );
		}
		JFactory::getApplication()->enqueueMessage( $msg, $type );
	}


	protected function verifyTable( $table )
	{
		$regex = '/^[a-zA-Z0-9_]+$/';
		if ( !preg_match( $regex, $table->table_name ) ||
		     !preg_match( $regex, $table->checked_out_column ) ||
		     !preg_match( $regex, $table->checked_out_time_column ) ) {
			return false;
		}
		if ( !empty( $table->status_column ) &&
			   ( !preg_match( $regex, $table->status_column ) ||
			     !preg_match( '/^-?[0-9]+$/', $table->status_column_value ) ) ) {
			return false;
		}
		return true;
	}
}
