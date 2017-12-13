<?php

namespace Drupal\wienimal_services\Service;

use Drupal\Core\Extension\ThemeHandler;

class Wienicons
{
    const CATEGORY_CONTENT = 'content';
    const CATEGORY_MENU = 'menu';
    const CATEGORY_EXTRA = 'extra';

    const SIZE_SMALL = 's';
    const SIZE_MEDIUM = 'm';
    const SIZE_LARGE = 'l';

    const MODIFIER_VERTICAL = 'vertical';

    /** @var ThemeHandler $themeHandler */
    private $themeHandler;
    /** @var array */
    private $icons;

    /**
     * Wienicons constructor.
     * @param ThemeHandler $themeHandler
     */
    public function __construct(
        ThemeHandler $themeHandler
    ) {
        $this->themeHandler = $themeHandler;
        $this->icons = [];
    }

    /**
     * Get a list of icons of a certain category
     * @param $category
     * @return mixed
     */
    public function getIcons($category)
    {
        if (empty($this->icons[$category])) {
            $this->setIcons($category);
        }

        return $this->icons[$category];
    }

    /**
     * Generate a list of icons for a certain category
     * @param $category
     */
    private function setIcons($category)
    {
        $path = $this->themeHandler->getTheme('customal')->getPath();
        $files = file_scan_directory("$path/icons/$category", '/.+\.svg/', [], 1);

        $this->icons[$category] = array_map(function ($file) use ($path, $category) {
            $path = preg_quote($path, '/');
            $category = preg_quote($category, '/');
            return preg_replace("/$path\/icons\/$category\/(.+).svg/", '${1}', $file);
        }, array_keys($files));
    }

    /**
     * Check if an icon exists
     * @return boolean
     */
    public function hasIcon($category, $fileName)
    {
        if (!$this->hasCustomal()) {
            return false;
        }

        return in_array($fileName, $this->getIcons($category));
    }

    /**
     * Check if the Customal theme is installed
     * @return boolean
     */
    public function hasCustomal()
    {
        return $this->themeHandler->themeExists('customal');
    }

    /**
     * Get an array of classes to apply to an element to show an icon
     * @param $size
     * @param $id
     * @param $modifiers
     * @return array
     */
    public function getClassNames($size, $id, $modifiers = [])
    {
        return [
            'icon',
            "icon--$size",
            "icon--$id",
        ] + array_map(function ($modifier) { return "icon--$modifier"; }, $modifiers);
    }
}
