<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ElementNotFoundException;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Page\Shop\Cart\SummaryPageInterface;
use Sylius\Behat\Page\Shop\Product\ShowPageInterface;
use Sylius\Behat\Service\NotificationCheckerInterface;
use Sylius\Behat\Service\SessionManagerInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Webmozart\Assert\Assert;

final class CartContext implements Context
{
    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private SummaryPageInterface $summaryPage,
        private ShowPageInterface $productShowPage,
        private NotificationCheckerInterface $notificationChecker,
        private SessionManagerInterface $sessionManager,
    ) {
    }

    /**
     * @When /^I see the summary of my (?:|previous )cart$/
     * @When /^I check details of my cart$/
     */
    public function iOpenCartSummaryPage(): void
    {
        $this->summaryPage->open();
    }

    /**
     * @When I update my cart
     */
    public function iUpdateMyCart()
    {
        $this->summaryPage->updateCart();
    }

    /**
     * @Then my cart should be empty
     * @Then my cart should be cleared
     * @Then cart should be empty with no value
     */
    public function iShouldBeNotifiedThatMyCartIsEmpty()
    {
        $this->summaryPage->open();

        Assert::true($this->summaryPage->isEmpty());
    }

    /**
     * @Given I removed product :productName from the cart
     *
     * @When I remove product :productName from the cart
     */
    public function iRemoveProductFromTheCart(string $productName): void
    {
        $this->summaryPage->open();
        $this->summaryPage->removeProduct($productName);
    }

    /**
     * @Given I change :productName quantity to :quantity
     * @Given I change product :productName quantity to :quantity in my cart
     */
    public function iChangeQuantityTo($productName, $quantity)
    {
        $this->summaryPage->open();
        $this->summaryPage->changeQuantity($productName, $quantity);
    }

    /**
     * @Then the grand total value should be :total
     * @Then my cart total should be :total
     * @Then the cart total should be :total
     * @Then their cart total should be :total
     */
    public function myCartTotalShouldBe($total)
    {
        $this->summaryPage->open();

        Assert::same($this->summaryPage->getGrandTotal(), $total);
    }

    /**
     * @Then the grand total value in base currency should be :total
     */
    public function myBaseCartTotalShouldBe($total)
    {
        $this->summaryPage->open();

        Assert::same($this->summaryPage->getBaseGrandTotal(), $total);
    }

    /**
     * @Then my cart items total should be :total
     */
    public function myCartItemsTotalShouldBe(string $itemsTotal): void
    {
        Assert::same($this->summaryPage->getItemsTotal(), $itemsTotal);
    }

    /**
     * @Then my cart taxes should be :taxTotal
     */
    public function myCartTaxesShouldBe(string $taxTotal): void
    {
        $this->summaryPage->open();

        Assert::same($this->summaryPage->getExcludedTaxTotal(), $taxTotal);
    }

    /**
     * @Then my included in price taxes should be :taxTotal
     * @Then my cart included in price taxes should be :taxTotal
     */
    public function myIncludedInPriceTaxesShouldBe(string $taxTotal): void
    {
        $this->summaryPage->open();

        Assert::same($this->summaryPage->getIncludedTaxTotal(), $taxTotal);
    }

    /**
     * @Then there should be no taxes charged
     */
    public function thereShouldBeNoTaxesCharged(): void
    {
        $this->summaryPage->open();

        Assert::false($this->summaryPage->areTaxesCharged());
    }

    /**
     * @Then my cart shipping total should be :shippingTotal
     * @Then my cart shipping should be for free
     * @Then my cart estimated shipping cost should be :shippingTotal
     */
    public function myCartShippingFeeShouldBe(string $shippingTotal = '$0.00'): void
    {
        $this->summaryPage->open();

        Assert::same($this->summaryPage->getShippingTotal(), $shippingTotal);
    }

    /**
     * @Then I should not see shipping total for my cart
     */
    public function iShouldNotSeeShippingTotalForMyCart(): void
    {
        $this->summaryPage->open();

        Assert::false($this->summaryPage->hasShippingTotal());
    }

    /**
     * @Then my discount should be :promotionsTotal
     */
    public function myDiscountShouldBe($promotionsTotal)
    {
        $this->summaryPage->open();

        Assert::same($this->summaryPage->getPromotionTotal(), $promotionsTotal);
    }

    /**
     * @Given /^there should be no shipping fee$/
     */
    public function thereShouldBeNoShippingFee()
    {
        $this->summaryPage->open();

        try {
            $this->summaryPage->getShippingTotal();
        } catch (ElementNotFoundException) {
            return;
        }

        throw new \DomainException('Get shipping total should throw an exception!');
    }

    /**
     * @Given /^there should be no discount$/
     */
    public function thereShouldBeNoDiscount()
    {
        $this->summaryPage->open();

        try {
            $this->summaryPage->getPromotionTotal();
        } catch (ElementNotFoundException) {
            return;
        }

        throw new \DomainException('Get promotion total should throw an exception!');
    }

    /**
     * @Then /^(its|theirs) price should be decreased by ("[^"]+")$/
     * @Then /^(product "[^"]+") price should be decreased by ("[^"]+")$/
     */
    public function itsPriceShouldBeDecreasedBy(ProductInterface $product, $amount)
    {
        $this->summaryPage->open();

        $quantity = $this->summaryPage->getQuantity($product->getName());
        $itemTotal = $this->summaryPage->getItemTotal($product->getName());
        $regularUnitPrice = $this->summaryPage->getItemUnitRegularPrice($product->getName());

        Assert::same($this->getPriceFromString($itemTotal), ($quantity * $regularUnitPrice) - $amount);
    }

    /**
     * @Then /^(product "[^"]+") price should not be decreased$/
     */
    public function productPriceShouldNotBeDecreased(ProductInterface $product)
    {
        $this->summaryPage->open();

        Assert::false($this->summaryPage->isItemDiscounted($product->getName()));
    }

    /**
     * @Given /^an anonymous user added (product "([^"]+)") to the cart$/
     * @Given /^I (?:add|added) (this product) to the cart$/
     * @Given /^I have (product "[^"]+") added to the cart$/
     * @Given I added product :product to the cart
     * @Given he added product :product to the cart
     * @Given /^I (?:have|had) (product "[^"]+") in the cart$/
     * @Given /^the customer (?:added|adds) ("[^"]+" product) to the cart$/
     * @Given /^I (?:add|added) ("[^"]+" product) to the (cart)$/
     *
     * @When I add product :product to the cart
     * @When they add product :product to the cart
     */
    public function iAddProductToTheCart(ProductInterface $product): void
    {
        $this->productShowPage->open(['slug' => $product->getSlug()]);
        $this->productShowPage->addToCart();

        $this->sharedStorage->set('product', $product);
    }

    /**
     * @When /^I add (products "([^"]+)" and "([^"]+)") to the cart$/
     * @When /^I add (products "([^"]+)", "([^"]+)" and "([^"]+)") to the cart$/
     */
    public function iAddMultipleProductsToTheCart(array $products)
    {
        foreach ($products as $product) {
            $this->iAddProductToTheCart($product);
        }
    }

    /**
     * @When /^an anonymous user in another browser adds (products "([^"]+)" and "([^"]+)") to the cart$/
     */
    public function anonymousUserAddsMultipleProductsToTheCart(array $products): void
    {
        $this->sessionManager->changeSession();

        foreach ($products as $product) {
            $this->iAddProductToTheCart($product);
        }
    }

    /**
     * @When I add :variantName variant of product :product to the cart
     * @When /^I add "([^"]+)" variant of (this product) to the cart$/
     *
     * @Given I have :variantName variant of product :product in the cart
     * @Given /^I have "([^"]+)" variant of (this product) in the cart$/
     */
    public function iAddProductToTheCartSelectingVariant($variantName, ProductInterface $product)
    {
        $this->productShowPage->open(['slug' => $product->getSlug()]);
        $this->productShowPage->addToCartWithVariant($variantName);

        $this->sharedStorage->set('product', $product);
        foreach ($product->getVariants() as $variant) {
            if ($variant->getName() === $variantName) {
                $this->sharedStorage->set('variant', $variant);

                break;
            }
        }
    }

    /**
     * @When /^I add (\d+) of (them) to (?:the|my) cart$/
     */
    public function iAddQuantityOfProductsToTheCart($quantity, ProductInterface $product)
    {
        $this->productShowPage->open(['slug' => $product->getSlug()]);
        $this->productShowPage->addToCartWithQuantity($quantity);
    }

    /**
     * @Given /^I have(?:| added) (\d+) (products "([^"]+)") (?:to|in) the cart$/
     *
     * @When /^I add(?:|ed)(?:| again) (\d+) (products "([^"]+)") to the cart$/
     */
    public function iAddProductsToTheCart($quantity, ProductInterface $product)
    {
        $this->productShowPage->open(['slug' => $product->getSlug()]);
        $this->productShowPage->addToCartWithQuantity($quantity);

        $this->sharedStorage->set('product', $product);
    }

    /**
     * @Then /^I should be(?: on| redirected to) my cart summary page$/
     * @Then I should not be able to address an order with an empty cart
     */
    public function shouldBeOnMyCartSummaryPage()
    {
        $this->summaryPage->waitForRedirect(3);

        $this->summaryPage->verify();
    }

    /**
     * @Then I should be notified that the product has been successfully added
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyAdded()
    {
        $this->notificationChecker->checkNotification('Item has been added to cart', NotificationType::success());
    }

    /**
     * @Then there should be one item in my cart
     */
    public function thereShouldBeOneItemInMyCart()
    {
        Assert::true($this->summaryPage->isSingleItemOnPage());
    }

    /**
     * @Then this item should have name :itemName
     */
    public function thisProductShouldHaveName($itemName)
    {
        Assert::true($this->summaryPage->hasItemNamed($itemName));
    }

    /**
     * @Then this item should have variant :variantName
     */
    public function thisItemShouldHaveVariant($variantName)
    {
        Assert::true($this->summaryPage->hasItemWithVariantNamed($variantName));
    }

    /**
     * @Then this item should have code :variantCode
     */
    public function thisItemShouldHaveCode($variantCode)
    {
        Assert::true($this->summaryPage->hasItemWithCode($variantCode));
    }

    /**
     * @When I view my cart in the previous session
     */
    public function iViewMyCartInPreviousSession(): void
    {
        $this->sessionManager->restorePreviousSession();

        $this->summaryPage->open();
    }

    /**
     * @Given I have :product with :productOption :productOptionValue in the cart
     * @Given I have product :product with product option :productOption :productOptionValue in the cart
     *
     * @When I add :product with :productOption :productOptionValue to the cart
     */
    public function iAddThisProductWithToTheCart(
        ProductInterface $product,
        ProductOptionInterface $productOption,
        string $productOptionValue,
    ): void {
        $this->productShowPage->open(['slug' => $product->getSlug()]);

        $this->productShowPage->addToCartWithOption($productOption, $productOptionValue);
    }

    /**
     * @Given /^(this product) should have ([^"]+) "([^"]+)"$/
     */
    public function thisItemShouldHaveOptionValue(ProductInterface $product, $optionName, $optionValue)
    {
        Assert::true($this->summaryPage->hasItemWithOptionValue($product->getName(), $optionName, $optionValue));
    }

    /**
     * @When I clear my cart
     */
    public function iClearMyCart()
    {
        $this->summaryPage->clearCart();
    }

    /**
     * @Then /^I should see "([^"]+)" with quantity (\d+) in my cart$/
     */
    public function iShouldSeeWithQuantityInMyCart($productName, $quantity)
    {
        Assert::same($this->summaryPage->getQuantity($productName), (int) $quantity);
    }

    /**
     * @Then /^I should see(?:| also) "([^"]+)" with unit price ("[^"]+") in my cart$/
     * @Then /^I should see(?:| also) "([^"]+)" with discounted unit price ("[^"]+") in my cart$/
     * @Then /^the product "([^"]+)" should have discounted unit price ("[^"]+") in the cart$/
     */
    public function iShouldSeeProductWithUnitPriceInMyCart(string $productName, int $unitPrice): void
    {
        Assert::same($this->summaryPage->getItemUnitPrice($productName), $unitPrice);
    }

    /**
     * @Then /^the product "([^"]+)" should have total price ("[^"]+") in the cart$/
     */
    public function theProductShouldHaveTotalPrice(string $productName, int $totalPrice): void
    {
        Assert::same($this->summaryPage->getItemTotal($productName), $totalPrice);
    }

    /**
     * @Then /^I should see "([^"]+)" with original price ("[^"]+") in my cart$/
     */
    public function iShouldSeeWithOriginalPriceInMyCart(string $productName, int $originalPrice): void
    {
        Assert::same($this->summaryPage->getItemUnitRegularPrice($productName), $originalPrice);
    }

    /**
     * @Then /^I should see "([^"]+)" only with unit price ("[^"]+") in my cart$/
     */
    public function iShouldSeeOnlyWithUnitPriceInMyCart(string $productName, int $unitPrice): void
    {
        $this->iShouldSeeProductWithUnitPriceInMyCart($productName, $unitPrice);
        Assert::false($this->summaryPage->hasOriginalPrice($productName));
    }

    /**
     * @Given I use coupon with code :couponCode
     */
    public function iUseCouponWithCode($couponCode)
    {
        $this->summaryPage->applyCoupon($couponCode);
    }

    /**
     * @Then I should be notified that the coupon is invalid
     */
    public function iShouldBeNotifiedThatCouponIsInvalid()
    {
        Assert::same($this->summaryPage->getPromotionCouponValidationMessage(), 'Coupon code is invalid.');
    }

    /**
     * @Then total price of :productName item should be :productPrice
     */
    public function thisItemPriceShouldBe($productName, $productPrice)
    {
        $this->summaryPage->open();

        Assert::same($this->summaryPage->getItemTotal($productName), $productPrice);
    }

    /**
     * @Then /^I should be notified that (this product) cannot be updated$/
     */
    public function iShouldBeNotifiedThatThisProductDoesNotHaveSufficientStock(ProductInterface $product)
    {
        Assert::true($this->summaryPage->hasProductOutOfStockValidationMessage($product));
    }

    /**
     * @Then /^I should not be notified that (this product) cannot be updated$/
     */
    public function iShouldNotBeNotifiedThatThisProductCannotBeUpdated(ProductInterface $product)
    {
        Assert::false($this->summaryPage->hasProductOutOfStockValidationMessage($product));
    }

    /**
     * @Then my cart's total should be :total
     */
    public function myCartSTotalShouldBe($total)
    {
        $this->summaryPage->open();

        Assert::same($this->summaryPage->getCartTotal(), $total);
    }

    /**
     * @Then /^(\d)(?:st|nd|rd|th) item in my cart should have "([^"]+)" image displayed$/
     */
    public function itemShouldHaveImageDisplayed(int $itemNumber, string $image): void
    {
        Assert::contains($this->summaryPage->getItemImage($itemNumber), $image);
    }

    private function getPriceFromString(string $price): int
    {
        return (int) round((float) str_replace(['€', '£', '$'], '', $price) * 100, 2);
    }
}
