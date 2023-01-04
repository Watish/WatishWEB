<?php



namespace Watish\WatishWEB\Service;



class PROXY_8c9b91672803576_BaseService

{



    public function toArray(mixed $data): array

    {

        return json_decode(json_encode($data), true);

    }

}

