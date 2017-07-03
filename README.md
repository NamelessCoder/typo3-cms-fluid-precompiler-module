TYPO3 CMS Fluid Compiler Extension
==================================

Provides a backend module and cache menu item to compile Fluid templates detected on the system, without having
to render the output. The backend module in addition provides very detailed information about the efficiency of
individual template files.


Installation
------------

Available through Composer / Packagist:

```
composer require namelesscoder/typo3-cms-fluid-precompiler-module
```

Then activate the extension in the TYPO3 extension manager (and configure the settings if you wish).

There is no TypoScript or other configuration to initialize except for the extension configuration. Once
activated all features are available through the clear cache menu or backend module respectively.


Usage
-----

Using the extension is as straight-forward as can be:

* Quickly compile all Fluid templates using the clear cache menu item, for example after a system cache flush.
* Use the backend module for much more detailed processing of templates; compile all templates or individual
  extensions' templates, navigate the structure they render (click partial names to go to that template, etc.)
  and inspect all kinds of details about which ViewHelpers are used, how efficient they are, which arguments
  get passed to a partial - and more.
* Use the efficiency rating to gain a rough idea about the performance level of your template once compiled.
  The scale ranges from minus infinity (uncompilable template) to a value between 0 and 2 (float) indicating
  how well the ViewHelpers compile and therefore how well the template is expected to perform.


Credits
-------

Icons by Rafał Brzeski (@RafalBrzeski)
