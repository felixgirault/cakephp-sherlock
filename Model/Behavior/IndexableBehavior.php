<?php

use Sherlock\Sherlock;



/**
 *
 *
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@package Sherlock.Model.Behavior
 *	@license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class IndexableBehavior extends ModelBehavior {

	/**
	 *	Setup this behavior with the specified configuration settings.
	 *
	 *	### Settings
	 *
	 *	- 'nodes' array
	 *	- 'index' string Elasticsearch index.
	 *	- 'type' string Elasticsearch type.
	 *	- 'fields' array
	 *	- 'mapping' array
	 *	- 'autoIndex' boolean
	 *
	 *	@param Model $Model Model using this behavior.
	 *	@param array $config Configuration settings.
	 */

	public function setup( Model $Model, $settings = array( )) {

		$a = $Model->alias;

		if ( !isset( $this->settings[ $a ])) {
			$this->settings[ $a ] = array(
				'nodes' => array(
					array(
						'host' => 'localhost',
						'port' => 9200
					)
				),
				'index' => 'sherlock',
				'type' => $Model->table,
				'fields' => array( ),
				'mapping' => array( ),
				'autoIndex' => true
			);
		}

		$this->settings[ $a ] = array_merge(
			$this->settings[ $a ],
			( array )$settings
		);
	}



	/**
	 *
	 */

	public function afterSave( Model $Model, $created, $options = array( )) {

		// let's get fresh data
		$this->index( $Model, $Model->findById( $Model->id ));
	}



	/**
	 *
	 */

	public function index( Model $Model, $data ) {

		$a = $Model->alias;
		$document = array( );

		foreach ( $this->settings[ $a ]['fields'] as $field ) {
			list( $alias, $field ) = pluginSplit( $field );

			if ( $alias === null ) {
				$alias = $a;
			}

			if ( isset( $data[ $alias ][ $field ])) {
				$document[ $field ] = $data[ $alias ][ $field ];
			} else if ( isset( $data[ $alias ][ 0 ][ $field ])) {
				foreach ( $data[ $alias ] as $i => $assoc ) {
					$table = $Model->{$alias}->table;
					$document[ $table ][ $i ][ $field ] = $assoc[ $field ];
				}
			}
		}

		if ( is_numeric( $data )) {
			$data = $Model->findById( $data );
		}

		$Document = $this->sherlock( $Model )
			->document( )
			->index( $this->settings[ $a ]['index'])
			->type( $this->settings[ $a ]['type'])
			->document(
				$document,
				isset( $data[ $a ][ $Model->primaryKey ])
					? $data[ $a ][ $Model->primaryKey ]
					: null
			);

		$Document->execute( );
	}



	/**
	 *
	 */

	public function sherlock( Model $Model ) {

		$a = $Model->alias;

		if ( isset( $this->settings[ $a ]['Sherlock'])) {
			return $this->settings[ $a ]['Sherlock'];
		}

		$Sherlock = new Sherlock( );

		foreach ( $this->settings[ $a ]['nodes'] as $node ) {
			$Sherlock->addNode( $node['host'], $node['port']);
		}

		$this->settings[ $a ]['Sherlock'] = $Sherlock;
		return $Sherlock;
	}



	/**
	 *
	 */

	public function search( Model $Model ) {

		$a = $Model->alias;

		return $this->sherlock( $Model )->search( )
			->index( $this->settings[ $a ]['index'])
			->type( $this->settings[ $a ]['type']);
	}



	/**
	 *
	 */

	protected function _buildMapping( Model $Model ) {

		/*$schema = $Model->schema( );
		$mapping = array( );

		if ( is_array( $schema )) {
			foreach ( $schema as $field => $meta ) {
				if ( $meta['default'] !== null ) {
					$mapping[ $field ] = $meta['default'];
				}
			}
		}

		$this->settings[ $Model->alias ]['mapping'];*/
	}
}
