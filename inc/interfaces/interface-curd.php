<?php

/**
 * Interface LP_Interface_CURD
 */
interface LP_Interface_CURD{
	/**
	 * Load data from database.
	 *
	 * @param object $object
	 *
	 * @return mixed
	 */
	public function load(&$object);

	/**
	 * Update data into database.
	 *
	 * @return mixed
	 */
	public function update();

	/**
	 * Delete data from database.
	 *
	 * @return mixed
	 */
	public function delete();
}