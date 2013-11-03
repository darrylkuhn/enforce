<?php namespace Enforce

class Model extends Illuminate\Database\Eloquent\Model
{
	/**
	 * Find a model by its primary key or throw an exception.
	 *
	 * @param  mixed  $id
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Model|Collection|static
	 */
	public static function findOrFail($id, $columns = array('*'))
	{
		if ( $this->enforceRead() )
		{
			return $parent->findOrFail( $id, $columns = array('*') );
		}

		throw new ModelNotFoundException;
	}
}