<?php namespace Enforce;

use \Exception, \Illuminate\Database\Eloquent, \Illuminate\Database\Eloquent\ModelNotFoundException;

class Builder extends Eloquent\Builder
{
    protected $enforceOnRead = null;

    /**
     * Execute the query and get the first result.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static|null
     */
    public function first($columns = array('*'), $enforceOnRead=null)
    {
        $this->enforceOnRead = $enforceOnRead === null ? \Config::get('enforce.byDefault') : $enforceOnRead;
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function firstOrFail($columns = array('*'), $enforceOnRead=null)
    {
        if ( ! is_null($model = $this->first($columns, $enforceOnRead))) return $model;

        throw new ModelNotFoundException;
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static|null
     */
    public function find($id, $columns = array('*'), $enforceOnRead=null)
    {
        $this->enforceOnRead = $enforceOnRead === null ? \Config::get('enforce.byDefault') : $enforceOnRead;

        if (is_array($id))
        {
            $results = $this->findMany($id, $columns);
        }
        else 
        {
            $this->query->where($this->model->getKeyName(), '=', $id);

            $results = $this->first($columns, $enforceOnRead);
        }

        return $results;
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function findOrFail($id, $columns = array('*'), $enforceOnRead=null)
    {
        $this->enforceOnRead = $enforceOnRead === null ? \Config::get('enforce.byDefault') : $enforceOnRead;

        if ( ! is_null($model = $this->find($id, $columns, $enforceOnRead))) return $model;

        throw new ModelNotFoundException;
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array  $columns
     * @return array|static[]
     */
    public function getModels($columns = array('*'))
    {
        // First, we will simply get the raw results from the query builders which we
        // can use to populate an array with Eloquent models. We will pass columns
        // that should be selected as well, which are typically just everything.
        $results = $this->query->get($columns);

        $connection = $this->model->getConnectionName();

        $models = array();

        // Once we have the results, we can spin through them and instantiate a fresh
        // model instance for each records we retrieved from the database. We will
        // also set the proper connection name for the model after we create it.
        foreach ($results as $result)
        {
            $model = $this->model->newFromBuilder($result);
            $class = get_class($model);

            if ( $this->enforceOnRead )
            {
                if ( $model = $class::enforceOnRead($model) )
                {
                    $model->setConnection($connection);
                    $models[] = $model;
                }
            }
            else 
            {
                $model->setConnection($connection);
                $models[] = $model;
            }
        }

        return $models;
    }
}