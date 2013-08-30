<?php 
/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

/**
 * Uses Imagemagick to convert images.
 */
class OpenSKOS_ImageConverter 
{
	const DEFAULT_CONVERT_PATH = 'convert';
	
	/**
	 * Converts the specified image to the specified width and height and the specified extension.
	 * 
	 * @param string $sourceImagePath
	 * @param int $width
	 * @param int $height
	 * @param string $newExtension, optional
	 */
	public static function convertTo($sourceImagePath, $width, $height, $newExtension = '')
	{
		if ( ! class_exists('Imagick', false)) {
			throw new Zend_Exception('Class \'Imagick\' not found.');
		}
		
		$image = new Imagick($sourceImagePath);
		
		$destImagePath = $sourceImagePath;
		if ( ! empty($newExtension)) {
			$destImagePath = substr($destImagePath, 0, strrpos($destImagePath, '.') + 1) . $newExtension;
			$image->setImageFormat($newExtension);
		}
		
		$image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
		
		$image->writeImage($destImagePath);
	}
}