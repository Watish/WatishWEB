<?php



namespace Watish\WatishWEB\Service;



class PROXY_8a70a1671462743_BaseService

{

    public function toArray(mixed $data): array

    {

        return json_decode(json_encode($data), true);

    }

}

