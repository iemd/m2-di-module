<?php

namespace Foggyline\Di\Model;

class Bowl
{
    private $fruits;

    public function __construct(array $fruits = [])
    {
        $this->fruits = $fruits;
    }  

}
