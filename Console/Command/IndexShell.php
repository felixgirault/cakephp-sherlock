<?php

/**
 *
 *
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@package Sherlock.Console.Command
 *	@license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class IndexShell extends AppShell {

	/**
	 *
	 */

	public function getOptionParser( ) {

		$Parser = parent::getOptionParser( );

		$Parser->addOption( 'model', [
			'help' => __( 'Model to index.' ),
			'short' => 'm',
			'required' => true
		]);

		$Parser->addOption( 'start', [
			'short' => 's',
			'default' => 0
		]);

		$Parser->addOption( 'block', [
			'short' => 'b',
			'default' => 500
		]);

		return $Parser;
	}



	/**
	 *
	 */

	public function main( ) {

		$alias = $this->params['model'];
		$start = $this->params['start'];
		$block = $this->params['block'];

		$Model = ClassRegistry::init( $alias );

		if ( !$Model ) {
			$this->out( "<error>Unable to load model: $alias</error>" );
			return;
		}

		$this->out( 'Indexing...' );

		do {
			$records = $Model->find( 'all', [
				'offset' => $start,
				'limit' => $block,
				'order' => "$alias.id"
			]);

			$this->out( $start . '-' . ( $start + count( $records )));
			$start += $block;

			foreach ( $records as $record ) {
				$Model->index( $record );
			}

		} while ( count( $records ) === $block );
	}
}
