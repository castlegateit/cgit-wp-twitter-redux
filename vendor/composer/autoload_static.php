<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita5761442e2423600bde9a0e780e5a37b
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Composer\\CaBundle\\' => 18,
            'Cgit\\Twitter\\' => 13,
        ),
        'A' => 
        array (
            'Abraham\\TwitterOAuth\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Composer\\CaBundle\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/ca-bundle/src',
        ),
        'Cgit\\Twitter\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
        'Abraham\\TwitterOAuth\\' => 
        array (
            0 => __DIR__ . '/..' . '/abraham/twitteroauth/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita5761442e2423600bde9a0e780e5a37b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita5761442e2423600bde9a0e780e5a37b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita5761442e2423600bde9a0e780e5a37b::$classMap;

        }, null, ClassLoader::class);
    }
}