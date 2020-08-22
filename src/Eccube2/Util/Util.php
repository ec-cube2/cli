<?php

namespace Eccube2\Util;

class Util
{
    public function getRandomString($length)
    {
        return \SC_Utils_Ex::sfGetRandomString($length);
    }
}
