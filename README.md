# Api2Symfony Bundle

A Symfony2 bundle to automatically generate controllers from standard API specifications (RAML, Blueprint, Swagger...)

BUT... We only support the following specification formats now:

* RAML

But we'd like to also support:

* Blueprint
* Swagger

This bundle uses the [api2symfony](https://github.com/creads/api2symfony) library.

Installation
------------

Using composer:

```sh
composer require "creads\api2symfony-bundle":"@dev"
```

Then register the bundle in `app/AppKernel.php`:

```php
    public function registerBundles()
    {
        // in AppKernel::registerBundles()
        $bundles = array(
            // ...
            new Creads\Api2SymfonyBundle\Api2SymfonyBundle(),
            // ...
        );

        return $bundles;
    }
```

Usage
-----

Here's a Raml specification sample (`api.rml`):

```yaml

  #%RAML 0.8
  title: Api Example
  version: 1.0.1-alpha

  /posts:
    description: Collection of available post resource
    get:
      description: Get a list of post
    post:
      description: Create a new post
      /{id}:
        displayName: Post
        get:
          description: Get a single post
          responses:
            200:
              body:
                application/json:
                  example: |
                    {
                      "title": "An amazing news"
                    }
        delete:
          description: Delete a specific post
        /comments:
          description: Collection of available post's comments
          displayName: Comments
          get:
            description: Get list of comment for given post
          post:
            description: Comment a post
```

Generate controllers into `Acme\DemoBundle\Controller`:

```sh
app/console.php api2symfony:raml:generate "Acme\\DemoBundle\\Controller" api.raml
```
