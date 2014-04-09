Jam is a small ORM for Kohana 3.3+ that builds on top of Jelly - the project was originally started by [Jonathan Geiger](http://jonathan-geiger.com/) and co-developed by [Paul Banks](http://blog.banksdesigns.co.uk/).

[![Build Status](https://travis-ci.org/OpenBuildings/jam.png?branch=master)](https://travis-ci.org/OpenBuildings/jam)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/OpenBuildings/jam/badges/quality-score.png?s=b2c55a305a1bbf8f71019c844844178bd1f8bd3f)](https://scrutinizer-ci.com/g/OpenBuildings/jam/)
[![Code Coverage](https://scrutinizer-ci.com/g/OpenBuildings/jam/badges/coverage.png?s=1d4e07144e6884988d4ec449cdbdf4ad1312c723)](https://scrutinizer-ci.com/g/OpenBuildings/jam/)
[![Latest Stable Version](https://poser.pugx.org/openbuildings/jam/v/stable.png)](https://packagist.org/packages/openbuildings/jam)



Jam adds some much needed features to Jelly project,

* Lazy loading of collections
* Modifying Collections on the fly and saving the changes
* Extending Builder, Model and Meta classes with mixins
* Sane validation checks that don't always throw exceptions
* Mass assignment of related models with arrays (easy nested forms)
* Associations separate from Fields - more powerful and easier to extend
* Polymorphic Associations (MTI(Multiple Table Inheritance))
* A lot of tweaks to the api to make it more consistent and to remove redundancy
* Upload Files with temporary directory to survive validation fail (do not upload images twice in forms)
* Upload File Servers - Local, FTP and Rackspace
* Automatically store Uploaded images width / height to relevant fields
* Built In Popular behaviors - Paranoid, Sluggable, Nested, Sortable, Uploadable
* Updated files to the Kohana convention (inside kohana directory)
* Versitile form builder with automattic error display

As the original Jelly had a lot of great but undocumented functionality This guide will try to write about both about the new features and the hidden gems of Jelly, as a more comprehensive guide.

--------

Jam
===

* [Getting Started](guide/jam/getting-started.md)
* [Writing Models & Builders](guide/jam/models-and-builders.md)
* [Validators](guide/jam/validators.md)
* [Fields](guide/jam/fields.md)
* [Uploads](guide/jam/uploads.md)
* [Associations](guide/jam/associations.md)
* [Builder](guide/jam/builder.md)
* [Behaviors](guide/jam/behaviors.md)
* [Form Builder](guide/jam/form-builder.md)

--------

Jam was developed by [Despark](http://despark.com) Team as part of the [Clippings](http://clippings.com) Project

The guide itself is heavily influenced by [Rails Guides](http://guides.rubyonrails.org/) - they did amazing work of creating an accessible documentation and as I'm not much of a writer I've tried to follow their example as much as possible.
