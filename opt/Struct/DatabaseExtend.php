<?php

namespace Watish\Components\Struct;


use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\PDOPool;

class DatabaseExtend extends Builder
{

    private bool $usePool = false;

    public function __construct(ConnectionInterface $connection, Grammar $grammar = null, Processor $processor = null , bool $usePool = false)
    {
        parent::__construct($connection, $grammar, $processor);
        $this->connection = $connection;
        $this->usePool = $usePool;
    }

    public function count($columns = '*'): int
    {
        if(!$this->usePool)
        {
            return parent::count($columns);
        }
        $sql = parent::toSql();
        $pdo = PDOPool::getPdo();
        $statement = $this->pdo->prepare($sql);
        $bindings = parent::getBindings();
        $statement->execute($bindings);
        $res = $statement->rowCount();
        PDOPool::putPdo($pdo);
        return $res;
    }

    public function table(string $table,$as = null): DatabaseExtend
    {
        return parent::from($table,$as);
    }

    public function clone(): DatabaseExtend
    {
        return clone $this;
    }

    public function newQuery(): DatabaseExtend
    {
        return new DatabaseExtend($this->connection,$this->grammar,$this->processor,$this->usePool);
    }

    public function insert(array $values): bool
    {
        if(!$this->usePool)
        {
            return parent::insert($values);
        }
        //Raw Logic
        if (empty($values)) {
            return true;
        }
        if (! is_array(reset($values))) {
            $values = [$values];
        }
        else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }
        $this->applyBeforeQueryCallbacks();

        //Override
        $pdo = PDOPool::getPdo();
        $statement = $pdo->prepare($this->grammar->compileInsert($this, $values));
        $statement->execute($this->cleanBindings(Arr::flatten($values, 1)));
        $res = $statement->rowCount();
        PDOPool::putPdo($pdo);
        return $res;
    }

    public function useWritePdo(): DatabaseExtend|static
    {
        $this->useWritePdo = true;
        return $this;
    }

    public function lock($value = true): DatabaseExtend|static
    {
        $this->lock = $value;

        if (! is_null($this->lock)) {
            $this->useWritePdo();
        }
        return $this;
    }

    public function get($columns = ['*']) :array
    {
        if(!$this->usePool)
        {
            return parent::get($columns)->toArray();
        }
        $sql = parent::toSql();
        $pdo = PDOPool::getPdo();
        $statement = $pdo->prepare($sql);
        $bindings = parent::getBindings();
        $statement->execute($bindings);
        $res = $statement->fetchAll(\PDO::FETCH_ASSOC);
        PDOPool::putPdo($pdo);
        return $res;
    }

    public function lockForUpdate(): DatabaseExtend|static
    {
        return $this->lock(true);
    }

    public function sharedLock(): DatabaseExtend|static
    {
        return $this->lock(false);
    }

    public function first($columns = ['*']) :array|false
    {
        if(!$this->usePool)
        {
            $resArray = parent::first($columns);
            return $resArray ? $resArray->toArray() : false;
        }
        $sql = parent::toSql();
        $pdo = PDOPool::getPdo();
        $statement = $pdo->prepare($sql);
        $bindings = parent::getBindings();
        $statement->execute($bindings);
        $res = $statement->fetch(\PDO::FETCH_ASSOC);
        PDOPool::putPdo($pdo);
        return $res;
    }

    public function exists():bool
    {
        if(!$this->usePool)
        {
            return parent::exists();
        }
        $sql = parent::toSql();
        $pdo = PDOPool::getPdo();
        $statement = $pdo->prepare($sql);
        $bindings = parent::getBindings();
        $statement->execute($bindings);
        $res = ($statement->rowCount()>0);
        PDOPool::putPdo($pdo);
        return $res;
    }

    public function update(array $values) :int
    {
        if(!$this->usePool)
        {
            return parent::update($values);
        }
        //Raw Logic
        $this->applyBeforeQueryCallbacks();
        $sql = $this->grammar->compileUpdate($this, $values);
        $bindings = $this->cleanBindings(
            $this->grammar->prepareBindingsForUpdate($this->bindings, $values)
        );
        //Override
        $pdo = PDOPool::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute($bindings);
        $res = $statement->rowCount();
        PDOPool::putPdo($pdo);
        return $res;
    }

    public function delete($id = null): int
    {
        if(!$this->usePool)
        {
            return parent::delete($id);
        }
        //Raw Logic
        if (! is_null($id)) {
            $this->where($this->from.'.id', '=', $id);
        }
        $this->applyBeforeQueryCallbacks();

        //Overide
        $pdo = PDOPool::getPdo();
        $statement = $pdo->prepare($this->grammar->compileDelete($this));
        $statement->execute($this->cleanBindings(
            $this->grammar->prepareBindingsForDelete($this->bindings)));
        $res = $statement->rowCount();
        PDOPool::putPdo($pdo);
        return $res;
    }

    public function toSql(): string
    {
        $sql = parent::toSql();
        Logger::debug($sql);
        return $sql;
    }


}
