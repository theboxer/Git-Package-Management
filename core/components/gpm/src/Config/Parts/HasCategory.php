<?php
namespace GPM\Config\Parts;

use GPM\Config\Config;

trait HasCategory
{
    /** @var Config */
    protected $config;

    /** @var string[] */
    protected $category = [];

    protected function setCategory(array $category): void
    {
        if (count($category) !== 1) {
            $this->category = $category;
            return;
        }

        $categoryPath = $this->findCategoryPath([], $category[0], $this->config->categories);

        if ($categoryPath[count($categoryPath) - 1] === $category[0]) {
            $this->category = $categoryPath;
        } else {
            $this->category = $category;
        }
    }

    /**
     * @param string[] $path
     * @param string $categoryName
     * @param \GPM\Config\Parts\Element\Category[] $categories
     */
    private function findCategoryPath(array $path, string $categoryName, array $categories): array
    {
        $futureScan = [];

        foreach ($categories as $category) {
            if ($category->name === $categoryName) {
                $path[] = $category->name;
                return $path;
            }

            if (!empty($category->children)) {
                $futureScan[] = ['name' => $category->name, 'children' => $category->children];
            }
        }

        foreach ($futureScan as $childCategories) {
            $found = $this->findCategoryPath(array_merge($path, [$childCategories['name']]), $categoryName, $childCategories['children']);
            if (!empty($found)) return $found;
        }

        return [];
    }
}
