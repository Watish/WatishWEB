<?php

namespace Watish\Components\Utils;

use Opis\Closure\SerializableClosure;

class Table
{
    private static \Swoole\Table $table;
    private static int $chunk_size;
    private static int $real_row;

    public static function init(int $row = 1024 , int $chunk_size = 64): void
    {
        $row = 512 * CPU_NUM;
        if($row > 4096)
        {
            $row = 4096;
        }
        $table = new \Swoole\Table($row);
        $table->column("data",\Swoole\Table::TYPE_STRING,$chunk_size);
        $table->column("chunk",\Swoole\Table::TYPE_INT);
        $table->create();
        self::$real_row = self::real_row_calc($table,$chunk_size);
        self::$chunk_size = $chunk_size;
        self::$table = $table;
    }

    public static function set(string $key,mixed $value) :void
    {
        $md5_key = self::get_md5_key($key);
        $table = self::$table;
        if(!$table->exist($md5_key))
        {
            //Newly set
            self::set_chunks($md5_key,$value);
        }else{
            //Override set
            self::override_chunks($md5_key,$value);
        }
    }

    /**
     * @param string $key
     * @return void
     */
    public static function del(string $key) :void
    {
        $md5_key = self::get_md5_key($key);
        $table = self::$table;
        if(!$table->exist($md5_key))
        {
            return;
        }
        self::clear_chunks($md5_key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function get(string $key) :mixed
    {
        $md5_key = self::get_md5_key($key);
        $table = self::$table;
        if(!$table->exist($md5_key))
        {
            return null;
        }
        $data = self::read_chunks($md5_key);
        return unserialize($data);
    }

    public static function stats() :array
    {
        return self::$table->stats();
    }

    public static function count() :int
    {
        return self::$table->count();
    }

    /**
     * @return int
     */
    public static function getRealRow(): int
    {
        return self::$real_row;
    }

    /**
     * @return int
     */
    public static function getChunkSize(): int
    {
        return self::$chunk_size;
    }

    /**
     * @return \Swoole\Table
     */
    public static function getTable(): \Swoole\Table
    {
        return self::$table;
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function exists(string $key) :bool
    {
        $md5_key = self::get_md5_key($key);
        return self::$table->exist($md5_key);
    }

    private static function read_chunks(string $md5_key) :string
    {
        //Must Exists !!!
        $table = self::$table;
        $chunk_int = $table->get($md5_key,"chunk");
        if($chunk_int <= 0)
        {
            return $table->get($md5_key,"data");
        }
        $block = $table->get($md5_key,"data");
        for($start=1;$start<=$chunk_int;$start++)
        {
            $chunk_key = "{$md5_key}_chunk_{$start}";
            $block .= $table->get($chunk_key,"data");
        }
        return $block;
    }

    private static function set_chunks(string $md5_key,mixed $data) :void
    {
        //Must Not Exists!!!
        $table = self::$table;
        if($data instanceof \Closure)
        {
            $data = @new SerializableClosure($data);
        }
        $data = @serialize($data);
        $data_length = strlen($data);
        $chunk_size = self::$chunk_size;

        //Check whether to chunk
        if($data_length > $chunk_size)
        {
            //Need to chunk
            $chunk_num = (int)($data_length/$chunk_size);
            if($data_length%$chunk_size > 0)
            {
                $chunk_num++;
            }

            //Batch Set Chunks
            self::batch_chunks_set($md5_key,$data,$data_length,$chunk_size);
        }else{
            //No need to chunk
            $table->set($md5_key,[
                "data" => $data,
                "chunk" => 0
            ]);
        }
    }

    private static function override_chunks(string $md5_key,mixed $data) :void
    {
        //Must Exists!!!
        $table = self::$table;
        if($data instanceof \Closure)
        {
            $data = @new SerializableClosure($data);
        }
        $data = @serialize($data);
        $old_chunk_int = $table->get($md5_key,"chunk");

        $data_length = strlen($data);
        $chunk_size = self::$chunk_size;
        if($data_length > $chunk_size)
        {
            //Need to chunk
            $chunk_num = (int)($data_length/$chunk_size);
            if($data_length%$chunk_size > 0)
            {
                $chunk_num++;
            }

            //Check chunks which will be unused
            if($old_chunk_int > ($chunk_num-1))
            {
                //Delete unused chunks
                for($start=$chunk_num;$start<=$old_chunk_int;$start++)
                {
                    $chunk_key = "{$md5_key}_chunk_{$chunk_num}";
                    $table->del($chunk_key);
                }
            }

            //Batch Set Chunks
            self::batch_chunks_set($md5_key,$data,$data_length,$chunk_size);

        }else{
            //No need to chunk
            $table->set($md5_key,[
                "data" => $data,
                "chunk" => 0
            ]);
            //Delete old chunks
            if($old_chunk_int>0)
            {
                for($start=1;$start<=$old_chunk_int;$start++)
                {
                    $chunk_key = "{$md5_key}_chunk_{$start}";
                    $table->del($chunk_key);
                }
            }
        }
    }

    private static function batch_chunks_set(string $md5_key , string $data , int $data_length , int $chunk_size) :void
    {
        //Chunk
        $chunk_num = (int)($data_length/$chunk_size);
        if($data_length%$chunk_size > 0)
        {
            $chunk_num++;
        }
        $table = self::$table;
        for ($start = 0;$start < $chunk_num; $start++)
        {
            $chunk_data = mb_substr($data,$start*$chunk_size,$chunk_size);
            //First chunk as main block
            if($start == 0)
            {
                $table->set($md5_key,[
                    "data" =>  $chunk_data,
                    "chunk" => $chunk_num-1
                ]);
                continue;
            }

            //Chunk..
            $chunk_key = "{$md5_key}_chunk_{$start}";
            $table->set($chunk_key,[
                "data" => $chunk_data,
                "chunk" => -1
            ]);
        }
    }

    private static function clear_chunks(string $md5_key): void
    {
        //Must Exists !!!
        $table = self::$table;
        $chunk_int = $table->get($md5_key,"chunk");

        //Clear Main Block
        $table->del($md5_key);

        //Clear Chunks If Exists
        if($chunk_int>0)
        {
            for ($i=1;$i<=$chunk_int;$i++)
            {
                $table->del("{$md5_key}_chunk_{$i}");
            }
        }
    }

    private static function get_md5_key(string $key) :string
    {
        return substr(md5($key),8,16);
    }

    private static function real_row_calc(\Swoole\Table $table,int $chunk_size): int
    {
        $row_count = 0;
        $chunk_data = "";
        for($i=1;$i<=$chunk_size;$i++)
        {
            $chunk_data .= "0";
        }
        while(1)
        {
            $row_count++;
            $key = self::get_md5_key("$row_count");
            @$table->set($key,[
                    "data" => $chunk_data,
                    "chunk" => -2
            ]);
            if(!$table->exist($key))
            {
                $row_count--;
                break;
            }
        }
        for($i=1;$i<=$row_count;$i++)
        {
            $key = self::get_md5_key("$i");
            $table->del($key);
        }
        return $row_count;
    }

}
