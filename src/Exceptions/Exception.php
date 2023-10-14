<?php

namespace LaravelLiberu\DataExport\Exceptions;

use LaravelLiberu\Helpers\Exceptions\LiberuException;

class Exception extends LiberuException
{
    public static function cannotBeCancelled()
    {
        return new static(__('Only in-progress exports can be cancelled'));
    }

    public static function deleteRunningExport()
    {
        return new static(__('The export is currently running and cannot be deleted'));
    }
}
