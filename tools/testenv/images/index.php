<?php
/* get images for different types
 *
 * Example url: /tools/testenv/images/?group=max&avatar=100&gallery=33
 * Or from cli: php index.php --group=max --avatar=100 --gallery=33
 * In both cases each parameter is optional (but nothing is done when all are omitted)
 * 'max' will create the real number of images based on db.
 * Any number limits the amount of images (excl. thumbnails) to that number
 *
 * 'max' is the most realistic scenario but this could take up a serious amount of
 * time (several hours, depending on your system) and diskspace (3 GB), so be aware
 * before you do so. Only groups are not so many images
 * For avatar and gallery the last created id is stored in a little file: status.csv
 * That will serve as a starting point for any new calls, in case of a data-update or if
 * you want to do it in stages.
 **/

set_time_limit(0);
ini_set('memory_limit', '256M');

require 'autoload.php';
spl_autoload_register('main');

$group = new GroupImagesCreator();
$group->getImages();

$avatar = new AvatarImagesCreator();
$avatar->getImages();

$gallery = new GalleryImagesCreator();
$gallery->getImages();
