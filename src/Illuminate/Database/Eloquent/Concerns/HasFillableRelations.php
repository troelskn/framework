<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

trait HasFillableRelations
{
    /**
     * The relations that should be mass assignable.
     *
     * @var array
     */
    protected $fillable_relations = [];

    public function fillableRelations()
    {
        return $this->fillable_relations;
    }

    public function extractFillableRelations(array $attributes)
    {
        $fillableRelationsData = [];
        foreach ($this->fillableRelations() as $relationName) {
            $val = array_pull($attributes, $relationName);
            if ($val) {
                $fillableRelationsData[$relationName] = $val;
            }
        }
        return [$fillableRelationsData, $attributes];
    }

    public function fillRelations(array $fillableRelationsData)
    {
        foreach ($fillableRelationsData as $relationName => $fillableData) {
            $camelCaseName = camel_case($relationName);
            $relation = $this->{$camelCaseName}();
            $klass = get_class($relation->getRelated());
            if ($relation instanceof BelongsTo) {
                $entity = $klass::where($fillableData)->firstOrFail();
                $relation->associate($entity);
            } elseif ($relation instanceof HasOne) {
                $entity = $klass::firstOrCreate($fillable_data);
                $qualified_foreign_key = $relation->getForeignKey();
                list($table, $foreign_key) = explode('.', $qualified_foreign_key);
                $qualified_local_key_name = $relation->getQualifiedParentKeyName();
                list($table, $local_key) = explode('.', $qualified_local_key_name);
                $this->{$local_key} = $entity->{$foreign_key};
            } elseif ($relation instanceof HasMany) {
                if (!$this->exists) {
                    $this->save();
                }
                $relation->delete();
                foreach ($fillableData as $row) {
                    $entity = new $klass($row);
                    $relation->save($entity);
                }
            } elseif ($relation instanceof BelongsToMany) {
                if (!$this->exists) {
                    $this->save();
                }
                $relation->detach();
                foreach ($fillableData as $row) {
                    $entity = $klass::where($row)->firstOrFail();
                    $relation->attach($entity);
                }
            } else {
                throw new RuntimeException("Unknown or unfillable relation type $relationName");
            }
        }
    }
}