<?php

namespace bvdputte\kirbyQueue;
use Kirby\Toolkit\Obj;

class Job extends Obj
{
    public function get(String $key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
}