<?php
declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\OptionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option as OptionResource;
use TddWizard\Fixtures\Catalog\OptionFixtureRollback;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class OptionBuilderTest extends TestCase
{

    private $options = [];

    /** @var AttributeOptionManagementInterface */
    private $attributeOptionManagement;

    /** @var OptionResource */
    private $optionResourceModel;

    /** @var OptionFactory */
    private $optionFactory;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->attributeOptionManagement = $this->objectManager->get(AttributeOptionManagementInterface::class);
        $this->optionFactory = $this->objectManager->get(OptionFactory::class);
        $this->optionResourceModel = $this->objectManager->get(OptionResource::class);
    }

    protected function tearDown(): void
    {
        if (!empty($this->options)) {
            foreach ($this->options as $optionFixture) {
                OptionFixtureRollback::create()->execute($optionFixture);
            }
        }
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/dropdown_attribute.php
     */
    public function testAddOption()
    {
        $userDefinedAttributeCode = 'dropdown_attribute';
        $optionFixture = new OptionFixture(
            OptionBuilder::anOption($userDefinedAttributeCode)->build(), $userDefinedAttributeCode
        );
        $this->options[] = $optionFixture;

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        $option = $this->optionFactory->create();
        $this->optionResourceModel->load($option, $optionFixture->getOption()->getId());

        self::assertEquals($optionFixture->getOption()->getId(), $option->getId());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/dropdown_attribute.php
     */
    public function testAddOptionWithLabel()
    {
        $userDefinedAttributeCode = 'dropdown_attribute';
        $label = uniqid('Label ', true);
        $optionFixture = new OptionFixture(
            OptionBuilder::anOption($userDefinedAttributeCode)->withLabel($label)->build(), $userDefinedAttributeCode
        );
        $this->options[] = $optionFixture;

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        $option = $this->optionFactory->create();
        $this->optionResourceModel->load($option, $optionFixture->getOption()->getId());

        $items = $this->attributeOptionManagement->getItems(Product::ENTITY, $userDefinedAttributeCode);

        self::assertEquals($optionFixture->getOption()->getId(), $option->getId());
        $foundLabel = false;
        foreach ($items as $item) {
            if ((int)$item->getValue() === $optionFixture->getOption()->getId()) {
                self::assertEquals($label, $item->getLabel());
                $foundLabel = true;
            }
        }
        if (!$foundLabel) {
            self::fail('No label found');
        }
    }
}