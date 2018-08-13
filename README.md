# Interjar ConfigurableChildVisibility
Magento 2.x Extension to Fix a few issues surrounding Configurable child products visibility when out of stock.

Various Related Core Issues:

- [#16069](https://github.com/magento/magento2/issues/16069)
- [#13411](https://github.com/magento/magento2/issues/13411)
- [#10454](https://github.com/magento/magento2/issues/10454)
- [Related PR #12936](https://github.com/magento/magento2/pull/12936)

Possibly some more too..


# Whats it all about?

So, in Magento we have an option in config:

**Stores > Configuration > Catalog > Inventory > Display Out Of Stock Products (Y/N)**

This implies that we'll get to see any out of stock products at any point where we would expect. For a lot of people - me included, it implies we'd get to see child options etc.

Unfortunately its not the case. There are quite a few places where products are removed from various collections due to the `stock_status` value. 

In this extension I've attempted to stop this from happening **if** the aforementioned configuration is set to **Yes**.

# Note

This is really temporary solution, we're hoping to get the issues fixed in the core following conversations with various developers/maintainers/contributors. The problem with this is that its a case of, is this a Bug or a Feature?

**If you would like to show options/swatches even if all children are out of stock you need to make a template change, you need to remove the $product->isSalable() checks from the Magento_Catalog::product/view/form.phtml template**

# Installation

- Add the module to composer:

        composer require interjar/module-configurable-child-visibility

- Enable the module:

        bin/magento module:enable Interjar_ConfigurableChildVisibility

- Deploy static content and compile DI:

        bin/magento setup:static-content:deploy
        bin/magento setup:di:compile

- Clear cache

# Support

If you have any issues with this extension, open an issue on [GitHub](https://github.com/Interjar/configurable-child-visibility/issues).

# Contribution

Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

# License

[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

# Copyright

&copy; 2018 [Interjar](https://www.interjar.com) Ltd
