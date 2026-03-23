<?php

namespace App\Exceptions;

use Exception;

class CircularCategoryException extends Exception
{
    protected $message = 'Circular parent detected';
}
