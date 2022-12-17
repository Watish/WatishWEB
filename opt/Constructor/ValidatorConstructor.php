<?php

namespace Watish\Components\Constructor;


use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;

class ValidatorConstructor
{
    public static function make(array $data,array $rules): Validator
    {
        return new Validator(new Translator(new ArrayLoader(),"zh-CN"),$data,$rules);
    }
}
