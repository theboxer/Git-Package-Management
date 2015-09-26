<?php

namespace GPM;

final class Utils
{
    public static function getPublicVars($object)
    {
        return is_string($object) ? get_class_vars($object) : get_object_vars($object);
    }
}