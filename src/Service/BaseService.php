<?php

namespace Watish\WatishWEB\Service;

class BaseService
{
    public function toArray(mixed $data): array
    {
        return json_decode(json_encode($data), true);
    }
}
