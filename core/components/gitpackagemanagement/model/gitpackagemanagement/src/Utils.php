<?php

namespace GPM;

final class Utils
{
    public static function getPublicVars($object)
    {
        return is_string($object) ? get_class_vars($object) : get_object_vars($object);
    }

    public static function getProfilesDir()
    {
        return dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . '/profiles';
    }
    
    public static function getGPMDir()
    {
        return dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))));
    }

    public static function isInGlobalMode()
    {
        return is_dir(self::getProfilesDir());
    }

    public static function loadProfile($profile = null)
    {
        $profilesDir = Utils::getProfilesDir();

        if ($profile === null) {
            $profileFile = dirname($profilesDir). '/.current_profile';
            if (!file_exists($profileFile)) return false;

            return json_decode(file_get_contents($profileFile), true);
        }

        $profileFile = $profilesDir. '/' . $profile . '.json';
        if (!file_exists($profileFile)) return false;

        return json_decode(file_get_contents($profileFile), true);
    }
}