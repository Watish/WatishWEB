<?php

namespace Watish\Components\Struct;


use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use Watish\Components\Utils\Logger;

class DatabaseExtend extends Builder
{
    private $pdo;

    public function __construct(ConnectionInterface $connection, Grammar $grammar = null, Processor $processor = null,$pdo)
    {
        parent::__construct($connection, $grammar, $processor);
        $this->connection = $connection;
        $this->pdo = $pdo;
    }

    public function count($columns = '*'): int
    {
        $sql = parent::toSql();
        $statement = $this->pdo->prepare($sql);
        $bindings = parent::getBindings();
        $statement->execute($bindings);
        return $statement->rowCount();
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
        return new DatabaseExtend($this->connection,$this->grammar,$this->processor,$this->pdo);
    }

    public function insert(array $values): bool
    {
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
        $statement = $this->pdo->prepare($this->grammar->compileInsert($this, $values));
        $statement->execute($this->cleanBindings(Arr::flatten($values, 1)));
        return $statement->rowCount();
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
        $sql = parent::toSql();
        $statement = $this->pdo->prepare($sql);
        $bindings = parent::getBindings();
        $statement->execute($bindings);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
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
        $sql = parent::toSql();
        $statement = $this->pdo->prepare($sql);
        $bindings = parent::getBindings();
        $statement->execute($bindings);
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    public function exists():bool
    {
        $sql = parent::toSql();
        $statement = $this->pdo->prepare($sql);
        $bindings = parent::getBindings();
        $statement->execute($bindings);
        return ($statement->rowCount()>0);
    }

    public function update(array $values) :int
    {
        //Raw Logic
        $this->applyBeforeQueryCallbacks();
        $sql = $this->grammar->compileUpdate($this, $values);
        $bindings = $this->cleanBindings(
            $this->grammar->prepareBindingsForUpdate($this->bindings, $values)
        );
        //Override
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);
        return $statement->rowCount();
    }

    public function delete($id = null): int
    {
        //Raw Logic
        if (! is_null($id)) {
            $this->where($this->from.'.id', '=', $id);
        }
        $this->applyBeforeQueryCallbacks();

        //
        $statement = $this->pdo->prepare($this->grammar->compileDelete($this));
        $statement->execute($this->cleanBindings(
            $this->grammar->prepareBindingsForDelete($this->bindings)));
        return $statement->rowCount();
    }

    public function toSql(): string
    {
        $sql = parent::toSql();
        Logger::debug($sql);
        return $sql;
    }


}
