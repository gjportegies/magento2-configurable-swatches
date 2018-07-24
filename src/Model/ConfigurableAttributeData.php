<?php

namespace SR\ConfigurableSwatches\Model;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;

class ConfigurableAttributeData extends \Magento\ConfigurableProduct\Model\ConfigurableAttributeData
{

    /**
     * @var Product
     */
    protected $products;

    /**
     * Get product attributes
     *
     * @param Product $product
     * @param array $options
     * @return array
     */
    public function getAttributesData(Product $product, array $options = [])
    {
        $defaultValues = [];
        $attributes = [];
        $this->products = $product->getTypeInstance()->getUsedProducts($product);
        foreach ($product->getTypeInstance()->getConfigurableAttributes($product) as $attribute) {
            $attributeOptionsData = $this->getAttributeOptionsData($attribute, $options);
            if ($attributeOptionsData) {
                $productAttribute = $attribute->getProductAttribute();
                $attributeId = $productAttribute->getId();
                $attributes[$attributeId] = [
                    'id' => $attributeId,
                    'code' => $productAttribute->getAttributeCode(),
                    'label' => $productAttribute->getStoreLabel($product->getStoreId()),
                    'options' => $attributeOptionsData,
                    'position' => $attribute->getPosition(),
                ];
                $defaultValues[$attributeId] = $this->getAttributeConfigValue($attributeId, $product);
            }
        }
        return [
            'attributes' => $attributes,
            'defaultValues' => $defaultValues,
        ];
    }

    /**
     * Get minimal price from simple products
     *
     * @param $attributeProductsId
     * @return mixed
     */
    protected function getMinPrice($attributeProductsId)
    {
        $price;
        foreach ($this->products as $product) {
            foreach ($attributeProductsId as $productId) {
                if ($product->getId() == $productId) {
                    $price = (isset($price) && $price < $product->getFinalPrice())
                        ? $price
                        : $product->getFinalPrice();
                }
            }
        }
        return $price;
    }

    /**
     * @param Attribute $attribute
     * @param array $config
     * @return array
     */
    protected function getAttributeOptionsData($attribute, $config)
    {
        $attributeOptionsData = [];
        $prices= [];
        foreach ($attribute->getOptions() as $attributeOption) {
            $optionId = $attributeOption['value_index'];
            if (isset($config[$attribute->getAttributeId()][$optionId])) {
                $productsIds = $config[$attribute->getAttributeId()][$optionId];
                $prices[$optionId] = $this->getMinPrice($productsIds, $optionId);
            }  else {
                $productsIds = [];
            }
            $attributeOptionsData[] = [
                'id' => $optionId,
                'label' => $attributeOption['label'],
                'products' => $productsIds,
                'minPrice' => $prices[$optionId],
            ];
        }
        usort($attributeOptionsData, function ($item1, $item2) {
            return $item1['minPrice'] <=> $item2['minPrice'];
        });
        return $attributeOptionsData;
    }
}