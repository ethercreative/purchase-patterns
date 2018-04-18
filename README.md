# Purchase Patterns
Easily keep track of products customers bought together — for Craft Commerce 2

## Requirements

This plugin requires Craft CMS 3.0.0 or later, and Craft Commerce 2.0.0-beta.2 or later.

## Installation

Purchasable via the Plugin Store.

## Usage

Use the `craft.purchasePatterns.related` function in your templates to get related products that customers also bought.

```php
ProductQuery related ( Product $myProduct [, int $limit = 8  ] )
```

The function returns a `ProductQuery`, so you can include additional query parameters as needed. The `id` and `limit` parameters are already set and shouldn't be overridden.

```twig
{% set customersAlsoBought = craft.purchasePatterns.related(
    product,
    10
).all() %}
```