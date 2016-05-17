<?php

namespace Eav;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $primaryKey = 'entity_id';
    
    protected static $baseEntity = [];
    protected static $entityIdCache = [];
    
    protected $fillable = [
        'entity_code', 'entity_class', 'entity_table',
        'default_attribute_set_id', 'additional_attribute_table'
    ];
    
    public $timestamps = false;
    
    public function getEntityTablePrefix()
    {
        $tableName = $this->getAttribute('entity_table');
        $tablePrefix = $this->getConnection()->getTablePrefix();
        if ($tablePrefix != '') {
            $tableName = "$tablePrefix.$tableName";
        }
        return $tableName;
    }
        
    public function eavAttributeSet()
    {
        return $this->hasMany(AttributeSet::class);
    }
        
    public function eavAttributes()
    {
        return $this->hasMany(Attribute::class);
    }
    
    public static function findByCode($code)
    {
        if (!isset(static::$entityIdCache[$code])) {
            $entity= static::where('entity_code', '=', $code)->firstOrFail();
                                            
            static::$entityIdCache[$entity->getAttribute('entity_code')] = $entity->getKey();
            
            static::$baseEntity[$entity->getKey()] = $entity;
        }
                    
        return static::$baseEntity[static::$entityIdCache[$code]];
    }
    
    public static function findById($id)
    {
        if (!isset(static::$baseEntity[$id])) {
            $entity = static::findOrFail($id);
            
            static::$entityIdCache[$entity->getAttribute('entity_code')] = $entity->getKey();
            
            static::$baseEntity[$id] = $entity;
        }
                    
        return static::$baseEntity[$id];
    }
    
    public function defaultAttributeSet()
    {
        return $this->hasOne(AttributeSet::class, 'attribute_set_id', 'default_attribute_set_id');
    }
    
    public function describe()
    {
        $table = $this->getAttribute('entity_table');
        $pdo = \DB::connection()->getPdo();
        return new Collection($pdo->query("describe $table")->fetchAll());
    }
}
