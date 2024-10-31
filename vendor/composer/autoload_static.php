<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbb97cf4a586688a4101b777b51b543e1
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'Netcash\\PayNow\\' => 15,
        ),
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Netcash\\PayNow\\' => 
        array (
            0 => __DIR__ . '/..' . '/netcash/paynow-php/src',
        ),
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbb97cf4a586688a4101b777b51b543e1::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbb97cf4a586688a4101b777b51b543e1::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitbb97cf4a586688a4101b777b51b543e1::$classMap;

        }, null, ClassLoader::class);
    }
}