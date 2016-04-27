<?php
/**
 * Created by PhpStorm.
 * User: invictus
 * Date: 1/11/2016
 * Time: 11:04 PM
 */

namespace app\InvictusClasses;


class ImageManipulation
{

    public static function isCorrectSize($photo, $maxWidth, $maxHeight)
    {
        list($width, $height) = getimagesize($photo);
        return (($width == $maxWidth) && ($height == $maxHeight));
    }


}